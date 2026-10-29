<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A job sent to Content AI: the photos and note, and the draft article and captions made from them. */
class ContentAiJob extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'photos' => 'array',
        'social' => 'array',
        'checks' => 'array',
        'cost' => 'decimal:4',
    ];

    public function blog()
    {
        return $this->belongsTo(BlogDetail::class, 'blog_id');
    }

    public function service()
    {
        return $this->belongsTo(ServiceDetail::class, 'service_id');
    }
}
