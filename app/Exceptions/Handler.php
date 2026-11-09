<?php

namespace App\Exceptions;

use App\Support\ArchiveFileStorage;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        /*
         * BODY terlalu besar melewati post_max_size: PHP mengosongkan
         * seluruh input sebelum Laravel menerima request, sehingga pesan
         * informatif ini ditampilkan alih-alih error 413 yang teknis.
         */
        $this->renderable(function (PostTooLargeException $e, $request) {
            $maxMb = ArchiveFileStorage::maxUploadMegabytes();
            $message = "Ukuran berkas terlalu besar dan melebihi batas maksimal unggahan server ({$maxMb} MB). Silakan pilih berkas yang lebih kecil.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 413);
            }

            return back()
                ->withInput()
                ->with('error', $message)
                ->withErrors([
                    'file' => $message,
                    'lampiran' => $message,
                ]);
        });
    }
}
