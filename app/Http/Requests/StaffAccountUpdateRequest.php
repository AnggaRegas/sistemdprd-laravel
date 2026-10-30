<?php

namespace App\Http\Requests;

use App\Models\DprdMember;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffAccountUpdateRequest extends FormRequest
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
        /** @var User|null $account */
        $account = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique(User::class, 'username')->ignore($account?->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($account?->id),
            ],
            'dprd_member_id' => [
                Rule::requiredIf($account?->isStaff() ?? false),
                'nullable',
                'integer',
                Rule::exists('dprd_members', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at');
                }),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User|null $account */
            $account = $this->route('user');

            if (! $account) {
                return;
            }

            $memberId = $this->input('dprd_member_id');

            if ($account->isStaff() && $memberId) {
                $member = DprdMember::query()->find((int) $memberId);

                if (! $member) {
                    $validator->errors()->add('dprd_member_id', 'Anggota DPRD yang dipilih tidak ditemukan.');

                    return;
                }

                $memberChanged = (int) $memberId !== (int) $account->dprd_member_id;

                if ($memberChanged && ! $member->is_active) {
                    $validator->errors()->add(
                        'dprd_member_id',
                        'Anggota DPRD yang dipilih tidak aktif. Pilih anggota aktif lainnya.'
                    );
                }

                if ($this->boolean('is_active')) {
                    $alreadyAssigned = User::query()
                        ->where('role', User::ROLE_STAF_PENDAMPING)
                        ->where('dprd_member_id', (int) $memberId)
                        ->where('is_active', true)
                        ->where('id', '!=', $account->id)
                        ->exists();

                    if ($alreadyAssigned) {
                        $validator->errors()->add(
                            'dprd_member_id',
                            'Anggota DPRD ini sudah memiliki staf pendamping aktif lain.'
                        );
                    }
                }
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
