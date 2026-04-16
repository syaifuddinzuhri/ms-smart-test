<?php

namespace App\Imports;

use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class QuestionPgImport implements ToCollection
{
    public array $importErrors = []; // Menampung semua daftar error

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

        foreach ($chunks as $index => $chunk) {
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

            // Validasi: Simpan ke array importErrors alih-alih langsung throw
            $errorMessage = null;
            if (count($optionsData) < 3) {
                $errorMessage = "Minimal harus ada 3 pilihan jawaban.";
            } elseif ($correctCount === 0) {
                $errorMessage = "Belum ada kunci jawaban (angka 1).";
            }

            if ($errorMessage) {
                $this->importErrors[] = [
                    'row' => $excelRow,
                    'question' => substr($questionText, 0, 50) . '...',
                    'reason' => $errorMessage
                ];
                continue;
            }

            // Jika sampai sini dan belum ada error sama sekali di baris-baris sebelumnya,
            // kita lakukan insert. Jika nanti di akhir ada error, ini akan di-rollback.
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
        }

        // KUNCI ATOMICITY:
        // Jika setelah semua baris dicek ternyata ada error, lempar satu Exception besar
        if (!empty($this->importErrors)) {
            throw new \Exception("Validasi Gagal");
        }
    }
}
