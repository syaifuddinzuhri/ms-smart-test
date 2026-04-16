<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Cell;
use Exception;

class QuestionPgWordImport
{
    protected $subjectId;
    protected $categoryId;

    // Properti untuk menampung error agar konsisten dengan Excel
    public array $importErrors = [];

    public function __construct($subjectId, $categoryId)
    {
        $this->subjectId = $subjectId;
        $this->categoryId = $categoryId;
    }

    public function import($filePath)
    {
        $phpWord = IOFactory::load($filePath);
        $sections = $phpWord->getSections();
        $table = null;

        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof Table) {
                    $table = $element;
                    break 2;
                }
            }
        }

        if (!$table) {
            throw new Exception("Tabel data tidak ditemukan di file Word.");
        }

        $rows = $table->getRows();
        $dataRows = array_slice($rows, 1);
        $chunks = array_chunk($dataRows, 5);

        $maxOptionsFound = 0;
        $validChunks = [];

        foreach ($chunks as $chunk) {
            $firstRowCells = $chunk[0]->getCells();
            $questionText = $this->getCellValue($firstRowCells[1]);

            if (empty($questionText))
                continue;

            $currentOptionsCount = 0;
            foreach ($chunk as $row) {
                $optionText = $this->getCellValue($row->getCells()[2] ?? null);
                if (!empty($optionText)) {
                    $currentOptionsCount++;
                }
            }

            // Simpan jumlah opsi terbesar yang ditemukan di dokumen
            if ($currentOptionsCount > $maxOptionsFound) {
                $maxOptionsFound = $currentOptionsCount;
            }

            $validChunks[] = [
                'question_text' => $questionText,
                'chunk' => $chunk,
                'options_count' => $currentOptionsCount
            ];
        }

        $requiredOptions = max(3, min(5, $maxOptionsFound));

        foreach ($chunks as $index => $chunk) {
            // Kalkulasi baris (Header 1 + (index * 5) + 1 untuk start)
            $visualRow = ($index * 5) + 2;
            $nomorSoal = $index + 1;

            $firstRowCells = $chunk[0]->getCells();
            $questionText = $this->getCellValue($firstRowCells[1]);

            if (empty($questionText))
                continue;

            $optionsData = [];
            $correctCount = 0;

            foreach ($chunk as $row) {
                $cells = $row->getCells();
                $optionText = $this->getCellValue($cells[2] ?? null);
                $isCorrect = trim($this->getCellValue($cells[3] ?? null)) === '1';

                if (!empty($optionText)) {
                    $optionsData[] = [
                        'text' => $optionText,
                        'is_correct' => $isCorrect,
                    ];
                    if ($isCorrect)
                        $correctCount++;
                }
            }

            // Validasi Logika: Simpan ke importErrors, jangan langsung throw
            $errorMessage = null;
            if (count($optionsData) !== $requiredOptions) {
                $errorMessage = "Jumlah opsi tidak selaras. Ditemukan soal lain dengan {$requiredOptions} opsi, maka soal ini wajib memiliki {$requiredOptions} opsi (Saat ini: " . count($optionsData) . ").";
            } elseif ($correctCount === 0) {
                $errorMessage = "Belum ada kunci jawaban (angka 1).";
            }

            if ($errorMessage) {
                $this->importErrors[] = [
                    'row' => $visualRow,
                    'no' => $nomorSoal,
                    'question' => substr($questionText, 0, 50) . '...',
                    'reason' => $errorMessage
                ];
                continue;
            }

            // Simpan ke DB (akan di-rollback oleh DB::transaction di Page jika ada error)
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
                    'row' => $visualRow,
                    'reason' => "Database Error: " . $e->getMessage()
                ];
            }
        }

        // Kunci Atomicity: Jika ada error terkumpul, lempar exception untuk trigger rollback
        if (!empty($this->importErrors)) {
            throw new Exception("Validasi Gagal");
        }
    }

    private function getCellValue($cell)
    {
        if (!$cell)
            return '';
        $text = '';
        if ($cell instanceof Cell) {
            foreach ($cell->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText();
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $subElement) {
                        if (method_exists($subElement, 'getText')) {
                            $text .= $subElement->getText();
                        }
                    }
                }
            }
        }
        return trim($text);
    }
}
