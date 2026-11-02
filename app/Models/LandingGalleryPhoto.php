<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingGalleryPhoto extends Model
{
    use HasFactory;

    protected $table = 'landing_gallery_photos';

    protected $fillable = [
        'landing_gallery_id',
        'image_path',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(LandingGallery::class, 'landing_gallery_id');
    }
}
