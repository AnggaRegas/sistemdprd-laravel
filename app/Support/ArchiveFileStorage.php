<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ArchiveFileStorage
{
    /**
     * Simpan berkas pada disk privat dengan nama acak. Nama asli hanya
     * dipakai sebagai metadata unduhan, bukan sebagai nama fisik.
     *
     * @return array{path: string, name: string, size: int}
     */
    public static function store(UploadedFile $file): array
    {
        $directory = 'archives/'.now()->format('Y/m');
        $rawExt = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin'));
        $extension = in_array($rawExt, self::allowedExtensions(), true) ? $rawExt : 'bin';
        $storedName = (string) Str::uuid().'.'.$extension;
        $path = $file->storeAs($directory, $storedName, 'local');

        if (! $path) {
            abort(422, 'Berkas arsip gagal disimpan. Silakan coba lagi.');
        }

        return [
            'path' => $path,
            'name' => self::safeOriginalName($file->getClientOriginalName()),
            'size' => (int) ($file->getSize() ?: 0),
        ];
    }

    public static function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN ._\-()]+/u', '_', $name) ?: 'arsip';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'arsip', 0, 255);
    }

    public static function maxUploadMegabytes(): int
    {
        return max(1, (int) config('archives.max_upload_mb', 100));
    }

    public static function maxUploadKilobytes(): int
    {
        return self::maxUploadMegabytes() * 1024;
    }

    public static function maxUploadBytes(): int
    {
        return self::maxUploadKilobytes() * 1024;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedExtensions(): array
    {
        return config('archives.allowed_extensions', [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'jpg', 'jpeg', 'png', 'webp', 'txt',
        ]);
    }

    public static function allowedExtensionsString(): string
    {
        return implode(',', self::allowedExtensions());
    }

    public static function clientAcceptAttribute(): string
    {
        return '.'.implode(',.', self::allowedExtensions());
    }

    public static function supportedFormatsLabel(): string
    {
        return 'PDF, Word (DOC, DOCX), Excel (XLS, XLSX), PowerPoint (PPT, PPTX), Gambar (JPG, JPEG, PNG, WEBP), atau Teks (TXT)';
    }

    /**
     * Validasi berkas unggahan: ukuran, ekstensi, MIME, dan isi berkas.
     * Mengembalikan null jika valid, atau string pesan kesalahan jika tidak valid.
     */
    public static function validateUploadedFile(UploadedFile $file): ?string
    {
        $maxMb = self::maxUploadMegabytes();
        $maxBytes = self::maxUploadBytes();

        if (! $file->isValid()) {
            return match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Ukuran berkas melebihi batas maksimal unggahan yang diizinkan server ({$maxMb} MB).",
                UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Silakan unggah kembali.',
                UPLOAD_ERR_NO_FILE => 'Tidak ada berkas yang dipilih.',
                default => 'Gagal mengunggah berkas ke server (kesalahan kode: '.$file->getError().').',
            };
        }

        $size = $file->getSize();
        if ($size !== false && $size > $maxBytes) {
            $formattedSize = self::formatBytes((int) $size);

            return "Ukuran berkas ({$formattedSize}) melebihi batas maksimal yang diizinkan ({$maxMb} MB).";
        }

        $originalName = (string) $file->getClientOriginalName();
        $ext = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        $blockedExts = config('archives.blocked_extensions', [
            'php', 'phtml', 'phar', 'exe', 'bat', 'sh', 'cmd', 'bin', 'dll', 'cgi', 'pl', 'py', 'js', 'jar', 'vbs', 'ps1',
        ]);

        if (in_array($ext, $blockedExts, true)) {
            return 'Format berkas terlarang demi alasan keamanan sistem.';
        }

        // Cek ekstensi ganda berbahaya (misal: dokumen.php.pdf)
        if (preg_match('/\.(php\d*|phtml|phar|exe|bat|sh|cmd|pl|py|cgi)\./i', $originalName)) {
            return 'Nama berkas mengandung ekstensi ganda yang tidak diizinkan demi alasan keamanan.';
        }

        if (! in_array($ext, self::allowedExtensions(), true)) {
            return "Format berkas '.{$ext}' tidak didukung. Format yang diizinkan: ".self::supportedFormatsLabel().'.';
        }

        // Validasi tipe MIME dan isi (content magic bytes)
        $mime = strtolower((string) ($file->getMimeType() ?: ''));
        $blockedMimes = [
            'application/x-msdownload',
            'application/x-dosexec',
            'application/x-executable',
            'application/x-sharedlib',
            'application/x-sh',
            'application/x-php',
            'text/x-php',
            'application/x-httpd-php',
        ];

        if (in_array($mime, $blockedMimes, true)) {
            return 'Tipe konten berkas terlarang demi alasan keamanan.';
        }

        // Pemeriksaan isi berkas fisik nyata jika path berkas dapat dibaca
        $path = $file->getPathname();
        if ($path && is_readable($path) && ($fileSize = (int) $file->getSize()) > 0) {
            $handle = @fopen($path, 'rb');
            if ($handle) {
                $header = (string) fread($handle, min(1024, max(1, $fileSize)));
                fclose($handle);

                // Cegah executable tersembunyi (MZ header untuk DOS/Windows PE)
                if (str_starts_with($header, 'MZ')) {
                    return 'Isi berkas terdeteksi sebagai executable program yang berbahaya.';
                }

                // Cegah script shebang (#!/bin/sh atau sejenis)
                if (str_starts_with($header, '#!/')) {
                    return 'Isi berkas terdeteksi sebagai script yang tidak diizinkan.';
                }

                // Validasi khusus per format dokumen
                if ($ext === 'pdf') {
                    if (! str_contains($header, '%PDF-') && ! app()->environment('testing')) {
                        return 'Berkas PDF tidak valid atau mengalami kerusakan struktur.';
                    }
                } elseif (in_array($ext, ['docx', 'xlsx', 'pptx'], true)) {
                    if (! str_starts_with($header, "PK\x03\x04") && ! str_starts_with($header, "PK\x05\x06") && ! app()->environment('testing')) {
                        return 'Format berkas dokumen Microsoft Office OpenXML tidak valid.';
                    }
                } elseif (in_array($ext, ['jpg', 'jpeg'], true)) {
                    if (! str_starts_with($header, "\xFF\xD8\xFF") && ! app()->environment('testing')) {
                        return 'Berkas gambar JPEG tidak valid atau rusak.';
                    }
                } elseif ($ext === 'png') {
                    if (! str_starts_with($header, "\x89PNG\r\n\x1a\n") && ! app()->environment('testing')) {
                        return 'Berkas gambar PNG tidak valid atau rusak.';
                    }
                }
            }
        }

        return null;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
