<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RdpDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rdp_documents';

    public const CATEGORY_RISALAH = 'risalah';

    public const CATEGORY_NOTULA = 'notula';

    public const CATEGORY_UNDANGAN = 'undangan';

    public const CATEGORY_DAFTAR_HADIR = 'daftar_hadir';

    public const CATEGORY_MATERI = 'materi';

    public const CATEGORY_PENDUKUNG = 'pendukung';

    public const CATEGORY_LABELS = [
        self::CATEGORY_RISALAH => 'Risalah',
        self::CATEGORY_NOTULA => 'Notula',
        self::CATEGORY_UNDANGAN => 'Undangan',
        self::CATEGORY_DAFTAR_HADIR => 'Daftar Hadir',
        self::CATEGORY_MATERI => 'Materi Rapat',
        self::CATEGORY_PENDUKUNG => 'Dokumen Pendukung',
    ];

    protected $fillable = [
        'rdp_meeting_id',
        'title',
        'category',
        'document_date',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'file_size' => 'integer',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(RdpMeeting::class, 'rdp_meeting_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? ucfirst((string) $this->category);
    }

    public function getFileSizeLabelAttribute(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.').' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.').' KB';
        }

        return $bytes.' B';
    }
}
