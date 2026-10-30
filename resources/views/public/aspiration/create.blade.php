@extends('layouts.public')

@section('title', 'Sampaikan Aspirasi Anda | DPRD Kota Parepare')
@section('meta_description', 'Formulir resmi untuk menyampaikan aspirasi, masukan, atau laporan kepada DPRD Kota Parepare tanpa perlu membuat akun.')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0e3b31] text-white">
        <div class="hero-grid absolute inset-0"></div>
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[#c9a45c]/20 blur-3xl"></div>
        <div class="absolute -bottom-28 left-[-8%] h-72 w-72 rounded-full bg-emerald-300/10 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-5 pb-28 pt-12 sm:px-8 sm:pt-16 lg:pb-32">
            <nav class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-white/45" aria-label="Breadcrumb">
                <a href="{{ route('welcome') }}" class="transition hover:text-[#e6c47d]">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-[#e6c47d]">Sampaikan Aspirasi</span>
            </nav>

            <div class="mt-8 max-w-3xl">
                <div class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] px-4 py-2 text-[10px] font-bold uppercase tracking-[.18em] text-[#f0dfb8] backdrop-blur sm:text-[11px]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#e6c47d]"></span>
                    Formulir aspirasi masyarakat
                </div>

                <h1 class="mt-5 text-3xl font-extrabold leading-tight tracking-[-0.035em] sm:text-[44px]">
                    Sampaikan Aspirasi Anda
                </h1>

                <p class="mt-4 max-w-2xl text-sm leading-7 text-white/65 sm:text-base sm:leading-8">
                    Gunakan formulir ini untuk menyampaikan aspirasi, masukan, atau laporan kepada DPRD Kota Parepare.
                </p>
            </div>
        </div>
    </section>

    <section class="relative z-10 mx-auto -mt-16 max-w-7xl px-5 pb-24 sm:px-8">
        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(280px,.55fr)] lg:items-start">
            <div class="rounded-3xl border border-[#e7dfd0] bg-white p-6 shadow-[0_28px_80px_-45px_rgba(14,59,49,.5)] sm:p-8">
                @if ($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-4 text-sm leading-6 text-red-700" role="alert">
                        <div class="flex items-start gap-3">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v5m0 3h.01"/>
                            </svg>
                            <div>
                                <p class="font-extrabold">Periksa kembali isian Anda.</p>
                                <p class="mt-1 text-red-600">Ada {{ $errors->count() }} bagian yang perlu diperbaiki sebelum aspirasi dapat dikirim.</p>
                                <ul class="mt-2 list-disc space-y-1 pl-5 text-[13px] text-red-600">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('aspirasi.store') }}" enctype="multipart/form-data" class="space-y-7" novalidate>
                    @csrf

                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0e3b31] text-[11px] font-extrabold text-[#e6c47d]">01</span>
                            <div>
                                <h2 class="text-base font-extrabold tracking-tight text-[#123b32]">Identitas pelapor</h2>
                                <p class="mt-0.5 text-[11px] leading-5 text-[#75827d]">Data ini hanya digunakan untuk keperluan verifikasi dan tindak lanjut.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="pelapor_nama" class="block text-[12px] font-extrabold text-[#33443e]">
                                    Nama Lengkap <span class="text-red-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="pelapor_nama"
                                    name="pelapor_nama"
                                    value="{{ old('pelapor_nama') }}"
                                    required
                                    maxlength="150"
                                    autocomplete="name"
                                    placeholder="Contoh: Andi Saputra"
                                    aria-describedby="pelapor_nama_help @error('pelapor_nama') pelapor_nama_error @enderror"
                                    @class([
                                        'mt-2 w-full rounded-xl border bg-[#fbfaf6] px-4 py-3 text-sm text-[#16342c] outline-none transition placeholder:text-[#9aa49f] focus:ring-2',
                                        'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has('pelapor_nama'),
                                        'border-[#e2dacb] focus:border-[#0e3b31] focus:ring-[#0e3b31]/10' => ! $errors->has('pelapor_nama'),
                                    ])
                                >
                                <p id="pelapor_nama_help" class="mt-1.5 text-[11px] leading-5 text-[#8a948f]">Tuliskan nama lengkap sesuai identitas Anda.</p>
                                @error('pelapor_nama')
                                    <p id="pelapor_nama_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pelapor_kontak" class="block text-[12px] font-extrabold text-[#33443e]">
                                    Nomor Telepon atau Email <span class="text-red-600" aria-hidden="true">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="pelapor_kontak"
                                    name="pelapor_kontak"
                                    value="{{ old('pelapor_kontak') }}"
                                    required
                                    maxlength="150"
                                    autocomplete="email"
                                    placeholder="Contoh: 0812xxxxxxx atau nama@email.com"
                                    aria-describedby="pelapor_kontak_help @error('pelapor_kontak') pelapor_kontak_error @enderror"
                                    @class([
                                        'mt-2 w-full rounded-xl border bg-[#fbfaf6] px-4 py-3 text-sm text-[#16342c] outline-none transition placeholder:text-[#9aa49f] focus:ring-2',
                                        'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has('pelapor_kontak'),
                                        'border-[#e2dacb] focus:border-[#0e3b31] focus:ring-[#0e3b31]/10' => ! $errors->has('pelapor_kontak'),
                                    ])
                                >
                                <p id="pelapor_kontak_help" class="mt-1.5 text-[11px] leading-5 text-[#8a948f]">Kontak tidak ditampilkan kepada publik dan hanya dipakai jika diperlukan tindak lanjut.</p>
                                @error('pelapor_kontak')
                                    <p id="pelapor_kontak_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#f0ebe0] pt-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0e3b31] text-[11px] font-extrabold text-[#e6c47d]">02</span>
                            <div>
                                <h2 class="text-base font-extrabold tracking-tight text-[#123b32]">Wilayah dan anggota DPRD</h2>
                                <p class="mt-0.5 text-[11px] leading-5 text-[#75827d]">Pilih kecamatan/dapil terlebih dahulu, lalu pilih anggota yang mewakili wilayah tersebut.</p>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-5 md:grid-cols-2">
                            <div>
                                <label for="dprd_region_id" class="block text-[12px] font-extrabold text-[#33443e]">
                                    Kecamatan / Dapil <span class="text-red-600" aria-hidden="true">*</span>
                                </label>
                                <select
                                    id="dprd_region_id"
                                    name="dprd_region_id"
                                    required
                                    aria-describedby="dprd_region_id_help @error('dprd_region_id') dprd_region_id_error @enderror"
                                    @class([
                                        'mt-2 w-full rounded-xl border bg-[#fbfaf6] px-4 py-3 text-sm text-[#16342c] outline-none transition focus:ring-2',
                                        'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has('dprd_region_id'),
                                        'border-[#e2dacb] focus:border-[#0e3b31] focus:ring-[#0e3b31]/10' => ! $errors->has('dprd_region_id'),
                                    ])
                                >
                                    <option value="">-- Pilih Kecamatan / Dapil --</option>
                                    @foreach ($regions as $region)
                                        <option value="{{ $region->id }}" @selected((string) old('dprd_region_id') === (string) $region->id)>
                                            {{ $region->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p id="dprd_region_id_help" class="mt-1.5 text-[11px] leading-5 text-[#8a948f]">Wilayah ini menentukan anggota DPRD yang dapat Anda pilih.</p>
                                @error('dprd_region_id')
                                    <p id="dprd_region_id_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="dprd_member_id" class="block text-[12px] font-extrabold text-[#33443e]">
                                    Anggota DPRD <span class="text-red-600" aria-hidden="true">*</span>
                                </label>
                                <select
                                    id="dprd_member_id"
                                    name="dprd_member_id"
                                    required
                                    data-member-select
                                    aria-describedby="dprd_member_id_help @error('dprd_member_id') dprd_member_id_error @enderror"
                                    @class([
                                        'mt-2 w-full rounded-xl border bg-[#fbfaf6] px-4 py-3 text-sm text-[#16342c] outline-none transition focus:ring-2 disabled:cursor-not-allowed disabled:bg-[#f1eee7] disabled:text-[#9aa49f]',
                                        'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has('dprd_member_id'),
                                        'border-[#e2dacb] focus:border-[#0e3b31] focus:ring-[#0e3b31]/10' => ! $errors->has('dprd_member_id'),
                                    ])
                                    @disabled(! old('dprd_region_id'))
                                >
                                    <option value="">
                                        {{ old('dprd_region_id') ? '-- Pilih Anggota DPRD --' : 'Pilih kecamatan/dapil terlebih dahulu' }}
                                    </option>
                                    @foreach ($members as $member)
                                        <option value="{{ $member->id }}" @selected((string) old('dprd_member_id') === (string) $member->id)>
                                            {{ $member->nama_lengkap }}{{ $member->komisi ? ' — '.$member->komisi : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <p id="dprd_member_id_help" class="mt-1.5 text-[11px] leading-5 text-[#8a948f]">Hanya anggota aktif yang terhubung dengan wilayah terpilih yang ditampilkan.</p>
                                <p id="dprd_member_id_empty" class="mt-1.5 hidden text-[11px] font-semibold leading-5 text-[#a17f3e]" data-member-empty></p>
                                @error('dprd_member_id')
                                    <p id="dprd_member_id_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#f0ebe0] pt-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0e3b31] text-[11px] font-extrabold text-[#e6c47d]">03</span>
                            <div>
                                <h2 class="text-base font-extrabold tracking-tight text-[#123b32]">Isi aspirasi</h2>
                                <p class="mt-0.5 text-[11px] leading-5 text-[#75827d]">Jelaskan permasalahan secara jelas agar mudah diverifikasi dan ditindaklanjuti.</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="isi" class="block text-[12px] font-extrabold text-[#33443e]">
                                Isi Aspirasi <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <textarea
                                id="isi"
                                name="isi"
                                rows="8"
                                required
                                maxlength="5000"
                                placeholder="Jelaskan permasalahan, lokasi kejadian jika relevan, serta harapan tindak lanjut Anda."
                                aria-describedby="isi_help @error('isi') isi_error @enderror"
                                @class([
                                    'mt-2 w-full resize-y rounded-xl border bg-[#fbfaf6] px-4 py-3 text-sm leading-6 text-[#16342c] outline-none transition placeholder:text-[#9aa49f] focus:ring-2',
                                    'border-red-400 focus:border-red-500 focus:ring-red-100' => $errors->has('isi'),
                                    'border-[#e2dacb] focus:border-[#0e3b31] focus:ring-[#0e3b31]/10' => ! $errors->has('isi'),
                                ])
                            >{{ old('isi') }}</textarea>
                            <div class="mt-1.5 flex items-start justify-between gap-3">
                                <p id="isi_help" class="text-[11px] leading-5 text-[#8a948f]">Sertakan lokasi kejadian jika relevan dan harapan tindak lanjut. Maksimal 5.000 karakter.</p>
                                <span class="shrink-0 text-[10px] font-semibold text-[#a8b1ad]" data-character-count>0/5000</span>
                            </div>
                            @error('isi')
                                <p id="isi_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                            @enderror

                            <div class="mt-4 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] p-4">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[12px] font-extrabold text-[#123b32]">Rekomendasi komisi</p>
                                        <p class="mt-0.5 text-[11px] leading-5 text-[#75827d]">Berdasarkan aturan kata kunci berbobot. Bukan AI dan bukan keputusan final.</p>
                                    </div>
                                    <button type="button" data-analysis-run class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#d9cfbb] bg-white px-3.5 py-2 text-[11px] font-extrabold text-[#0e3b31] transition hover:border-[#0e3b31]">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M4 7h16M4 12h10M4 17h7"/>
                                        </svg>
                                        Analisis Komisi
                                    </button>
                                </div>

                                <div class="mt-3 hidden rounded-xl border border-[#e2dacb] bg-white p-3" data-analysis-panel>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#e6f0eb] px-2.5 py-1 text-[10px] font-extrabold text-[#155043]" data-analysis-status></span>
                                        <strong class="text-[12px] text-[#123b32]" data-analysis-commission></strong>
                                    </div>
                                    <p class="mt-2 text-[11px] leading-6 text-[#63716c]" data-analysis-reason></p>
                                    <div class="mt-2 flex flex-wrap gap-1.5" data-analysis-keywords></div>
                                </div>

                                <p class="mt-2 hidden text-[11px] font-semibold leading-5 text-red-600" data-analysis-error></p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#f0ebe0] pt-7">
                        <div class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#0e3b31] text-[11px] font-extrabold text-[#e6c47d]">04</span>
                            <div>
                                <h2 class="text-base font-extrabold tracking-tight text-[#123b32]">Lampiran pendukung</h2>
                                <p class="mt-0.5 text-[11px] leading-5 text-[#75827d]">Opsional. Unggah dokumen atau foto yang mendukung aspirasi Anda.</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="lampiran" class="block text-[12px] font-extrabold text-[#33443e]">Lampiran</label>
                            <input
                                type="file"
                                id="lampiran"
                                name="lampiran"
                                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                                aria-describedby="lampiran_help @error('lampiran') lampiran_error @enderror"
                                @class([
                                    'mt-2 block w-full cursor-pointer rounded-xl border border-dashed bg-[#fbfaf6] px-4 py-3 text-[12px] text-[#5d6b65] file:mr-3 file:rounded-lg file:border-0 file:bg-[#0e3b31] file:px-3 file:py-2 file:text-[11px] file:font-bold file:text-white hover:file:bg-[#082a23]',
                                    'border-red-400' => $errors->has('lampiran'),
                                    'border-[#d9cfbb]' => ! $errors->has('lampiran'),
                                ])
                            >
                            <p id="lampiran_help" class="mt-1.5 text-[11px] leading-5 text-[#8a948f]">PDF, JPG, JPEG, PNG, WEBP, DOC, atau DOCX. Ukuran maksimal 5 MB. Berkas disimpan secara privat.</p>
                            @error('lampiran')
                                <p id="lampiran_error" class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t border-[#f0ebe0] pt-7">
                        <label for="persetujuan" class="flex cursor-pointer items-start gap-3 rounded-2xl border border-[#e7dfd0] bg-[#fbf9f4] p-4">
                            <input
                                type="checkbox"
                                id="persetujuan"
                                name="persetujuan"
                                value="1"
                                required
                                @checked(old('persetujuan'))
                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#cfc4ae] text-[#0e3b31] focus:ring-[#0e3b31]"
                            >
                            <span class="text-[12px] leading-6 text-[#52605b]">
                                Saya setuju data yang saya sampaikan digunakan untuk keperluan verifikasi, penugasan, dan tindak lanjut aspirasi oleh DPRD Kota Parepare.
                                <span class="text-red-600" aria-hidden="true">*</span>
                            </span>
                        </label>
                        @error('persetujuan')
                            <p class="mt-1.5 text-[11px] font-semibold leading-5 text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-[#f0ebe0] pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('welcome') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#e2dacb] px-5 py-3 text-[13px] font-bold text-[#52605b] transition hover:border-[#0e3b31] hover:text-[#0e3b31]">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M19 12H5m6 6-6-6 6-6"/>
                            </svg>
                            Batal dan kembali
                        </a>

                        <button type="submit" class="group inline-flex items-center justify-center gap-2.5 rounded-xl bg-[#0e3b31] px-6 py-3.5 text-sm font-extrabold text-white shadow-[0_16px_36px_-18px_rgba(14,59,49,.8)] transition hover:-translate-y-0.5 hover:bg-[#082a23]">
                            Kirim Aspirasi
                            <svg class="h-4 w-4 transition group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <path d="M5 12h14m-6-6 6 6-6 6"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-[#e7dfd0] bg-[#fbf9f4] p-6">
                    <div class="gold-rule"></div>
                    <h2 class="mt-4 text-[15px] font-extrabold text-[#123b32]">Sebelum mengirim</h2>
                    <ol class="mt-4 space-y-3 text-[12px] leading-6 text-[#63716c]">
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0e3b31] text-[10px] font-extrabold text-[#e6c47d]">1</span>
                            <span>Pastikan nama dan kontak yang Anda tulis aktif.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0e3b31] text-[10px] font-extrabold text-[#e6c47d]">2</span>
                            <span>Pilih kecamatan/dapil, lalu anggota DPRD yang mewakili wilayah tersebut.</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#0e3b31] text-[10px] font-extrabold text-[#e6c47d]">3</span>
                            <span>Jelaskan kronologi dan harapan tindak lanjut secara ringkas.</span>
                        </li>
                    </ol>
                </div>

                <div class="rounded-3xl bg-[#0e3b31] p-6 text-white">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-[#e6c47d]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="10" rx="2"/>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                        </svg>
                    </div>
                    <h2 class="mt-4 text-[15px] font-extrabold">Data dijaga kerahasiaannya</h2>
                    <p class="mt-2 text-[12px] leading-6 text-white/60">
                        Data pelapor tidak ditampilkan kepada publik. Laporan hanya dapat diakses administrator dan staf pendamping yang ditugaskan.
                    </p>
                </div>

                <div class="rounded-3xl border border-[#e7dfd0] bg-white p-6">
                    <h2 class="text-[13px] font-extrabold text-[#123b32]">Setelah dikirim</h2>
                    <p class="mt-2 text-[12px] leading-6 text-[#63716c]">
                        Aspirasi akan masuk dengan status <strong class="font-extrabold text-[#a17f3e]">Menunggu Penugasan</strong> dan ditinjau oleh administrator sebelum ditindaklanjuti. Rekomendasi komisi tidak menugaskan staf secara otomatis.
                    </p>
                </div>
            </aside>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form[action="{{ route('aspirasi.store') }}"]');
            const regionSelect = document.getElementById('dprd_region_id');
            const memberSelect = document.getElementById('dprd_member_id');
            const memberEmpty = document.querySelector('[data-member-empty]');
            const textarea = document.getElementById('isi');
            const counter = document.querySelector('[data-character-count]');
            const analysisButton = document.querySelector('[data-analysis-run]');
            const analysisPanel = document.querySelector('[data-analysis-panel]');
            const analysisStatus = document.querySelector('[data-analysis-status]');
            const analysisCommission = document.querySelector('[data-analysis-commission]');
            const analysisReason = document.querySelector('[data-analysis-reason]');
            const analysisKeywords = document.querySelector('[data-analysis-keywords]');
            const analysisError = document.querySelector('[data-analysis-error]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            if (counter && textarea) {
                const updateCounter = function () {
                    counter.textContent = textarea.value.length.toLocaleString('id-ID') + '/5000';
                };

                textarea.addEventListener('input', updateCounter);
                updateCounter();
            }

            function setMemberPlaceholder(text) {
                if (!memberSelect) {
                    return;
                }

                const placeholder = memberSelect.querySelector('option[value=""]');

                if (placeholder) {
                    placeholder.textContent = text;
                }
            }

            function resetMemberSelect(text) {
                if (!memberSelect) {
                    return;
                }

                memberSelect.innerHTML = '';
                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = text;
                memberSelect.appendChild(placeholder);
                memberSelect.disabled = true;
                memberSelect.required = true;
            }

            async function loadMembers(regionId) {
                if (!memberSelect) {
                    return;
                }

                if (!regionId) {
                    resetMemberSelect('Pilih kecamatan/dapil terlebih dahulu');
                    if (memberEmpty) {
                        memberEmpty.classList.add('hidden');
                        memberEmpty.textContent = '';
                    }
                    return;
                }

                memberSelect.disabled = true;
                setMemberPlaceholder('Memuat anggota DPRD...');
                if (memberEmpty) {
                    memberEmpty.classList.add('hidden');
                }

                try {
                    const response = await fetch('{{ route('aspirasi.members') }}?dprd_region_id=' + encodeURIComponent(regionId), {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (!response.ok) {
                        throw new Error('Gagal memuat anggota.');
                    }

                    const data = await response.json();
                    memberSelect.innerHTML = '';
                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = data.members.length > 0
                        ? '-- Pilih Anggota DPRD --'
                        : 'Tidak ada anggota untuk wilayah ini';
                    memberSelect.appendChild(placeholder);

                    data.members.forEach(function (member) {
                        const option = document.createElement('option');
                        option.value = member.id;
                        option.textContent = member.name + (member.komisi ? ' — ' + member.komisi : '');
                        memberSelect.appendChild(option);
                    });

                    memberSelect.disabled = data.members.length === 0;

                    if (memberEmpty) {
                        memberEmpty.textContent = data.message || '';
                        memberEmpty.classList.toggle('hidden', !data.message);
                    }
                } catch (error) {
                    resetMemberSelect('Gagal memuat anggota, coba pilih ulang wilayah');
                    if (memberEmpty) {
                        memberEmpty.textContent = 'Daftar anggota belum dapat dimuat. Periksa koneksi Anda lalu pilih wilayah kembali.';
                        memberEmpty.classList.remove('hidden');
                    }
                }
            }

            if (regionSelect) {
                regionSelect.addEventListener('change', function () {
                    loadMembers(regionSelect.value);
                });

                if (regionSelect.value) {
                    memberSelect.disabled = false;
                }
            }

            if (form && memberSelect && regionSelect && !regionSelect.value) {
                resetMemberSelect('Pilih kecamatan/dapil terlebih dahulu');
            }

            async function runAnalysis() {
                if (!textarea || !analysisPanel || !analysisButton) {
                    return;
                }

                if (textarea.value.trim().length < 20) {
                    if (analysisError) {
                        analysisError.textContent = 'Tulis isi aspirasi minimal 20 karakter sebelum analisis.';
                        analysisError.classList.remove('hidden');
                    }
                    return;
                }

                analysisButton.disabled = true;
                analysisButton.textContent = 'Menganalisis...';
                if (analysisError) {
                    analysisError.classList.add('hidden');
                }

                try {
                    const response = await fetch('{{ route('aspirasi.analyze') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ isi: textarea.value }),
                    });

                    if (!response.ok) {
                        throw new Error('Analisis gagal.');
                    }

                    const data = await response.json();
                    analysisPanel.classList.remove('hidden');
                    analysisStatus.textContent = data.status_label || 'Perlu Ditinjau';
                    analysisCommission.textContent = data.komisi_nama || 'Belum ada rekomendasi komisi';
                    analysisReason.textContent = data.alasan || '';
                    analysisKeywords.innerHTML = '';

                    (data.keywords || []).forEach(function (term) {
                        const chip = document.createElement('span');
                        chip.className = 'rounded-full bg-[#f1ecdf] px-2.5 py-1 text-[10px] font-semibold text-[#7a6a48]';
                        chip.textContent = term;
                        analysisKeywords.appendChild(chip);
                    });
                } catch (error) {
                    if (analysisError) {
                        analysisError.textContent = 'Rekomendasi komisi belum dapat dihitung. Anda tetap dapat mengirim aspirasi.';
                        analysisError.classList.remove('hidden');
                    }
                } finally {
                    analysisButton.disabled = false;
                    analysisButton.textContent = 'Analisis Komisi';
                }
            }

            if (analysisButton) {
                analysisButton.addEventListener('click', runAnalysis);
            }

            let analysisTimer = null;

            if (textarea && analysisButton) {
                textarea.addEventListener('input', function () {
                    window.clearTimeout(analysisTimer);

                    if (textarea.value.trim().length < 20) {
                        return;
                    }

                    analysisTimer = window.setTimeout(runAnalysis, 1200);
                });
            }
        });
    </script>
@endpush
