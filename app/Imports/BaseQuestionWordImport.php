<?php

namespace App\Imports;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class BaseQuestionWordImport
{
    protected $subjectId;
    protected $categoryId;
    protected $basePath;
    protected $fileMap = [];
    protected $tempMediaPath;
    public array $importErrors = [];

    public function __construct($subjectId, $categoryId, $basePath = null)
    {
        $this->subjectId = $subjectId;
        $this->categoryId = $categoryId;
        $this->basePath = $basePath;
        $this->tempMediaPath = storage_path('app/temp_media_' . Str::random(10));
        $this->indexFiles();
    }

    private function indexFiles()
    {
        if (!$this->basePath || !is_dir($this->basePath))
            return;
        $files = File::allFiles($this->basePath);
        foreach ($files as $file) {
            $this->fileMap[strtolower($file->getFilename())] = $file->getRealPath();
        }
    }

    protected function getPandocPath()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $winPaths = [
                'pandoc.exe',
                'C:\Program Files\Pandoc\pandoc.exe',
                'C:\Program Files (x86)\Pandoc\pandoc.exe'
            ];
            foreach ($winPaths as $path) {
                $check = (str_contains($path, ':')) ? file_exists($path) : shell_exec("where pandoc");
                if ($check)
                    return str_contains($path, ' ') ? '"' . $path . '"' : $path;
            }
        }

        $unixPaths = [
            '/usr/local/bin/pandoc',   // Intel Mac / Linux
            '/opt/homebrew/bin/pandoc', // Apple Silicon (M1/M2/M3)
            '/usr/bin/pandoc',          // Ubuntu/Debian Standard
            '/bin/pandoc'
        ];

        foreach ($unixPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $path = trim(shell_exec('which pandoc'));
            if (!empty($path))
                return $path;
        }

        return 'pandoc';
    }

    public function convertDocxToMarkdown($filePath)
    {
        if (!file_exists($filePath))
            throw new Exception("File Docx tidak ditemukan.");

        $pandoc = $this->getPandocPath();

        if (!File::exists($this->tempMediaPath))
            File::makeDirectory($this->tempMediaPath, 0755, true);

        $tempFile = storage_path('app/temp_' . Str::random(10) . '.html');
        $command = sprintf(
            '%s %s -f docx -t html --mathjax --standalone --extract-media=%s -o %s 2>&1',
            // '%s %s -f docx -t html --standalone --extract-media=%s -o %s 2>&1',
            $pandoc,
            escapeshellarg($filePath),
            escapeshellarg($this->tempMediaPath),
            escapeshellarg($tempFile)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMsg = implode(" ", $output);
            throw new Exception("Pandoc Error ({$returnVar}): {$errorMsg}. Path: {$pandoc}");
        }

        if (!file_exists($tempFile)) {
            throw new Exception("Gagal membuat file temporary markdown.");
        }

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    protected function parseMarkdownTable($markdown)
    {
        $tableData = [];
        if (preg_match('/<table/i', $markdown)) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $content = mb_convert_encoding($markdown, 'HTML-ENTITIES', 'UTF-8');
            $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $mainTable = $xpath->query('//table')->item(0);

            if ($mainTable) {
                $rows = $xpath->query('.//tr', $mainTable);
                foreach ($rows as $row) {
                    $cells = $xpath->query('./td | ./th', $row);
                    $rowData = [];
                    foreach ($cells as $cell) {
                        $innerHtml = '';
                        foreach ($cell->childNodes as $child) {
                            $innerHtml .= $dom->saveHTML($child);
                        }
                        $rowData[] = $this->finalizeHtml($innerHtml);
                    }
                    if (!empty($rowData))
                        $tableData[] = $rowData;
                }
            }
        }
        return $tableData;
    }

    protected function convertHtmlMathToLatex($html)
    {
        if (empty($html))
            return "";

        // 1. Map simbol ke format LaTeX (Selalu aman)
        $symbols = [
            'π' => '\pi',
            '±' => '\pm',
            '√' => '\sqrt',
            '∞' => '\infty',
            '×' => '\times',
            '÷' => '\div',
            '≤' => '\le',
            '≥' => '\ge',
            '≠' => '\ne'
        ];
        $html = strtr($html, $symbols);

        // 2. Identifikasi apakah string ini SUDAH mengandung LaTeX (pembungkus $ atau \( atau \[)
        // Kita bersihkan dulu delimiter pandoc ke standar $ agar deteksi mudah
        $tempCheck = $this->cleanLatex($html);
        $isAlreadyLatex = str_contains($tempCheck, '$');

        // 3. Konversi <sup> dan <sub>
        $html = preg_replace_callback('/<(sup|sub)>(.*?)<\/\1>/i', function ($m) use ($isAlreadyLatex) {
            $tag = strtolower($m[1]);
            $content = strip_tags($m[2]);
            $latexNotation = ($tag === 'sup') ? "^{$content}" : "_{$content}";

            // JIKA sudah ada pembungkus LaTeX di string utama, JANGAN tambahkan $ lagi
            return $isAlreadyLatex ? $latexNotation : '$' . $latexNotation . '$';
        }, $html);

        // 4. Deteksi teks manual yang punya pangkat/bawah (misal: x^2) tapi bukan LaTeX
        // Kita hanya bungkus jika string utama benar-benar murni teks (bukan latex pandoc)
        if (!$isAlreadyLatex) {
            $html = preg_replace_callback('/([a-zA-Z0-9](\^|_)(\{[^}]+\}|[a-zA-Z0-9]+))/', function ($m) {
                return '$' . $m[1] . '$';
            }, $html);
        }

        return $html;
    }

    protected function finalizeHtml($html)
    {
        if (empty($html))
            return "";
        $html = html_entity_decode($html);
        $html = $this->cleanLatex($html);
        $html = $this->convertHtmlMathToLatex($html);

        $html = preg_replace('/<table[^>]*>.*?<\/table>/is', '', $html);
        $allowedTags = '<span><strong><em><u><s><ul><ol><li><p><br><i><b><mark><sub><sup><math><img><div>';
        return trim($this->cleanLatex(strip_tags($html, $allowedTags)));
    }

    protected function processHtmlImages($html, $questionId)
    {
        if (empty($html) || stripos($html, '<img') === false)
            return $html;

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            $sourcePath = null;

            if (file_exists($src)) {
                $sourcePath = $src;
            } elseif (file_exists('/' . $src)) {
                $sourcePath = '/' . $src;
            } else {
                $cleanSrc = ltrim($src, './');
                $testPath = $this->tempMediaPath . DIRECTORY_SEPARATOR . $cleanSrc;
                if (file_exists($testPath)) {
                    $sourcePath = $testPath;
                }
            }

            if ($sourcePath && file_exists($sourcePath)) {
                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION);
                $filename = "img_" . Str::random(10) . "." . $extension;

                $storageFolder = "questions/{$questionId}";
                $storagePath = "{$storageFolder}/{$filename}";

                Storage::disk('public')->put($storagePath, file_get_contents($sourcePath));

                $img->setAttribute('src', Storage::url($storagePath));

                $img->setAttribute('style', 'max-width: 50%; height: auto;');
                $img->setAttribute('class', 'rounded-lg shadow-sm my-2');
            } else {
                $img->parentNode->removeChild($img);
            }
        }

        $container = $dom->getElementsByTagName('div')->item(0);
        $result = '';
        if ($container) {
            foreach ($container->childNodes as $node) {
                $result .= $dom->saveHTML($node);
            }
        }

        return $result;
    }

    protected function cleanLatex($text)
    {
        if (empty($text))
            return "";

        // A. Standarisasi Delimiter Pandoc ke format LaTeX
        // \[ ... \] ke $$ ... $$
        $text = preg_replace('/\\\\\[\s*(.*?)\s*\\\\\]/s', '$$$$1$$', $text);
        // \( ... \) ke $ ... $
        $text = preg_replace('/\\\\\(\s*(.*?)\s*\\\\\)/s', '$$1$', $text);

        // B. Perbaikan Double/Triple Dollar yang tidak sengaja
        // Jika ada $$$ atau lebih, kembalikan ke $$ (Block Mode)
        $text = preg_replace('/\${3,}/', '$$', $text);

        return $text;
    }

    protected function checkAttachment($nomorSoal)
    {
        if (!$this->basePath)
            return null;

        if (empty($this->fileMap))
            return null;

        $extensions = [
            'image' => ['png', 'jpg', 'jpeg', 'gif'],
            'audio' => ['mp3', 'wav'],
            'video' => ['mp4', 'webm'],
        ];

        foreach ($extensions as $type => $exts) {
            foreach ($exts as $ext) {
                $searchName = strtolower("soal-{$nomorSoal}.{$ext}");

                if (isset($this->fileMap[$searchName])) {
                    $fullPath = $this->fileMap[$searchName];

                    return [
                        'path' => $fullPath,
                        'name' => basename($fullPath),
                        'size' => filesize($fullPath),
                        'type' => $type
                    ];
                }
            }
        }
        return null;
    }

    protected function addError($row, $no, $text, $reason)
    {
        $this->importErrors[] = [
            'row' => $row,
            'no' => $no,
            'question' => Str::limit($text, 50),
            'reason' => $reason
        ];
    }

    protected function cleanup()
    {
        if (File::exists($this->tempMediaPath))
            File::deleteDirectory($this->tempMediaPath);
    }
}
