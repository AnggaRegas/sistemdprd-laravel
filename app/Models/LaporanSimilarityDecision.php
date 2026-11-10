<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit keputusan Super Admin terhadap kelompok kemiripan.
 */
class LaporanSimilarityDecision extends Model
{
    use HasFactory;

    protected $table = 'laporan_similarity_decisions';

    public const ACTION_PILIH_UTAMA = 'pilih_utama';

    public const ACTION_ARSIP_TERKAIT = 'arsip_terkait';

    public const ACTION_TANDAI_DUPLIKAT = 'tandai_duplikat';

    public const ACTION_TETAP_TERPISAH = 'tetap_terpisah';

    public const ACTION_TUNDA = 'tunda';

    public const ACTION_BUBARKAN = 'bubarkan';

    public const ACTION_LABELS = [
        self::ACTION_PILIH_UTAMA => 'Pilih Aspirasi Utama',
        self::ACTION_ARSIP_TERKAIT => 'Simpan sebagai Arsip Terkait',
        self::ACTION_TANDAI_DUPLIKAT => 'Tandai Duplikat',
        self::ACTION_TETAP_TERPISAH => 'Tetap Diproses Terpisah',
        self::ACTION_TUNDA => 'Tunda Keputusan',
        self::ACTION_BUBARKAN => 'Bubarkan Kelompok',
    ];

    protected $fillable = [
        'laporan_similarity_group_id',
        'laporan_masyarakat_id',
        'user_id',
        'action',
        'previous_status',
        'new_status',
        'catatan',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(LaporanSimilarityGroup::class, 'laporan_similarity_group_id');
    }

    public function laporan(): BelongsTo
    {
        return $this->belongsTo(LaporanMasyarakat::class, 'laporan_masyarakat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? ucfirst((string) $this->action);
    }
}
