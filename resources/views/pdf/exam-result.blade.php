<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Hasil Ujian - {{ $session->user->name }}</title>
    <style>
        @font-face {
            font-family: 'Amiri';
            src: url('{{ public_path('fonts/Amiri-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Amiri';
            src: url('{{ public_path('fonts/Amiri-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        body {
            /* AMIRI HARUS DI URUTAN PERTAMA */
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Header Style */
        .header {
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .exam-desc {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .exam-title {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .exam-subtitle {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Summary Table */
        .summary-container {
            width: 100%;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .summary-header {
            background: #f8fafc;
            padding: 10px;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 10px;
            font-size: 11px;
            border-bottom: 1px solid #f0f0f0;
        }

        .arabic-font {
            font-family: 'Amiri', serif !important;
            font-size: 16px;
            /* Arab dibuat lebih besar agar terbaca */
            color: #1a202c;
            line-height: 1.8;
            text-align: right !important;
            /* Karena Ar-PHP sudah membalik teks, jangan pakai direction: rtl */
        }

        /* Badge Styles */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-warning {
            background-color: #ffedd5;
            color: #9a3412;
        }

        .soal-content,
        .option-item,
        .essay-answer {
            word-wrap: break-word;
        }

        .soal-content p {
            margin: 0;
            padding: 0;
        }

        [dir="rtl"] {
            text-align: right;
            direction: rtl;
        }


        /* Mimik Tailwind Typography (Prose) */
        .soal-content {
            font-size: 12px;
            color: #374151;
            margin-bottom: 10px;
            /* text-gray-700 */
        }

        .soal-content p {
            margin-bottom: 10px;
        }

        /* 1. GLOBAL STYLE UNTUK SEMUA GAMBAR SOAL (NON-LATEX) */
        .soal-content img {
            max-width: 200px;
            height: auto;
            display: inline-block;
            vertical-align: middle;
            margin: 5px 0;
        }

        /* 2. BASE STYLE UNTUK SEMUA LATEX (CODECOGS) */
        .latex-img {
            vertical-align: middle;
            margin: 5px 0;
            display: inline-block;
            max-width: none !important;
            /* LaTeX tidak boleh kena max-width 200px */
            image-rendering: -webkit-optimize-contrast;
        }

        /* 3. KHUSUS DI BAGIAN PERTANYAAN (LEBIH BESAR) */
        /* Pastikan di Blade, div soal utama menggunakan class .question-text */
        .question-text .latex-img {
            /* Biarkan tinggi menyesuaikan proporsi rumus asli */
            height: auto !important;

            /* Batas maksimal tinggi agar tidak merusak layout halaman */
            max-height: 50px;

            /* Batas lebar maksimal agar tidak menabrak pinggir card */
            max-width: 100%;

            /* Batas minimal agar variabel kecil (seperti 'x') tetap terbaca */
            min-height: 20px;

            vertical-align: middle;
            margin: 10px 0;
            display: inline-block;
        }

        /* 4. KHUSUS DI BAGIAN OPSI (LEBIH KECIL/PROPORSIONAL) */
        /* Pastikan di Blade, kolom opsi menggunakan class .option-content-cell */
        .option-content-cell .latex-img {
            /* Menjaga proporsi agar tidak gepeng/melar */
            height: auto !important;

            /* Batasi tinggi supaya kotak pilihan (A, B, C) tidak terlalu tinggi */
            max-height: 30px;

            /* Sisakan ruang untuk teks/badge 'Jawaban Peserta' di kanan */
            max-width: 85%;

            /* Pastikan tidak terlalu kecil */
            min-height: 15px;

            vertical-align: middle;
            margin: 5px 0;
            display: inline-block;
        }

        /* Question Cards */
        .question-card {
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            margin-bottom: 20px;
            padding: 15px;
            position: relative;
            page-break-inside: avoid;
            /* Penting agar soal tidak terpotong halaman */
        }

        .card-correct {
            /* border-left: 5px solid #22c55e; */
            background-color: #f0fff4;
        }

        .card-wrong {
            /* border-left: 5px solid #ef4444; */
            background-color: #fff5f5;
        }

        .card-pending {
            /* border-left: 5px solid #f97316; */
            background-color: #fffaf0;
        }

        .question-number {
            font-weight: bold;
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 10px;
            display: block;
        }


        /* Options Style */
        /* Styling Option Item dengan Table-Look */
        .option-item {
            margin-bottom: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 15px;
            /* Padding lebih luas agar rapi */
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        .option-item table {
            width: 100%;
            border-collapse: collapse;
        }

        .option-letter {
            font-weight: bold;
            vertical-align: top;
            text-align: left;
            width: 25px;
            font-size: 11px;
            padding-top: 2px;
        }

        .option-content-cell {
            vertical-align: top;
            text-align: left;
            width: auto
                /* Kolom ini akan mengambil sisa ruang */
        }

        .badge-user-cell {
            vertical-align: middle;
            text-align: right;
            width: 100px;
        }

        .option-selected-correct {
            border: 1px solid #22c55e;
            background-color: #dcfce7;
            font-weight: bold;
        }

        .option-selected-wrong {
            border: 1px solid #ef4444;
            background-color: #fee2e2;
            font-weight: bold;
        }


        .label-user-answer {
            font-size: 9px;
            font-weight: bold;
            color: #666;
            background: #eee;
            padding: 2px 4px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }

        .essay-answer {
            padding: 10px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            font-size: 10px;
            font-style: italic;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 9px;
            color: #aaa;
            text-align: center;
        }

        .score-footer {
            text-align: right;
            font-size: 10px;
            font-weight: bold;
            margin-top: 10px;
            color: #666;
        }

        .badge-user-pdf {
            font-size: 9px;
            font-weight: bold;
            color: #4b5563;
            text-transform: uppercase;
            white-space: nowrap;
            display: flex;
            align-items: center
        }
    </style>
</head>

<body>

    {{-- HEADER (Sama dengan Template Token) --}}
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                @if ($logo)
                    <td style="width: 65px; border: none; vertical-align: middle; padding-right: 15px;">
                        <img src="{{ $logo }}" style="width: 60px; height: auto;">
                    </td>
                @endif
                <td style="border: none; vertical-align: middle; text-align: left;">
                    <div class="exam-title">HASIL UJIAN PESERTA</div>
                    <div class="exam-subtitle">MANUSGI SMART TEST - {{ $exam->title }}</div>
                </td>
            </tr>
        </table>
        <div class="exam-desc">
            <table style="width: 100%; border: none; margin-bottom: 0;">
                <tr>
                    <td style="text-align: left; border: none; padding: 0; width: 50%;">
                        <strong>Mata Pelajaran:</strong> {{ $exam->subject?->name ?? '-' }} <br>
                        <strong>Kelas:</strong>
                        {{ $exam->classrooms->map(fn($c) => "{$c->name}" . ($c->major ? "-{$c->major->code}" : ''))->implode(', ') ?: '-' }}
                    </td>
                    <td style="text-align: right; border: none; padding: 0; width: 50%;">
                        <strong>Tanggal Mulai:</strong> {{ $record->started_at->format('d/m/Y') }} <br>
                        <strong>Durasi Pengerjaan:</strong> {{ $duration }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- INFORMASI PESERTA & RANGKUMAN --}}
    <div class="summary-container">
        <div class="summary-header">RANGKUMAN HASIL</div>
        <table class="summary-table">
            <tr>
                <td width="20%"><strong>Nama Peserta</strong></td>
                <td width="30%">: {{ $session->user->name }}</td>
                <td width="20%"><strong>Skor Akhir</strong></td>
                <td width="30%">: <span
                        style="font-size: 14px; font-weight: bold;">{{ $session->total_score }}</span></td>
            </tr>
            <tr>
                <td><strong>Kelas</strong></td>
                <td>: {{ $session->user->student->classroom->name ?? '-' }}</td>
                <td><strong>KKM / Min. Skor</strong></td>
                <td>: {{ $passingGrade ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Mata Pelajaran</strong></td>
                <td>: {{ $exam->subject->name ?? '-' }}</td>
                <td><strong>Status</strong></td>
                <td>:
                    @if ($isPassed)
                        <span class="badge badge-success">LULUS</span>
                    @else
                        <span class="badge badge-danger">TIDAK LULUS</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td><strong>Total Soal</strong></td>
                <td>: PG: {{ collect($results)->where('is_pg', true)->count() }} | Essay:
                    {{ collect($results)->where('is_pg', false)->count() }}</td>
                <td><strong>Waktu Selesai</strong></td>
                <td>: {{ $session->finished_at->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Acak Urutan Soal</strong></td>
                <td>: {{ $exam->random_question_type ? 'Ya' : 'Tidak' }}</td>
                <td><strong>Acak Pilihan Jawaban</strong></td>
                <td>: {{ $exam->random_option_type ? 'Ya' : 'Tidak' }}</td>
            </tr>
        </table>
    </div>

    {{-- DAFTAR JAWABAN --}}
    <div class="container">
        @foreach ($results as $item)
            @php
                $statusClass = is_null($item['is_correct'])
                    ? 'card-pending'
                    : ($item['is_correct']
                        ? 'card-correct'
                        : 'card-wrong');
            @endphp

            <div class="question-card {{ $statusClass }}">
                <span class="question-number">SOAL #{{ $item['number'] }}</span>

                <div class="soal-content question-text">
                    {!! $item['question'] !!}
                </div>

                <div class="answer-section">
                    @if ($item['is_pg'])
                        @foreach ($item['options'] ?? [] as $key => $option)
                            @php
                                $isSelected = is_array($item['answer'])
                                    ? in_array($key, $item['answer'])
                                    : $item['answer'] === $key;
                                $optionClass = '';
                                if ($isSelected) {
                                    $optionClass = $item['is_correct']
                                        ? 'option-selected-correct'
                                        : 'option-selected-wrong';
                                }
                            @endphp
                            <div class="option-item {{ $optionClass }}">
                                <table style="width: 100%; border-collapse: collapse; border: none;">
                                    <tr>
                                        <!-- 1. KOLOM HURUF -->
                                        <td class="option-letter">
                                            {{ strtoupper($key) }}.
                                        </td>

                                        <!-- 2. KOLOM ISI JAWABAN (Rata Kiri) -->
                                        <td class="option-content-cell">
                                            <div class="soal-content">
                                                {!! $option !!}
                                            </div>
                                        </td>

                                        <!-- 3. KOLOM BADGE (Rata Kanan) -->
                                        <td class="badge-user-cell">
                                            @if ($isSelected)
                                                <span class="badge-user-pdf">
                                                    (Jawaban Peserta)
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @endforeach
                    @else
                        <div class="essay-answer">
                            <strong>Jawaban Peserta:</strong><br>
                            {!! $item['answer'] ?? '<em>Tidak ada jawaban</em>' !!}
                        </div>
                    @endif
                </div>

                <div class="score-footer">
                    Poin Diperoleh: {{ is_null($item['is_correct']) ? 'Pending' : $item['score'] }}
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s T') }} | Dokumen Sah Hasil Ujian Digital
    </div>
</body>

</html>
