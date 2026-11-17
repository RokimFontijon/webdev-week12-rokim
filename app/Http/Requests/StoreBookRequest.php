<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // user_id is not validated or accepted here: the owner always comes from the signed-in user.
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(Book::STATUSES)],
            'rating' => ['nullable', 'integer', 'between:1,5'],
        ];
    }
}
