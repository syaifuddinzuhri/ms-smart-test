<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionPgImport implements ToCollection
{
    public array $importErrors = [];

    protected $subjectId;
    protected $categoryId;

    public function __construct($subjectId, $categoryId)
    {
        $this->subjectId = $subjectId;
        $this->categoryId = $categoryId;
    }

    public function collection(Collection $rows)
    {
        $dataRows = $rows->slice(7);
        $chunks = $dataRows->chunk(5);

        // --- TAHAP 1: SCANNING UNTUK MENENTUKAN STANDAR JUMLAH OPSI ---
        $maxOptionsFound = 0;
        foreach ($chunks as $chunk) {
            $questionText = $chunk->first()[1] ?? null;
            if (empty($questionText))
                continue;

            $currentOptionsCount = 0;
            foreach ($chunk as $row) {
                if (!empty($row[2])) {
                    $currentOptionsCount++;
                }
            }
            if ($currentOptionsCount > $maxOptionsFound) {
                $maxOptionsFound = $currentOptionsCount;
            }
        }

        // Tentukan standar: minimal 3, maksimal 5 (berdasarkan temuan di file)
        $requiredOptions = max(3, min(5, $maxOptionsFound));

        foreach ($chunks as $index => $chunk) {
            $nomorSoal = $index + 1;
            $excelRow = ($index * 5) + 8;
            $firstRow = $chunk->first();
            $questionText = $firstRow[1];

            if (empty($questionText))
                continue;

            $optionsData = [];
            $correctCount = 0;

            foreach ($chunk as $row) {
                $optionText = $row[2];
                $isCorrect = (int) ($row[3] ?? 0) === 1;

                if (!empty($optionText)) {
                    $optionsData[] = [
                        'text' => $optionText,
                        'is_correct' => $isCorrect,
                    ];
                    if ($isCorrect)
                        $correctCount++;
                }
            }

            // Validasi Ketat & Selaras
            $errorMessage = null;

            if (count($optionsData) !== $requiredOptions) {
                $errorMessage = "Jumlah opsi tidak selaras. Ditemukan soal lain dengan {$requiredOptions} opsi, maka soal ini wajib memiliki {$requiredOptions} opsi (Saat ini: " . count($optionsData) . ").";
            } elseif ($correctCount === 0) {
                $errorMessage = "Belum ada kunci jawaban (angka 1).";
            }

            if ($errorMessage) {
                $this->importErrors[] = [
                    'no' => $nomorSoal,
                    'row' => $excelRow,
                    'question' => substr($questionText, 0, 50) . '...',
                    'reason' => $errorMessage
                ];
                continue;
            }

            try {
                $type = ($correctCount > 1) ? QuestionType::MULTIPLE_CHOICE->value : QuestionType::SINGLE_CHOICE->value;
                $question = Question::create([
                    'subject_id' => $this->subjectId,
                    'question_category_id' => $this->categoryId,
                    'question_text' => $questionText,
                    'question_type' => $type,
                ]);

                $labels = range('A', 'E');
                foreach ($optionsData as $idx => $opt) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                        'label' => $labels[$idx],
                        'order' => $idx,
                    ]);
                }
            } catch (Exception $e) {
                $this->importErrors[] = [
                    'no' => $nomorSoal,
                    'row' => $excelRow,
                    'reason' => "Database Error: " . $e->getMessage()
                ];
            }
        }

        // KUNCI ATOMICITY:
        // Jika setelah semua baris dicek ternyata ada error, lempar satu Exception besar
        if (!empty($this->importErrors)) {
            throw new Exception("Validasi Gagal");
        }
    }
}
