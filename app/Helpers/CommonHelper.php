<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('generate_exam_system_id')) {
    function generate_exam_system_id($token)
    {
        $id = strtoupper(substr($token, 0, 12));
        return 'EXAM-' . substr($id, 0, 4) . '-' . substr($id, 4, 4) . '-' . substr($id, 8, 4);
    }
}

if (!function_exists('isProduction')) {
    function isProduction()
    {
        return app()->environment('production');
    }
}

if (!function_exists('normalizeUsername')) {
    /**
     * Mengubah username menjadi huruf kecil, menghapus spasi
     * dan menghapus karakter spesial (hanya menyisakan huruf dan angka).
     */
    function normalizeUsername(?string $username): string
    {
        if (!$username)
            return '';

        // 1. Convert ke lowercase
        $username = strtolower($username);

        // 2. Hapus semua karakter yang BUKAN huruf (a-z) atau angka (0-9)
        // Karakter selain itu (spasi, @, #, !, dll) akan dihapus
        return preg_replace('/[^a-z0-9]/', '', $username);
    }
}

if (!function_exists('detectFileType')) {
    function detectFileType($path)
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => 'image',
            in_array($ext, ['mp3', 'wav', 'ogg']) => 'audio',
            in_array($ext, ['mp4', 'mov', 'avi']) => 'video',
            default => 'file',
        };
    }
}


if (!function_exists('generateFilePath')) {

    function generateFilePath(string $folder, string $questionId, int $order, string $oldPath): string
    {
        $ext = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));

        $safeName = Str::slug(pathinfo($oldPath, PATHINFO_FILENAME));

        if (empty($safeName)) {
            $safeName = 'file';
        }
        return "{$folder}/{$questionId}/{$questionId}-{$order}.{$ext}";
    }
}

if (!function_exists('moveQuestionFile')) {

    function moveQuestionFile(string $oldPath, string $newPath, string $disk = 'public'): string
    {
        $oldPath = str_replace(['\\', '//'], '/', $oldPath);
        $newPath = str_replace(['\\', '//'], '/', $newPath);

        if (Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->makeDirectory(dirname($newPath));
            Storage::disk($disk)->move($oldPath, $newPath);
        }

        return $newPath;
    }
}

if (!function_exists('getDeviceInfo')) {

    function getDeviceInfo(): string
    {
        $userAgent = request()->userAgent();
        $os = "Unknown OS";
        $device = "Unknown Device";

        // Deteksi OS / Merk HP dari User Agent
        if (preg_match('/iphone/i', $userAgent)) {
            $os = "iPhone";
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = "iPad";
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = "Android";
            // Mencoba mengambil model HP dari string Android
            if (preg_match('/android\s+([a-zA-Z0-9\-\s]+);/i', $userAgent, $matches)) {
                $device = $matches[1];
            }
        } elseif (preg_match('/windows/i', $userAgent)) {
            $os = "Windows PC";
        } elseif (preg_match('/macintosh/i', $userAgent)) {
            $os = "MacBook/iMac";
        }

        $browser = collect(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'])
            ->first(fn($b) => str_contains($userAgent, $b)) ?? "Unknown Browser";

        return "{$os} ({$device}) - {$browser}";
    }
}


if (!function_exists('moveTempToPermanent')) {
    /**
     * Memindahkan file dari folder temporary ke folder permanen
     * dan mengupdate path di dalam konten teks (HTML).
     *
     * @param string $uploadToken Token unik folder temp
     * @param string $folderName Nama folder tujuan (misal: 'questions')
     * @param string $recordId ID dari record (untuk subfolder)
     * @param mixed $content Konten teks (String) atau Array (untuk Repeater) yang perlu diupdate path-nya
     * @param string $disk Nama disk storage
     * @return mixed Konten yang sudah diupdate path-nya
     */
    function moveTempToPermanent(string $uploadToken, string $folderName, $recordId, $content, string $disk = 'public')
    {
        $tempPath = "{$folderName}/temp/{$uploadToken}";
        $finalPath = "{$folderName}/{$recordId}";

        if (!Storage::disk($disk)->exists($tempPath)) {
            return $content;
        }

        // 1. Pastikan folder tujuan ada
        if (!Storage::disk($disk)->exists($finalPath)) {
            Storage::disk($disk)->makeDirectory($finalPath);
        }

        // 2. Pindahkan semua file fisik
        $files = Storage::disk($disk)->files($tempPath);
        foreach ($files as $file) {
            $fileName = basename($file);
            Storage::disk($disk)->move($file, "{$finalPath}/{$fileName}");
        }

        // 3. Update path di dalam konten (bisa string tunggal atau array repeater)
        $oldPathString = $tempPath;
        $newPathString = $finalPath;

        if (is_array($content)) {
            array_walk_recursive($content, function (&$item) use ($oldPathString, $newPathString) {
                if (is_string($item)) {
                    $item = str_replace($oldPathString, $newPathString, $item);
                }
            });
        } else if (is_string($content)) {
            $content = str_replace($oldPathString, $newPathString, $content);
        }

        // 4. Hapus folder temp yang sudah kosong
        Storage::disk($disk)->deleteDirectory($tempPath);

        return $content;
    }
}


if (!function_exists('format_exam_range')) {
    function format_exam_range($start, $end)
    {
        if (!$start || !$end)
            return '-';

        // Jika hari, bulan, dan tahun sama
        if ($start->isSameDay($end)) {
            return [
                'is_same_day' => true,
                'date' => $start->translatedFormat('d F Y'),
                'time' => $start->format('H:i') . ' — ' . $end->format('H:i T'),
            ];
        }

        // Jika beda hari
        return [
            'is_same_day' => false,
            'start' => $start->translatedFormat('d F Y, H:i'),
            'end' => $end->translatedFormat('d F Y, H:i T'),
        ];
    }
}

if (!function_exists('auth_api')) {
    function auth_api()
    {
        $user = Auth::guard('api')->user();
        if ($user) {
            $user->load(['student.classroom']);
        }
        return $user;
    }
}
