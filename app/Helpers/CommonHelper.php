<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (!function_exists('generate_exam_system_id')) {
    function generate_exam_system_id($userId, $examId)
    {
        return strtoupper(substr(md5($userId . $examId), 0, 12));
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
