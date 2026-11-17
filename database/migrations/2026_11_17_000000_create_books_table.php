<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            // A book is meaningless without its owner, so deleting a user deletes their books.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('author');
            $table->string('status', 20)->default('to-read');
            $table->smallInteger('rating')->nullable();
            $table->timestamps();

            // Speeds up "my books, newest first, page by page".
            $table->index(['user_id', 'id']);
        });

        // The database enforces the rules too, not only our Form Requests.
        DB::statement("ALTER TABLE books ADD CONSTRAINT books_status_check CHECK (status IN ('to-read', 'reading', 'finished'))");
        DB::statement('ALTER TABLE books ADD CONSTRAINT books_rating_check CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)');
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
