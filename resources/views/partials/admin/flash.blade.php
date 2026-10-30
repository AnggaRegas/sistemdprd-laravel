@php
    $flashMessages = [
        'success' => ['tone' => 'success', 'icon' => 'ri-checkbox-circle-line'],
        'error' => ['tone' => 'danger', 'icon' => 'ri-error-warning-line'],
        'warning' => ['tone' => 'warning', 'icon' => 'ri-alert-line'],
        'info' => ['tone' => 'info', 'icon' => 'ri-information-line'],
    ];
@endphp

@foreach ($flashMessages as $key => $meta)
    @if (session()->has($key))
        <div class="alert alert-{{ $meta['tone'] }} alert-dismissible fade show app-alert" role="alert">
            <i class="{{ $meta['icon'] }}" aria-hidden="true"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! $errors->hasBag('resetPassword') && ! $errors->hasBag('documentUpload'))
    <div class="alert alert-danger alert-dismissible fade show app-alert" role="alert">
        <i class="ri-error-warning-line" aria-hidden="true"></i>
        <div>
            <strong>Periksa kembali isian Anda.</strong>
            Ada {{ $errors->count() }} isian yang perlu diperbaiki sebelum data dapat disimpan.
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup notifikasi"></button>
    </div>
@endif
