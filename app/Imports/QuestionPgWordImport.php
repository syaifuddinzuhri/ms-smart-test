<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Equation;
use PhpOffice\PhpWord\Element\Text;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Style\Paragraph;

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

    public function import($filePath)
    {
        libxml_use_internal_errors(true);

        try {
            // FIX: Gunakan Reader Word2007 secara eksplisit
            $reader = IOFactory::createReader('Word2007');
            if (!$reader->canRead($filePath)) {
                throw new Exception("File tidak dapat dibaca atau format salah.");
            }
            $phpWord = $reader->load($filePath);
        } catch (Exception $e) {
            throw new Exception("Gagal memuat file Word: " . $e->getMessage());
        }

        $table = $this->findTable($phpWord);

        if (!$table)
            throw new Exception("Tabel data tidak ditemukan.");

        $rows = $table->getRows();
        $dataRows = array_slice($rows, 1);
        $chunks = array_chunk($dataRows, 5);

        $maxOptionsFound = 0;
        $preparedData = [];

        // --- STAGE 1: VALIDASI & PREPARASI ---
        foreach ($chunks as $index => $chunk) {
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
                'text' => $questionText,
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

        $html = '';
        foreach ($cell->getElements() as $element) {
            $html .= $this->renderElement($element);
        }

        return $html;
    }

    private function renderElement($element)
    {
        $text = '';
        if ($element instanceof Text) {
            $text .= $element->getText();
        } elseif ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                $text .= $this->renderElement($child);
            }
        } elseif (method_exists($element, 'getOMML')) {
            $omml = $element->getOMML();
            $text .= $this->transformOmmlToMathml($omml);
        }
        return $text;
    }

    private function transformOmmlToMathml($omml)
    {
        $xslPath = resource_path('xml/OMML2MML.XSL');
        if (!file_exists($xslPath))
            return " [XSL Hilang] ";

        // XSL Transpect bekerja paling baik jika OMML dibungkus seperti ini:
        $ommlXml = '<?xml version="1.0" encoding="UTF-8"?>
        <m:oMath xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">
            ' . $omml . '
        </m:oMath>';

        $dom = new \DOMDocument();
        // Gunakan LIBXML_NOERROR agar tidak crash jika XML word kotor
        if (!@$dom->loadXML($ommlXml, LIBXML_NOERROR)) {
            return " [XML Rumus Rusak] ";
        }

        $xsl = new \DOMDocument();
        $xsl->load($xslPath);

        $proc = new \XSLTProcessor();
        $proc->importStyleSheet($xsl);

        $mathml = $proc->transformToXML($dom);

        if (!$mathml || empty(trim($mathml))) {
            // Jika gagal, coba bungkus dengan tag m:oMathPara (alternatif)
            $ommlXml = '<m:oMathPara xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math">' . $omml . '</m:oMathPara>';
            $dom->loadXML($ommlXml);
            $mathml = $proc->transformToXML($dom);
        }

        // Bersihkan hasil
        $mathml = preg_replace('/<\?xml.*\?>/', '', $mathml);
        $mathml = str_replace(["\n", "\r", "\t"], '', $mathml);

        return trim($mathml);
    }
}
