<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        // LIMIT/OFFSET happens in PostgreSQL; the response adds links + meta for paging.
        $books = $request->user()->books()->orderBy('id')->paginate(10);

        return BookResource::collection($books);
    }

    public function show(Request $request, int $book): BookResource
    {
        // Looking up through the user's own books means someone else's book is simply "not found" (404).
        return new BookResource($request->user()->books()->findOrFail($book));
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        // The owner is set by the relationship, never by the client.
        $book = $request->user()->books()->create($request->validated());

        return (new BookResource($book))->response()->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, int $book): BookResource
    {
        $model = $request->user()->books()->findOrFail($book);
        $model->update($request->validated());

        return new BookResource($model);
    }

    public function destroy(Request $request, int $book): JsonResponse
    {
        $request->user()->books()->findOrFail($book)->delete();

        return response()->json(['message' => 'Book deleted.']);
    }
}
