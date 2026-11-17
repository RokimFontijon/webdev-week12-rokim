<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo accounts for local testing only. The password is hashed by the User model cast.
        $alice = User::create([
            'name' => 'Alice Reader',
            'email' => 'alice@example.com',
            'password' => 'password',
        ]);

        $bob = User::create([
            'name' => 'Bob Bookworm',
            'email' => 'bob@example.com',
            'password' => 'password',
        ]);

        // Created in this order, Alice owns book ids 1-4 and Bob owns 5-8.
        $alice->books()->createMany([
            ['title' => 'Clean Code', 'author' => 'Robert C. Martin', 'status' => 'finished', 'rating' => 4],
            ['title' => 'The Pragmatic Programmer', 'author' => 'Andrew Hunt', 'status' => 'reading', 'rating' => null],
            ['title' => 'Designing Data-Intensive Applications', 'author' => 'Martin Kleppmann', 'status' => 'to-read', 'rating' => null],
            ['title' => 'Refactoring', 'author' => 'Martin Fowler', 'status' => 'to-read', 'rating' => null],
        ]);

        $bob->books()->createMany([
            ['title' => 'Dune', 'author' => 'Frank Herbert', 'status' => 'finished', 'rating' => 5],
            ['title' => 'Project Hail Mary', 'author' => 'Andy Weir', 'status' => 'reading', 'rating' => null],
            ['title' => 'Neuromancer', 'author' => 'William Gibson', 'status' => 'to-read', 'rating' => null],
            ['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'status' => 'finished', 'rating' => 3],
        ]);
    }
}
