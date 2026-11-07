<?php

namespace App\Exceptions;

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
         * ini ditampilkan alih-alih error 413 yang teknis.
         */
        $this->renderable(function (PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()
                ->withInput()
                ->withErrors([
                    'lampiran' => 'Ukuran file terlalu besar. Maksimal ukuran lampiran adalah 20 MB.',
                ]);
        });
    }
}
