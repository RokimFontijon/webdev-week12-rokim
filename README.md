# Reading List API (Week 12)

A small token-protected JSON API built with Laravel, Sanctum and PostgreSQL. Each person keeps their own list of books they want to read, are reading, or have finished.

## Run it

```bash
composer install
cp .env.example .env            # then set the DB_* values for PostgreSQL
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve               # http://127.0.0.1:8000
```

`.env` needs:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=webdev_week12
DB_USERNAME=<your postgres user>
DB_PASSWORD=<your postgres password>
```

The seeder creates two demo users (`alice@example.com` and `bob@example.com`, demo password `password`) with four books each. These are local test accounts only.

## Endpoints

All URLs start with `/api`. Send `Authorization: Bearer <token>` on everything except register and login.

| Method | URL | Token needed | Success | What it does |
|---|---|---|---|---|
| POST | `/register` | none | 201 | Create an account, returns a full-access token |
| POST | `/login` | none | 200 | Log in, returns a full-access token |
| POST | `/logout` | any token | 200 | Delete the token that made the request |
| POST | `/tokens/read-only` | `books:write` | 201 | Make a read-only token (can only look at books) |
| GET | `/books` | `books:read` | 200 | My books, 10 per page (`?page=2`) |
| GET | `/books/{id}` | `books:read` | 200 | One of my books |
| POST | `/books` | `books:write` | 201 | Add a book |
| PUT / PATCH | `/books/{id}` | `books:write` | 200 | Change one of my books |
| DELETE | `/books/{id}` | `books:write` | 200 | Delete one of my books |

Book fields: `title` (required), `author` (required), `status` (`to-read` default, `reading`, `finished`), `rating` (optional, 1 to 5).

### Responses

Success bodies use `{ "data": ... }`. Every error uses `{ "message": "..." }`, and validation errors add an `errors` object.

| Status | When |
|---|---|
| 401 | No token, bad token, logged-out token, or wrong email/password |
| 403 | Valid token that is not allowed to do this (read-only token writing) |
| 404 | Book does not exist **or belongs to someone else**, or unknown URL |
| 422 | Bad input (empty title, rating 9, unknown status, ...) |
| 429 | Too many register/login attempts |

Errors come back as JSON even when the request has no `Accept` header.

## Design notes

- **Database rules.** `books.status` has a `CHECK (status IN ('to-read','reading','finished'))` and `books.rating` has `CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)`, so bad data is rejected even if the code is bypassed.
- **Foreign key.** `books.user_id` references `users.id` with `ON DELETE CASCADE`: a book has no meaning without its owner, so deleting a user deletes their books.
- **Pagination.** `paginate(10)` runs `LIMIT 10 OFFSET ...` in PostgreSQL.
- **Ownership.** Books are always looked up through `$request->user()->books()`. The owner is set by the relationship, `user_id` is not in `$fillable`, and it is not in any validation rule or in `BookResource`.
- **Read-only token.** Tokens carry Sanctum abilities. Normal tokens have `books:read` and `books:write`; the read-only token has only `books:read`. Routes are guarded by `abilities:books:read` / `abilities:books:write`.

## Questions

**What is the difference between a 401 and a 403?**
A 401 means the API does not know who you are: the token is missing, invalid, expired or logged out, or the login was wrong. A 403 means the API knows exactly who you are, but this token is not allowed to do that, for example a read-only token trying to create a book. Fix a 401 by authenticating; a 403 cannot be fixed by logging in again, you need a token with more permission.

**What does the database store for a token?**
One row per token in `personal_access_tokens`: which model owns it (`tokenable_type` + `tokenable_id`, here a `User`), a `name`, the `abilities` as JSON, `last_used_at`, `expires_at` and timestamps. The `token` column holds only a SHA-256 hash of the secret part. The real token (`id|secret`) is shown to the client once, at login, and is never stored, so a leaked database does not leak usable tokens. Logging out deletes the row, which is why the same token then gets a 401. Passwords are stored separately, bcrypt-hashed, in `users`.

**Why use a Resource instead of returning the model?**
A Resource is the one place that decides what the outside world sees. Returning the model would send every column, including `user_id` and anything added later (or any hidden field someone forgets to hide). A Resource keeps the JSON shape stable even if the table changes, formats values (such as dates) consistently, hides `user_id`, and gives lists and single items the same shape with pagination links and meta added automatically.

## Inspecting tokens

```sql
SELECT id, tokenable_type, tokenable_id, name, abilities, last_used_at,
       left(token, 10) || '...' AS token_hash_start
FROM personal_access_tokens;
```
