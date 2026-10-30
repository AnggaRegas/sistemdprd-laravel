<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffAccountStoreRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique(User::class, 'username'),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
            ],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'dprd_member_id' => [
                'required',
                'integer',
                Rule::exists('dprd_members', 'id')->where(function ($query) {
                    $query->where('is_active', true)->whereNull('deleted_at');
                }),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->has('dprd_member_id')) {
                return;
            }

            $memberId = (int) $this->input('dprd_member_id');

            $alreadyAssigned = User::query()
                ->where('role', User::ROLE_STAF_PENDAMPING)
                ->where('dprd_member_id', $memberId)
                ->where('is_active', true)
                ->exists();

            if ($alreadyAssigned) {
                $validator->errors()->add(
                    'dprd_member_id',
                    'Anggota DPRD ini sudah memiliki staf pendamping aktif. Nonaktifkan atau hapus staf lama terlebih dahulu.'
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'username' => 'username',
            'email' => 'email',
            'password' => 'kata sandi',
            'dprd_member_id' => 'anggota DPRD',
            'is_active' => 'status akun',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => trim((string) $this->input('username')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
