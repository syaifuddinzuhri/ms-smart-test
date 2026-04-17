<?php

namespace App\Exports;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class QuestionPgWordTemplateExport
{
    public static function export()
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // 1. Judul & Petunjuk
        $section->addText("PETUNJUK PENGISIAN SOAL (WORD)", ['bold' => true, 'size' => 14]);
        $section->addListItem("Tulis teks SOAL di baris pertama setiap blok.");
        $section->addListItem("Baris 1-5 adalah pilihan JAWABAN (A, B, C, D, E).");
        $section->addListItem("Isi kolom KUNCI dengan angka 1 untuk jawaban benar.");
        $section->addListItem("Jangan menghapus baris nomor agar sistem tidak bingung.");
        $section->addTextBreak(1);

        // 2. Definisi Style Tabel
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ];
        $firstRowStyle = ['bgColor' => '333333'];
        $yellowStyle = ['bgColor' => 'FFFF00'];
        $phpWord->addTableStyle('QuestionTable', $tableStyle);

        $table = $section->addTable('QuestionTable');

        // Header Tabel
        $table->addRow();
        $table->addCell(800, $firstRowStyle)->addText("NO", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(5000, $firstRowStyle)->addText("SOAL", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(3500, $firstRowStyle)->addText("JAWABAN", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(1200, $firstRowStyle)->addText("KUNCI", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);

        // 3. Loop 50 Soal
        for ($i = 1; $i <= 50; $i++) {
            // Baris 1: Nomor & Soal (Serta Opsi A)
            $table->addRow();
            $table->addCell(800)->addText($i, null, ['alignment' => Jc::CENTER]);
            $table->addCell(5000)->addText($i === 1 ? "Contoh: Apa ibukota Indonesia?" : "");
            $table->addCell(3500)->addText($i === 1 ? "Jakarta" : "");
            $table->addCell(1200)->addText($i === 1 ? "1" : "", null, ['alignment' => Jc::CENTER]);

            // Baris 2-5: Opsi B-E (Kolom SOAL diwarnai Kuning)
            $options = ['B', 'C', 'D', 'E'];
            foreach ($options as $opt) {
                $table->addRow();
                $table->addCell(800); // Kosong (Nomor)
                $table->addCell(5000, $yellowStyle); // Kolom Soal Kuning
                $table->addCell(3500)->addText($i === 1 && $opt === 'B' ? "Surabaya" : "");
                $table->addCell(1200); // Kosong (Kunci)
            }
        }

        // 4. Download File
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $fileName = 'template_soal_pilihan_ganda_' . now()->format('Ymd_His') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $objWriter->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
