<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Book */
class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Only these fields ever leave the API. user_id is deliberately absent.
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'status' => $this->status,
            'rating' => $this->rating,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
