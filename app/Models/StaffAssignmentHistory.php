<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAssignmentHistory extends Model
{
    protected $table = 'dprd_staff_assignment_histories';

    protected $fillable = [
        'dprd_staff_id',
        'dprd_member_id',
        'previous_member_id',
        'assigned_by',
        'source',
        'catatan',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(DprdStaff::class, 'dprd_staff_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'dprd_member_id');
    }

    public function previousMember(): BelongsTo
    {
        return $this->belongsTo(DprdMember::class, 'previous_member_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
