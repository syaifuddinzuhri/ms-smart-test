<?php

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
