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

class QuestionChoiceExcelTemplateExport implements FromArray, WithHeadings, WithEvents, WithStyles
{
    public function headings(): array
    {
        return [
            ['PETUNJUK PENGISIAN SOAL PILIHAN GANDA'],
            ['1. Kolom NO diisi dengan nomor urut soal (sudah tersedia 1-50).'],
            ['2. Tulis teks SOAL sejajar dengan nomor soal.'],
            ['3. Kolom JAWABAN diisi dengan opsi (5 baris per soal).'],
            ['4. Kolom KUNCI diisi angka 1 untuk jawaban yang benar.'],
            [''],
            ['NO', 'SOAL', 'JAWABAN', 'KUNCI']
        ];
    }

    public function array(): array
    {
        $data = [];
        for ($i = 1; $i <= 50; $i++) {
            // Teks contoh hanya untuk soal nomor 1
            $soalText = ($i === 1) ? "Contoh: Apa ibukota Indonesia?" : "";
            $jawabanA = ($i === 1) ? "Jakarta" : "";
            $jawabanB = ($i === 1) ? "Surabaya" : "";
            $kunciA = ($i === 1) ? "1" : "";

            // Baris pertama blok soal (Ada nomor soal)
            $data[] = [$i, $soalText, $jawabanA, $kunciA];

            // 4 Baris berikutnya untuk opsi B-E (Tanpa nomor soal)
            $data[] = ['', '', $jawabanB, ''];
            $data[] = ['', '', '', ''];
            $data[] = ['', '', '', ''];
            $data[] = ['', '', '', ''];
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
                    'startColor' => ['rgb' => '333333'],
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
                $totalRows = (50 * 5) + 7;

                // 1. Merge Petunjuk
                foreach (range(1, 5) as $row) {
                    $sheet->mergeCells("A{$row}:D{$row}");
                }

                // 2. Pengaturan Lebar Kolom
                $sheet->getColumnDimension('A')->setWidth(6);  // NO (Pendek)
                $sheet->getColumnDimension('B')->setWidth(80); // SOAL (Lebar)
                $sheet->getColumnDimension('C')->setWidth(40); // JAWABAN (Sedang)
                $sheet->getColumnDimension('D')->setWidth(10); // KUNCI (Pendek)

                // 3. Border Kontinu A7 sampai D (akhir)
                $sheet->getStyle("A7:D{$totalRows}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // 4. Loop Styling per Blok Soal
                for ($i = 0; $i < 50; $i++) {
                    $blockStart = 8 + ($i * 5);
                    $blockEnd = $blockStart + 4;

                    // Mewarnai kolom SOAL (Kuning) mulai baris ke-2 hingga ke-5 di tiap blok
                    $sheet->getStyle("B" . ($blockStart + 1) . ":B{$blockEnd}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('FFFF00');

                    // Outline Tebal per nomor agar rapi
                    $sheet->getStyle("A{$blockStart}:D{$blockEnd}")->applyFromArray([
                        'borders' => [
                            'outline' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);

                    // Merge kolom Nomor Soal (A) agar nomor berada di tengah blok (Opsional)
                    // Jika ingin nomor sejajar baris pertama saja, abaikan merge ini.
                    // $sheet->mergeCells("A{$blockStart}:A{$blockEnd}");
                }

                // 5. Alignment Umum
                // Kolom NO dan KUNCI Rata Tengah
                $sheet->getStyle("A8:A{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D8:D{$totalRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Rata Atas untuk Nomor Soal agar sejajar baris pertama
                $sheet->getStyle("A8:A{$totalRows}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Wrap text untuk Soal dan Jawaban
                $sheet->getStyle("B8:C{$totalRows}")->getAlignment()->setWrapText(true);
            },
        ];
    }
}
