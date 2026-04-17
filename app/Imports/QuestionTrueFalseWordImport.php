<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuestionTrueFalseWordImport extends BaseQuestionWordImport
{
    public function import($filePath)
    {
        $markdownContent = $this->convertDocxToMarkdown($filePath);

        $rows = $this->parseMarkdownTable($markdownContent);

        if (empty($rows))
            throw new Exception("Tabel data tidak ditemukan atau format salah.");

        $chunks = array_chunk(array_slice($rows, 1), 2);

        $preparedData = [];
        $isSequenceBroken = false;

        foreach ($chunks as $index => $chunk) {
            $visualRow = ($index * 2) + 2;
            $nomorSoal = $index + 1;

            $questionText = trim($chunk[0][1] ?? '');
            if (empty($questionText)) {
                $isSequenceBroken = true;
                continue;
            }

            if ($isSequenceBroken && !empty($questionText)) {
                $this->addError(
                    $visualRow,
                    $nomorSoal,
                    $questionText,
                    "Nomor soal melompat. Nomor soal sebelumnya kosong, harap pastikan soal berurutan tanpa ada nomor yang dilewati."
                );
            }

            $optionsData = [];
            $correctCount = 0;

            foreach ($chunk as $row) {
                $optionText = trim($row[2] ?? '');
                $isCorrect = trim($row[3] ?? '') === '1';

                if (!empty($optionText)) {
                    $optionsData[] = ['text' => $optionText, 'is_correct' => $isCorrect];
                    if ($isCorrect)
                        $correctCount++;
                }
            }

            $foundFile = $this->checkAttachment($nomorSoal);

            $error = null;
            if ($correctCount !== 1) {
                $error = "Kunci jawaban harus tepat satu (angka 1).";
            } elseif (count($optionsData) < 2) {
                $error = "Opsi Benar/Salah tidak lengkap.";
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

        $requiredOptions = 2;
        foreach ($preparedData as $key => $item) {
            if (count($item['options']) !== $requiredOptions) {
                $this->addError($item['visual_row'], $item['no'], $item['text'], "Jumlah opsi wajib {$requiredOptions}.");
            }
        }

        if (!empty($this->importErrors))
            throw new Exception("Validasi Gagal");

        if (empty($preparedData)) {
            throw new Exception("Gagal import, data tidak ditemukan dalam file Word.");
        }

        foreach ($preparedData as $item) {
            try {
                $question = Question::create([
                    'subject_id' => $this->subjectId,
                    'question_category_id' => $this->categoryId,
                    'question_text' => $item['text'],
                    'question_type' => QuestionType::TRUE_FALSE->value,
                ]);

                $finalText = $this->processHtmlImages($item['text'], $question->id);
                $question->update(['question_text' => $finalText]);

                $labels = ['A', 'B'];
                foreach ($item['options'] as $idx => $opt) {
                    $finalOptText = $this->processHtmlImages($opt['text'], $question->id);
                    $question->options()->create([
                        'question_id' => $question->id,
                        'text' => $finalOptText,
                        'is_correct' => $opt['is_correct'],
                        'label' => $labels[$idx] ?? 'A',
                        'order' => $idx,
                    ]);
                }

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
                throw $e;
            }
        }
        $this->cleanup();
    }
}
