import * as bootstrap from 'bootstrap';
import ApexCharts from 'apexcharts';

window.bootstrap = bootstrap;

/* --------------------------------------------------------------------------
   Sidebar behaviour
   -------------------------------------------------------------------------- */

const SIDEBAR_KEY = 'dprd-sidebar-collapsed';
const desktopQuery = window.matchMedia('(min-width: 992px)');

function isDesktop() {
    return desktopQuery.matches;
}

function setCollapsed(collapsed) {
    document.body.classList.toggle('sidebar-collapsed', collapsed);

    if (isDesktop()) {
        try {
            window.localStorage.setItem(SIDEBAR_KEY, collapsed ? '1' : '0');
        } catch (error) {
            /* localStorage can be unavailable in private contexts. */
        }
    }
}

function closeMobileSidebar() {
    document.body.classList.remove('sidebar-mobile-open');
}

function initSidebar() {
    let stored = null;

    try {
        stored = window.localStorage.getItem(SIDEBAR_KEY);
    } catch (error) {
        stored = null;
    }

    if (stored === '1' && isDesktop()) {
        document.body.classList.add('sidebar-collapsed');
    }

    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            if (isDesktop()) {
                setCollapsed(!document.body.classList.contains('sidebar-collapsed'));
                return;
            }

            document.body.classList.toggle('sidebar-mobile-open');
        });
    });

    document.querySelectorAll('[data-sidebar-close]').forEach((element) => {
        element.addEventListener('click', closeMobileSidebar);
    });

    document.querySelectorAll('.sidebar-link').forEach((link) => {
        link.addEventListener('click', () => {
            if (!isDesktop()) {
                closeMobileSidebar();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMobileSidebar();
        }
    });

    desktopQuery.addEventListener('change', (event) => {
        if (event.matches) {
            closeMobileSidebar();

            let collapsed = false;

            try {
                collapsed = window.localStorage.getItem(SIDEBAR_KEY) === '1';
            } catch (error) {
                collapsed = false;
            }

            document.body.classList.toggle('sidebar-collapsed', collapsed);
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    });
}

/* --------------------------------------------------------------------------
   Toast helper (frontend-only preview)
   -------------------------------------------------------------------------- */

function showToast(message, title = 'Mode pratinjau') {
    const container = document.getElementById('demoToastContainer');

    if (!container) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = 'toast demo-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('aria-atomic', 'true');

    const body = document.createElement('div');
    body.className = 'toast-body';

    const icon = document.createElement('span');
    icon.className = 'toast-icon';
    icon.innerHTML = '<i class="ri-information-line" aria-hidden="true"></i>';

    const content = document.createElement('div');
    content.className = 'flex-grow-1';

    const heading = document.createElement('strong');
    heading.className = 'toast-title';
    heading.textContent = title;

    const text = document.createElement('span');
    text.textContent = message;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'btn-close';
    close.setAttribute('data-bs-dismiss', 'toast');
    close.setAttribute('aria-label', 'Tutup notifikasi');

    content.appendChild(heading);
    content.appendChild(text);
    body.appendChild(icon);
    body.appendChild(content);
    body.appendChild(close);
    toast.appendChild(body);
    container.appendChild(toast);

    const instance = bootstrap.Toast.getOrCreateInstance(toast, { delay: 4600 });
    instance.show();

    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

window.showDemoToast = showToast;

function initDemoToasts() {
    document.querySelectorAll('[data-demo-toast]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            showToast(trigger.getAttribute('data-demo-toast') || 'Aksi ini belum terhubung ke backend.');
        });
    });

}

/* --------------------------------------------------------------------------
   Generic demo forms + confirmation modal
   -------------------------------------------------------------------------- */

function initDemoForms() {
    document.querySelectorAll('form[data-demo-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                return;
            }

            const message = form.getAttribute('data-demo-message')
                || 'Data tidak dikirim karena tahap ini masih berupa tampilan.';

            showToast(message);

            const modalElement = form.closest('.modal');

            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);

                if (modal) {
                    modal.hide();
                }
            }

            form.classList.remove('was-validated');
        });
    });

    const confirmElement = document.getElementById('confirmActionModal');

    if (confirmElement) {
        const confirmModal = bootstrap.Modal.getOrCreateInstance(confirmElement);
        const title = confirmElement.querySelector('[data-confirm-title-target]');
        const message = confirmElement.querySelector('[data-confirm-message-target]');
        const label = confirmElement.querySelector('[data-confirm-label-target]');
        const confirmButton = confirmElement.querySelector('[data-confirm-accept]');
        let pendingToast = '';
        let pendingForm = null;

        document.querySelectorAll('[data-confirm-action]').forEach((trigger) => {
            trigger.addEventListener('click', (event) => {
                event.preventDefault();

                if (title) {
                    title.textContent = trigger.getAttribute('data-confirm-title') || 'Konfirmasi tindakan';
                }

                if (message) {
                    message.textContent = trigger.getAttribute('data-confirm-message') || 'Tindakan ini hanya simulasi tampilan.';
                }

                if (label) {
                    label.textContent = trigger.getAttribute('data-confirm-label') || 'Ya, lanjutkan';
                }

                pendingToast = trigger.getAttribute('data-confirm-toast')
                    || 'Tindakan tidak dijalankan karena masih berupa tampilan.';

                const formSelector = trigger.getAttribute('data-confirm-form');
                pendingForm = formSelector ? document.querySelector(formSelector) : null;

                confirmModal.show();
            });
        });

        confirmElement.addEventListener('hidden.bs.modal', () => {
            pendingForm = null;
            pendingToast = '';
        });

        if (confirmButton) {
            confirmButton.addEventListener('click', () => {
                const form = pendingForm;
                const toast = pendingToast;

                pendingForm = null;
                pendingToast = '';
                confirmModal.hide();

                if (form) {
                    form.submit();

                    return;
                }

                if (toast) {
                    showToast(toast);
                }
            });
        }
    }
}

/* --------------------------------------------------------------------------
   Detail + edit modal fillers
   -------------------------------------------------------------------------- */

function toneForStatus(status) {
    const tones = {
        'Terverifikasi': 'success',
        'Aktif': 'success',
        'Selesai': 'success',
        'Menunggu Verifikasi': 'warning',
        'Menunggu Penugasan': 'warning',
        'Draf': 'neutral',
        'Baru': 'info',
        'Diproses': 'info',
        'Ditolak': 'danger',
    };

    return tones[status] || 'neutral';
}

function setStatusBadge(element, status) {
    if (!element) {
        return;
    }

    element.textContent = status;
    element.className = 'status-badge status-' + toneForStatus(status);
}

function initArchiveModals() {
    const formModal = document.getElementById('archiveFormModal');

    if (formModal) {
        formModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const isEdit = trigger && trigger.getAttribute('data-mode') === 'edit';
            const title = formModal.querySelector('[data-form-title]');
            const subtitle = formModal.querySelector('[data-form-subtitle]');
            const submit = formModal.querySelector('[data-form-submit]');
            const form = formModal.querySelector('form');

            if (title) {
                title.textContent = isEdit ? 'Ubah Arsip Dokumen' : 'Tambah Arsip Dokumen';
            }

            if (subtitle) {
                subtitle.textContent = isEdit
                    ? 'Perbarui metadata arsip. Perubahan belum tersimpan pada tahap ini.'
                    : 'Lengkapi metadata arsip. Form ini masih berupa tampilan.';
            }

            if (submit) {
                submit.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan Arsip';
            }

            if (form) {
                form.reset();
                form.classList.remove('was-validated');

                if (isEdit && trigger) {
                    ['nomor', 'nama', 'kategori', 'tanggal', 'anggota', 'status', 'keterangan'].forEach((field) => {
                        const input = form.querySelector('[name="' + field + '"]');

                        if (input) {
                            input.value = trigger.getAttribute('data-' + field) || '';
                        }
                    });
                }
            }
        });
    }

    const detailModal = document.getElementById('archiveDetailModal');

    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            const fields = [
                'nomor',
                'nama',
                'kategori',
                'tanggal',
                'jenis',
                'file-name',
                'file-size',
                'folder',
                'uploader',
                'uploaded-at',
                'anggota',
                'keterangan',
            ];

            fields.forEach((field) => {
                const value = trigger.getAttribute('data-' + field) || '—';

                detailModal.querySelectorAll('[data-detail-' + field + ']').forEach((target) => {
                    target.textContent = value;
                });
            });

            setStatusBadge(detailModal.querySelector('[data-detail-status]'), trigger.getAttribute('data-status') || 'Draf');
        });
    }
}

function initMemberModals() {
    const formModal = document.getElementById('memberFormModal');

    if (formModal) {
        formModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const isEdit = trigger && trigger.getAttribute('data-mode') === 'edit';
            const title = formModal.querySelector('[data-form-title]');
            const subtitle = formModal.querySelector('[data-form-subtitle]');
            const submit = formModal.querySelector('[data-form-submit]');
            const form = formModal.querySelector('form');

            if (title) {
                title.textContent = isEdit ? 'Ubah Data Anggota' : 'Tambah Anggota DPRD';
            }

            if (subtitle) {
                subtitle.textContent = isEdit
                    ? 'Perbarui data anggota. Perubahan belum tersimpan pada tahap ini.'
                    : 'Lengkapi data anggota. Form ini masih berupa tampilan.';
            }

            if (submit) {
                submit.textContent = isEdit ? 'Simpan Perubahan' : 'Simpan Anggota';
            }

            if (form) {
                form.reset();
                form.classList.remove('was-validated');

                if (isEdit && trigger) {
                    ['nama', 'jabatan', 'partai', 'dapil', 'komisi', 'staf'].forEach((field) => {
                        const input = form.querySelector('[name="' + field + '"]');

                        if (input) {
                            input.value = trigger.getAttribute('data-' + field) || '';
                        }
                    });
                }
            }
        });
    }

    const detailModal = document.getElementById('memberDetailModal');

    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            ['nama', 'jabatan', 'partai', 'dapil', 'komisi', 'staf', 'arsip', 'laporan'].forEach((field) => {
                const target = detailModal.querySelector('[data-detail-' + field + ']');

                if (target) {
                    target.textContent = trigger.getAttribute('data-' + field) || '—';
                }
            });

            const avatar = detailModal.querySelector('[data-detail-avatar]');
            const initials = trigger.getAttribute('data-initials') || 'DP';
            const tone = trigger.getAttribute('data-tone') || 'emerald';

            if (avatar) {
                avatar.textContent = initials;
                avatar.className = 'avatar avatar-xl avatar-' + tone;
            }
        });
    }
}

function initReportModals() {
    const detailModal = document.getElementById('reportDetailModal');

    if (detailModal) {
        detailModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            ['tiket', 'pelapor', 'kontak', 'kategori', 'tanggal', 'anggota', 'staf', 'ringkasan', 'isi'].forEach((field) => {
                const value = trigger.getAttribute('data-' + field) || '—';

                detailModal.querySelectorAll('[data-detail-' + field + ']').forEach((target) => {
                    target.textContent = value;
                });
            });

            const status = trigger.getAttribute('data-status') || 'Baru';
            setStatusBadge(detailModal.querySelector('[data-detail-status]'), status);

            const timeline = {
                received: 'is-done',
                verified: status === 'Baru' ? '' : (status === 'Menunggu Penugasan' ? 'is-active' : 'is-done'),
                followup: status === 'Diproses' ? 'is-active' : (status === 'Selesai' ? 'is-done' : (status === 'Ditolak' ? 'is-rejected' : '')),
                finished: status === 'Selesai' ? 'is-done' : '',
            };

            Object.entries(timeline).forEach(([step, className]) => {
                const element = detailModal.querySelector('[data-timeline-step="' + step + '"]');

                if (element) {
                    element.className = ('timeline-item ' + className).trim();
                }
            });
        });
    }

    const followUpModal = document.getElementById('reportFollowUpModal');

    if (followUpModal) {
        followUpModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            const ticket = trigger.getAttribute('data-tiket') || '';

            followUpModal.querySelectorAll('[data-followup-ticket]').forEach((target) => {
                target.textContent = ticket;
            });

            const form = followUpModal.querySelector('form');

            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }

            const ticketInput = followUpModal.querySelector('[name="tiket"]');

            if (ticketInput) {
                ticketInput.value = ticket;
            }

            const statusSelect = followUpModal.querySelector('[name="status"]');

            if (statusSelect) {
                statusSelect.value = trigger.getAttribute('data-status') || 'Baru';
            }

            const memberSelect = followUpModal.querySelector('[name="anggota"]');

            if (memberSelect) {
                memberSelect.value = trigger.getAttribute('data-anggota') || 'Belum ditugaskan';
            }

            const staffSelect = followUpModal.querySelector('[name="staf"]');

            if (staffSelect) {
                staffSelect.value = trigger.getAttribute('data-staf') || 'Belum ditugaskan';
            }
        });
    }
}

/* --------------------------------------------------------------------------
   Account reset + document upload modals
   -------------------------------------------------------------------------- */

function initAccountAndDocumentModals() {
    const resetModalElement = document.getElementById('resetPasswordModal');

    if (resetModalElement) {
        const resetModal = bootstrap.Modal.getOrCreateInstance(resetModalElement);
        const userIdInput = resetModalElement.querySelector('#resetPasswordUserId');
        const nameTargets = resetModalElement.querySelectorAll('[data-reset-account-name]');

        resetModalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;

            if (!trigger) {
                return;
            }

            const id = trigger.getAttribute('data-account-id') || '';
            const name = trigger.getAttribute('data-account-name') || 'akun terpilih';

            if (userIdInput) {
                userIdInput.value = id;
            }

            nameTargets.forEach((target) => {
                target.textContent = name;
            });
        });

        resetModalElement.addEventListener('hidden.bs.modal', () => {
            resetModalElement.querySelectorAll('input[type="password"]').forEach((input) => {
                input.value = '';
            });
        });

        if (resetModalElement.getAttribute('data-reset-open') === '1') {
            const id = resetModalElement.getAttribute('data-reset-account-id') || '';
            const name = resetModalElement.getAttribute('data-reset-account-name') || 'akun terpilih';

            if (userIdInput) {
                userIdInput.value = id;
            }

            nameTargets.forEach((target) => {
                target.textContent = name;
            });

            window.setTimeout(() => resetModal.show(), 120);
        }
    }

    const uploadModalElement = document.getElementById('documentUploadModal');

    if (uploadModalElement) {
        const uploadModal = bootstrap.Modal.getOrCreateInstance(uploadModalElement);

        uploadModalElement.addEventListener('show.bs.modal', (event) => {
            if (!event.relatedTarget) {
                return;
            }

            const form = uploadModalElement.querySelector('form');

            if (form) {
                form.reset();
                form.classList.remove('was-validated');
            }
        });

        if (uploadModalElement.getAttribute('data-upload-open') === '1') {
            window.setTimeout(() => uploadModal.show(), 120);
        }
    }
}

/* --------------------------------------------------------------------------
   Auto-submit filters + task toggles + auto-open modal
   -------------------------------------------------------------------------- */

function initPageHelpers() {
    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => {
            const form = field.closest('form');

            if (form) {
                form.submit();
            }
        });
    });

    document.querySelectorAll('[data-task-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            button.classList.toggle('is-done');
            const item = button.closest('.task-item');

            if (item) {
                item.classList.toggle('is-done');
            }

            const done = button.classList.contains('is-done');
            button.setAttribute('aria-pressed', done ? 'true' : 'false');
            showToast(done ? 'Tugas ditandai selesai pada tampilan. Status belum tersimpan.' : 'Tanda selesai dibatalkan pada tampilan.', 'Daftar tugas');
        });
    });

    const params = new URLSearchParams(window.location.search);
    const modalName = params.get('modal');

    if (modalName) {
        const map = {
            tambah: 'archiveFormModal',
            'tambah-arsip': 'archiveFormModal',
            'tambah-anggota': 'memberFormModal',
        };

        const modalElement = document.getElementById(map[modalName] || modalName);

        if (modalElement) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
            modal.show();

            params.delete('modal');
            const query = params.toString();
            const cleanUrl = window.location.pathname + (query ? '?' + query : '') + window.location.hash;
            window.history.replaceState({}, '', cleanUrl);
        }
    }
}

/* --------------------------------------------------------------------------
   Archive activity chart
   -------------------------------------------------------------------------- */

function initArchiveChart() {
    const element = document.getElementById('archiveActivityChart');
    const dataElement = document.getElementById('archiveActivityData');

    if (!element || !dataElement) {
        return;
    }

    let data;

    try {
        data = JSON.parse(dataElement.textContent);
    } catch (error) {
        return;
    }

    const chart = new ApexCharts(element, {
        chart: {
            type: 'area',
            height: 268,
            fontFamily: "'Plus Jakarta Sans', sans-serif",
            toolbar: { show: false },
            zoom: { enabled: false },
            animations: { enabled: true, speed: 550 },
        },
        series: [
            { name: 'Arsip masuk', data: data.series12.in },
            { name: 'Arsip keluar', data: data.series12.out },
        ],
        colors: ['#155043', '#c9a45c'],
        stroke: {
            curve: 'smooth',
            width: 2.4,
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 0.4,
                opacityFrom: 0.22,
                opacityTo: 0.015,
                stops: [0, 92, 100],
            },
        },
        dataLabels: { enabled: false },
        markers: {
            size: 0,
            strokeWidth: 2,
            hover: { size: 4 },
        },
        grid: {
            borderColor: '#eef1ed',
            strokeDashArray: 4,
            padding: { left: 4, right: 6, top: 0, bottom: 0 },
            xaxis: { lines: { show: false } },
            yaxis: { lines: { show: true } },
        },
        xaxis: {
            categories: data.labels12,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: '#93a09a',
                    fontSize: '9.5px',
                    fontWeight: 600,
                },
            },
            tooltip: { enabled: false },
        },
        yaxis: {
            labels: {
                style: {
                    colors: '#93a09a',
                    fontSize: '9.5px',
                    fontWeight: 600,
                },
                formatter: (value) => Math.round(value),
            },
        },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            x: { show: true },
            y: { formatter: (value) => value + ' dokumen' },
        },
        responsive: [
            {
                breakpoint: 576,
                options: {
                    chart: { height: 230 },
                    xaxis: {
                        labels: { rotate: -35, rotateAlways: true, style: { fontSize: '8.5px' } },
                    },
                },
            },
        ],
    });

    chart.render();

    document.querySelectorAll('[data-chart-range]').forEach((button) => {
        button.addEventListener('click', () => {
            const range = button.getAttribute('data-chart-range');
            const labels = range === '6' ? data.labels6 : data.labels12;
            const series = range === '6' ? data.series6 : data.series12;

            chart.updateOptions({ xaxis: { categories: labels } });
            chart.updateSeries([
                { name: 'Arsip masuk', data: series.in },
                { name: 'Arsip keluar', data: series.out },
            ]);

            document.querySelectorAll('[data-chart-range]').forEach((item) => {
                item.classList.toggle('is-active', item === button);
                item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
            });
        });
    });
}

/* --------------------------------------------------------------------------
   Boot
   -------------------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initDemoToasts();
    initDemoForms();
    initArchiveModals();
    initMemberModals();
    initReportModals();
    initAccountAndDocumentModals();
    initPageHelpers();
    initArchiveChart();

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        new bootstrap.Tooltip(element);
    });
});
