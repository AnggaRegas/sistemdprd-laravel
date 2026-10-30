<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StaffPasswordResetRequest extends FormRequest
{
    /**
     * Error ditempatkan pada bag terpisah agar modal reset dapat dibuka
     * kembali tanpa mencampuri error form lain di halaman daftar akun.
     */
    protected $errorBag = 'resetPassword';

    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'akun',
            'password' => 'kata sandi baru',
        ];
    }
}
