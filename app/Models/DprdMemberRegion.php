<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DprdMemberRegion extends Model
{
    use HasFactory;

    protected $table = 'dprd_member_regions';

    protected $fillable = [
        'dprd_member_id',
        'dprd_region_id',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(DprdRegion::class, 'dprd_region_id');
    }
}
