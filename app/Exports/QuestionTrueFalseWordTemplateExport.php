<?php

namespace App\Exports;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class QuestionTrueFalseWordTemplateExport
{
    public static function export()
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText("PETUNJUK PENGISIAN SOAL BENAR/SALAH", ['bold' => true, 'size' => 14]);
        $section->addListItem("Tulis teks SOAL di baris pertama setiap blok.");
        $section->addListItem("Kolom JAWABAN sudah otomatis terisi Benar dan Salah.");
        $section->addListItem("Isi kolom KUNCI dengan angka 1 untuk jawaban benar.");
        $section->addListItem("Kolom KUNCI wajib diisi angka 1 pada salah satu baris (Benar atau Salah).");
        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ];
        $firstRowStyle = ['bgColor' => '333333'];
        $yellowStyle = ['bgColor' => 'FFFF00'];
        $phpWord->addTableStyle('QuestionTable', $tableStyle);

        $table = $section->addTable('QuestionTable');

        $table->addRow();
        $table->addCell(800, $firstRowStyle)->addText("NO", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(5000, $firstRowStyle)->addText("SOAL", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(3500, $firstRowStyle)->addText("JAWABAN", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $table->addCell(1200, $firstRowStyle)->addText("KUNCI", ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);

        for ($i = 1; $i <= 50; $i++) {
            $table->addRow();
            $table->addCell(800)->addText($i, null, ['alignment' => Jc::CENTER]);
            $table->addCell(5000)->addText($i === 1 ? "Contoh: Apa Jakarta adalah ibukota Indonesia?" : "");
            $table->addCell(3500)->addText("Benar");
            $table->addCell(1200)->addText($i === 1 ? "1" : "", null, ['alignment' => Jc::CENTER]);

            $table->addRow();
            $table->addCell(800);
            $table->addCell(5000, $yellowStyle);
            $table->addCell(3500)->addText("Salah");
            $table->addCell(1200)->addText("", null, ['alignment' => Jc::CENTER]);
        }

        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $fileName = 'template_soal_benar_salah_' . now()->format('Ymd_His') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $objWriter->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
