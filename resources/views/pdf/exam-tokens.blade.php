<!DOCTYPE html>
<html>

<head>
    <title>Token Ujian {{ $exam->title }}</title>
    <style>
        body {
            font-family: sans-serif;
            color: #333;
            line-height: 1.4;
        }

        .header {
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .exam-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .exam-desc {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }

        .container {
            width: 100%;
        }

        .column {
            width: 48%;
            float: left;
        }

        .column-right {
            float: right;
        }

        .type-header {
            background: #f4f4f4;
            padding: 8px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 10px;
            text-align: center;
        }

        th {
            background-color: #fafafa;
        }

        .token-font {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
            color: #1a56db;
        }

        .status-used {
            color: #b1b1b1;
            text-decoration: line-through;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 9px;
            color: #aaa;
            text-align: center;
        }

        .rules-container {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #4b5563;
            padding: 10px;
            margin-bottom: 20px;
        }

        .rules-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            text-decoration: underline;
        }

        .rules-text {
            font-size: 9px;
            color: #374151;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .rules-text li {
            margin-bottom: 3px;
        }

        .line-through {
            text-decoration: line-through;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                {{-- KOLOM LOGO --}}
                @if ($logo)
                    <td style="width: 65px; border: none; vertical-align: middle; padding-right: 15px;">
                        <img src="{{ $logo }}" style="width: 60px; height: auto; display: block;">
                    </td>
                @endif

                {{-- KOLOM JUDUL --}}
                <td style="border: none; vertical-align: middle; text-align: left;">
                    <div class="exam-title">{{ $exam->title }}</div>
                    <div style="font-size: 10px; color: #666; text-transform: uppercase; letter-spacing: 1px;">
                        MS SMART TEST
                    </div>
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
                        <strong>Tanggal:</strong> {{ $exam->start_time->format('d/m/Y H:i') }} <br>
                        <strong>Durasi:</strong> {{ $exam->duration }} Menit | <strong>Filter:</strong>
                        {{ $filterName }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="rules-container">
        <div class="rules-title">KETENTUAN PENGGUNAAN TOKEN:</div>
        <ul class="rules-text">
            <li>• <strong>TOKEN AKSES (HIJAU):</strong> Digunakan peserta untuk masuk ke ujian pertama kali. Dapat
                digunakan oleh <strong> banyak peserta</strong> sekaligus selama masa berlaku aktif.</li>
            <li>• <strong>TOKEN UNLOCK (MERAH):</strong> Digunakan khusus untuk peserta yang terblokir (keluar
                tab/aplikasi). Bersifat <strong> SEKALI PAKAI (Single Use)</strong>. Begitu digunakan oleh satu orang,
                kode tersebut otomatis hangus.</li>
            <li>• Token yang sudah dicoret (<s>ABCDEF</s>) menandakan kode tersebut sudah pernah digunakan dan tidak
                bisa dipakai lagi.</li>
            <li>• <strong>SINKRONISASI DATA:</strong> Jika ditemukan ketidaksinkronan data token dengan waktu server
                atau kode tidak dapat digunakan, harap hubungi Admin/Proktor untuk melakukan <strong>Export PDF
                    kembali</strong> guna mendapatkan daftar token terbaru.</li>
        </ul>
    </div>

    <div class="container">
        <!-- KOLOM KIRI: ACCESS -->
        <div class="column">
            <div class="type-header" style="border-left: 4px solid #10b981;">TOKEN AKSES (LOGIN)</div>
            <table>
                <thead>
                    <tr>
                        <th>KODE TOKEN</th>
                        <th>BERAKHIR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens->where('type', \App\Enums\ExamTokenType::ACCESS) as $t)
                        @php
                            $isExpired = \Carbon\Carbon::now()->greaterThan($t->expired_at);
                        @endphp
                        <tr>
                            <td class="token-font {{ $isExpired ? 'status-used' : '' }}">{{ $t->token }}</td>
                            <td>{{ $t->expired_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                Tidak ada token
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- KOLOM KANAN: RELOGIN -->
        <div class="column column-right">
            <div class="type-header" style="border-left: 4px solid #ef4444;">TOKEN UNLOCK (RELOGIN)</div>
            <table>
                <thead>
                    <tr>
                        <th>KODE TOKEN</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens->where('type', \App\Enums\ExamTokenType::RELOGIN) as $t)
                        @php
                            $isExpired = \Carbon\Carbon::now()->greaterThan($t->expired_at);
                        @endphp
                        <tr>
                            <td class="token-font {{ $t->used_at || $isExpired ? 'status-used' : '' }}">{{ $t->token }}</td>
                            <td>{{ $t->used_at || $isExpired ? 'Dipakai' : 'Tersedia' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">
                                Tidak ada token
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i:s') }} | MS Smart Test
    </div>
</body>

</html>
