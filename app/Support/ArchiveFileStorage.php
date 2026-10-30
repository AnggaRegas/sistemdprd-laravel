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
        $extension = $file->extension() ?: $file->getClientOriginalExtension() ?: 'bin';
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
}
