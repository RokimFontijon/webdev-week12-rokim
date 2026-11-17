<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    public const STATUSES = ['to-read', 'reading', 'finished'];

    // user_id is NOT here on purpose: a client can never choose the owner.
    protected $fillable = ['title', 'author', 'status', 'rating'];

    protected $attributes = [
        'status' => 'to-read',
        'rating' => null,
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
