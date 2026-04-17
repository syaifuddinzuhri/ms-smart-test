<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Cell;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionPgWordImport
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

    /**
     * Pendeteksian Jalur Pandoc yang Sangat Kuat (Multi-OS)
     */
    private function getPandocPath()
    {
        // 1. Cek Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $winPaths = [
                'pandoc.exe',
                'C:\Program Files\Pandoc\pandoc.exe',
                'C:\Program Files (x86)\Pandoc\pandoc.exe'
            ];
            foreach ($winPaths as $path) {
                $check = (str_contains($path, ':')) ? file_exists($path) : shell_exec("where pandoc");
                if ($check)
                    return str_contains($path, ' ') ? '"' . $path . '"' : $path;
            }
        }

        // 2. Cek macOS (Darwin) & Linux
        $unixPaths = [
            '/usr/local/bin/pandoc',   // Intel Mac / Linux
            '/opt/homebrew/bin/pandoc', // Apple Silicon (M1/M2/M3)
            '/usr/bin/pandoc',          // Ubuntu/Debian Standard
            '/bin/pandoc'
        ];

        foreach ($unixPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // 3. Fallback: Cek via 'which' di Unix
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $path = trim(shell_exec('which pandoc'));
            if (!empty($path))
                return $path;
        }

        return 'pandoc'; // Berharap ada di system PATH
    }

    public function convertDocxToMarkdown($filePath)
    {
        if (!file_exists($filePath)) {
            throw new Exception("File sumber Docx tidak ditemukan.");
        }

        $pandoc = $this->getPandocPath();

        $tempFile = storage_path('app/temp_' . Str::random(10) . '.html');

        $command = sprintf(
            '%s %s -f docx -t html --columns=1000 -o %s 2>&1',
            $pandoc,
            escapeshellarg($filePath),
            escapeshellarg($tempFile)
        );

        // $tempFile = storage_path('app/temp_' . Str::random(10) . '.md');

        // // Menambahkan 2>&1 untuk menangkap pesan error dari sistem operasi
        // $command = sprintf(
        //     '%s %s -f docx -t gfm+pipe_tables --columns=1000 -o %s 2>&1',
        //     $pandoc,
        //     escapeshellarg($filePath),
        //     escapeshellarg($tempFile)
        // );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = implode(" ", $output);
            throw new Exception("Pandoc Error ({$returnVar}): {$errorMsg}. Path: {$pandoc}");
        }

        if (!file_exists($tempFile)) {
            throw new Exception("Gagal membuat file temporary markdown.");
        }

        $content = file_get_contents($tempFile);
        @unlink($tempFile); // Gunakan @ untuk suppress error jika file terkunci

        return $content;
    }

    private function parseMarkdownTable($markdown)
    {
        $tableData = [];
        $markdown = str_replace("\r\n", "\n", $markdown); // Normalisasi baris baru

        // --- MODE 1: HTML TABLE (Sering muncul di Ubuntu/Mac untuk sel kompleks) ---
        if (preg_match('/<table/i', $markdown)) {
            preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $markdown, $rows);

            foreach ($rows[1] as $rowHtml) {
                preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $rowHtml, $cols);

                $cleanCols = array_map(function ($col) {
                    // Konversi tag paragraph/break menjadi newline agar teks tidak menyatu
                    $col = preg_replace('/<(p|br)[^>]*>/i', '', $col);
                    $col = preg_replace('/<\/(p|br)>/i', "\n", $col);
                    $col = html_entity_decode($col); // Decode entity seperti &nbsp; atau &quot;
                    return trim(strip_tags($col, '<span><div><math>')); // Amankan tag rumus
                }, $cols[1]);

                if (!empty($cleanCols))
                    $tableData[] = $cleanCols;
            }

            if (!empty($tableData))
                return $tableData;
        }

        // --- MODE 2: PIPE TABLE (| Soal | Jawaban |) ---
        $lines = explode("\n", $markdown);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_contains($line, '|')) {
                $columns = array_map('trim', explode('|', trim($line, '|')));

                // Skip baris separator seperti |---|---|
                if (isset($columns[0]) && preg_match('/^[:\s-]*$/', $columns[0]))
                    continue;

                if (count($columns) >= 3)
                    $tableData[] = $columns;
            }
        }

        // --- MODE 3: GRID TABLE (+---+---+) ---
        // Jika Mode 2 gagal (biasanya Pandoc versi lama di Ubuntu), deteksi tanda '+'
        if (empty($tableData)) {
            foreach ($lines as $line) {
                if (str_contains($line, '+--') || trim($line) === '')
                    continue;
                if (str_contains($line, '|')) {
                    $columns = array_map('trim', explode('|', trim($line, '|')));
                    if (count($columns) >= 3)
                        $tableData[] = $columns;
                }
            }
        }

        return $tableData;
    }

    private function cleanLatex($text)
    {
        // Ubah \( ... \) menjadi $ ... $ dan \[ ... \] menjadi $$ ... $$
        $text = preg_replace('/\\\\\((.*?)\\\\\)/s', '$$1$', $text);
        $text = preg_replace('/\\\\\[(.*?)\\\\\d*\]/s', '$$$$1$$', $text);
        return $text;
    }

    public function import($filePath)
    {
        // STRATEGI BARU:
        // 1. Kita ambil data teks dari Pandoc (untuk mendapatkan LaTeX)
        // 2. Kita tetap pakai PHPWord untuk mendeteksi struktur tabel jika perlu,
        // TAPI karena Pandoc sudah menghasilkan tabel Markdown, kita bisa parsing langsung dari Markdown.

        $markdownContent = $this->convertDocxToMarkdown($filePath);

        // Parsing tabel Markdown ke Array
        $rows = $this->parseMarkdownTable($markdownContent);

        if (empty($rows))
            throw new Exception("Tabel data tidak ditemukan atau format salah.");

        $dataRows = array_slice($rows, 1);
        $chunks = array_chunk($dataRows, 5);

        $maxOptionsFound = 0;
        $preparedData = [];

        // --- STAGE 1: VALIDASI & PREPARASI ---
        foreach ($chunks as $index => $chunk) {
            $visualRow = ($index * 5) + 2;
            $nomorSoal = $index + 1;

            // Kolom 1 di Markdown biasanya Soal
            $questionText = trim($chunk[0][1] ?? '');
            if (empty($questionText))
                continue;

            $optionsData = [];
            $correctCount = 0;

            foreach ($chunk as $row) {
                $optionText = trim($row[2] ?? ''); // Kolom Opsi
                $isCorrect = trim($row[3] ?? '') === '1'; // Kolom Kunci

                if (!empty($optionText)) {
                    $optionsData[] = ['text' => $optionText, 'is_correct' => $isCorrect];
                    if ($isCorrect)
                        $correctCount++;
                }
            }

            // Update jumlah opsi maksimal untuk validasi selaras
            $maxOptionsFound = max($maxOptionsFound, count($optionsData));

            // Cek File Multimedia & Ukurannya
            $foundFile = $this->checkAttachment($nomorSoal);

            $error = null;
            if ($correctCount === 0) {
                $error = "Belum ada kunci jawaban (angka 1).";
            } elseif ($foundFile && $foundFile['size'] > (3 * 1024 * 1024)) {
                $error = "File {$foundFile['name']} melebihi batas maksimal 3MB.";
            }

            if ($error) {
                $this->addError($visualRow, $nomorSoal, $questionText, $error);
            }

            $preparedData[] = [
                'visual_row' => $visualRow,
                'no' => $nomorSoal,
                'text' => $this->cleanLatex($questionText),
                'options' => $optionsData,
                'correct_count' => $correctCount,
                'attachment' => $foundFile
            ];
        }

        // Validasi keselarasan jumlah opsi
        $requiredOptions = max(3, min(5, $maxOptionsFound));
        foreach ($preparedData as $key => $item) {
            if (count($item['options']) !== $requiredOptions) {
                $this->addError($item['visual_row'], $item['no'], $item['text'], "Jumlah opsi wajib {$requiredOptions}.");
            }
        }

        // Jika ada error di tahap validasi, hentikan sebelum masuk DB
        if (!empty($this->importErrors)) {
            throw new Exception("Validasi Gagal");
        }

        // --- STAGE 2: PROSES INSERT ---
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

                // Simpan Opsi
                foreach ($item['options'] as $idx => $opt) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'text' => $opt['text'],
                        'is_correct' => $opt['is_correct'],
                        'label' => range('A', 'E')[$idx],
                        'order' => $idx,
                    ]);
                }

                // Simpan Attachment jika ada
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
                $this->addError($item['visual_row'], $item['no'], $item['text'], "Database Error: " . $e->getMessage());
                throw $e; // Trigger rollback
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

    private function findTable($phpWord)
    {
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table)
                    return $element;
            }
        }
        return null;
    }

    private function getCellValue($cell)
    {
        if (!$cell instanceof Cell)
            return '';
        $text = '';
        foreach ($cell->getElements() as $element) {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText();
            } elseif (method_exists($element, 'getElements')) {
                foreach ($element->getElements() as $sub) {
                    if (method_exists($sub, 'getText'))
                        $text .= $sub->getText();
                }
            }
        }
        return trim($text);
    }
}
