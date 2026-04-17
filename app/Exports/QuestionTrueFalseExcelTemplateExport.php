<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class QuestionTrueFalseExcelTemplateExport implements FromArray, WithHeadings, WithEvents, WithStyles
{
    public function headings(): array
    {
        return [
            ['PETUNJUK PENGISIAN SOAL BENAR/SALAH'],
            ['1. Kolom NO diisi dengan nomor urut soal.'],
            ['2. Tulis teks SOAL pada baris pertama di setiap blok nomor.'],
            ['3. Kolom JAWABAN sudah otomatis terisi "Benar" dan "Salah".'],
            ['4. Kolom KUNCI wajib diisi angka 1 pada salah satu baris (Benar atau Salah).'],
            [''],
            ['NO', 'SOAL', 'JAWABAN', 'KUNCI']
        ];
    }

    public function array(): array
    {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            // Contoh pengisian untuk soal nomor 1
            $soalText = ($i === 1) ? "Contoh: Matahari terbit dari sebelah timur?" : "";
            $kunciBenar = ($i === 1) ? "1" : ""; // Contoh kunci jawaban Benar

            // Baris 1: Opsi Benar
            $data[] = [$i, $soalText, 'Benar', $kunciBenar];

            // Baris 2: Opsi Salah
            $data[] = ['', '', 'Salah', ''];
        }
        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            7 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'], // Warna Indigo
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $questionCount = 50;
                $totalRows = ($questionCount * 2) + 7;

                // 1. Merge Petunjuk
                foreach (range(1, 5) as $row) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                }

                // 2. Pengaturan Lebar Kolom
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(80);
                $sheet->getColumnDimension('C')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(10);

                // 3. Border Umum
                $sheet->getStyle("A7:D{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

                // 4. Styling per Blok (Tiap Soal 2 Baris)
                for ($i = 0; $i < $questionCount; $i++) {
                    $blockStart = 8 + ($i * 2);
                    $blockEnd = $blockStart + 1;

                    // Beri warna abu-abu tipis pada baris ke-2 kolom SOAL agar user tahu tidak perlu diisi
                    $sheet->getStyle("B{$blockEnd}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F3F4F6');

                    // Outline tebal untuk memisahkan antar soal
                    $sheet->getStyle("A{$blockStart}:D{$blockEnd}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                            ],
                        ],
                    ]);

                    // Opsional: Merge No dan Soal agar lebih rapi (2 baris jadi 1 cell)
                    $sheet->mergeCells("A{$blockStart}:A{$blockEnd}");
                    $sheet->mergeCells("B{$blockStart}:B{$blockEnd}");
                }

                // 5. Alignment
                $sheet->getStyle("A8:A{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D8:D{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A8:B{$totalRows}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("B8:C{$totalRows}")->getAlignment()->setWrapText(true);
            },
        ];
    }
}
