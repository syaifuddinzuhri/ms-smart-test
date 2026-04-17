<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionChoiceExcelImport implements ToCollection
{
    protected $subjectId;
    protected $categoryId;
    protected $basePath;
    protected $fileMap = [];

    public array $importErrors = [];

    public function __construct($subjectId, $categoryId, $basePath = null)
    {
        $this->subjectId = $subjectId;
        $this->categoryId = $categoryId;
        $this->basePath = $basePath;
        $this->indexFiles();
    }

    private function indexFiles()
    {
        if (!$this->basePath || !is_dir($this->basePath))
            return;

        $files = File::allFiles($this->basePath);
        foreach ($files as $file) {
            $this->fileMap[strtolower($file->getFilename())] = $file->getRealPath();
        }
    }

    public function collection(Collection $rows)
    {
        // Data dimulai dari baris ke-8 (index 7)
        $dataRows = $rows->slice(7);
        $chunks = $dataRows->chunk(5);

        $maxOptionsFound = 0;
        $preparedData = [];
        $isSequenceBroken = false;

        // --- STAGE 1: SCANNING & VALIDASI ---
        foreach ($chunks as $index => $chunk) {
            $nomorSoal = $index + 1;
            $excelRow = ($index * 5) + 8;

            $firstRow = $chunk->first();
            $questionText = $firstRow[1] ?? null;

            if (empty(trim($questionText))) {
                $isSequenceBroken = true;
                continue;
            }

            if ($isSequenceBroken && !empty($questionText)) {
                $this->addError(
                    $excelRow,
                    $nomorSoal,
                    $questionText,
                    "Nomor soal melompat. Nomor soal sebelumnya kosong, harap pastikan soal berurutan tanpa ada nomor yang dilewati."
                );
            }

            $optionsData = [];
            $correctCount = 0;

            foreach ($chunk as $row) {
                $optionText = $row[2] ?? null;
                $isCorrect = (int) ($row[3] ?? 0) === 1;

                if (!empty(trim($optionText))) {
                    $optionsData[] = [
                        'text' => $optionText,
                        'is_correct' => $isCorrect,
                    ];
                    if ($isCorrect)
                        $correctCount++;
                }
            }

            // Hitung max opsi untuk validasi keselarasan nanti
            $maxOptionsFound = max($maxOptionsFound, count($optionsData));

            // Cek Attachment (Gambar/Audio/Video)
            $foundFile = $this->checkAttachment($nomorSoal);

            // Validasi awal
            $error = null;
            if ($correctCount === 0) {
                $error = "Belum ada kunci jawaban (angka 1).";
            } elseif ($foundFile && $foundFile['size'] > (20 * 1024 * 1024)) {
                $error = "File {$foundFile['name']} melebihi batas maksimal 20MB.";
            }

            if ($error) {
                $this->addError($excelRow, $nomorSoal, $questionText, $error);
            }

            $preparedData[] = [
                'excel_row' => $excelRow,
                'no' => $nomorSoal,
                'text' => $questionText,
                'options' => $optionsData,
                'correct_count' => $correctCount,
                'attachment' => $foundFile
            ];
        }

        // Validasi keselarasan jumlah opsi (min 3, max 5)
        $requiredOptions = max(3, min(5, $maxOptionsFound));
        foreach ($preparedData as $item) {
            if (count($item['options']) !== $requiredOptions) {
                $this->addError($item['excel_row'], $item['no'], $item['text'], "Jumlah opsi wajib {$requiredOptions}.");
            }
        }

        // Jika ada error di tahap validasi, hentikan sebelum masuk DB
        if (!empty($this->importErrors)) {
            throw new Exception("Validasi Gagal");
        }

        if (empty($preparedData)) {
            throw new Exception("Gagal import, data tidak ditemukan dalam file Excel.");
        }

        // --- STAGE 2: PROSES INSERT (ATOMIC) ---
        foreach ($preparedData as $item) {
            try {
                $type = ($item['correct_count'] > 1)
                    ? QuestionType::MULTIPLE_CHOICE->value
                    : QuestionType::SINGLE_CHOICE->value;

                $question = Question::create([
                    'subject_id' => $this->subjectId,
                    'question_category_id' => $this->categoryId,
                    'question_text' => $item['text'],
                    'question_type' => $type,
                ]);

                $labels = range('A', 'E');
                foreach ($item['options'] as $idx => $opt) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                        'label' => $labels[$idx],
                        'order' => $idx,
                    ]);
                }

                // Simpan Attachment jika ditemukan di dalam ZIP
                if ($item['attachment']) {
                    $newPath = generateFilePath('questions', $question->id, 1, $item['attachment']['path']);
                    Storage::disk('public')->put($newPath, file_get_contents($item['attachment']['path']));

                    $question->attachments()->create([
                        'id' => Str::uuid(),
                        'file_path' => $newPath,
                        'type' => $item['attachment']['type'],
                    ]);
                }
            } catch (Exception $e) {
                throw new Exception("Baris Excel {$item['excel_row']}: Database Error - " . $e->getMessage());
            }
        }
    }

    private function checkAttachment($nomorSoal)
    {
        if (!$this->basePath)
            return null;

        if (empty($this->fileMap))
            return null;

        $extensions = [
            'image' => ['png', 'jpg', 'jpeg', 'gif'],
            'audio' => ['mp3', 'wav'],
            'video' => ['mp4', 'webm'],
        ];

        foreach ($extensions as $type => $exts) {
            foreach ($exts as $ext) {
                $searchName = strtolower("soal-{$nomorSoal}.{$ext}");

                if (isset($this->fileMap[$searchName])) {
                    $fullPath = $this->fileMap[$searchName];

                    return [
                        'path' => $fullPath,
                        'name' => basename($fullPath),
                        'size' => filesize($fullPath),
                        'type' => $type
                    ];
                }
            }
        }
        return null;
    }

    private function addError($row, $no, $text, $reason)
    {
        $this->importErrors[] = [
            'row' => $row,
            'no' => $no,
            'question' => Str::limit($text, 50),
            'reason' => $reason
        ];
    }
}
