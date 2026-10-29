# Reading List API

A REST API for a personal reading list, written in plain PHP 8.5 without a framework or ORM.
Users can register, log in and manage their own books with a reading status and user-scoped tags.

I built it to practise REST API design, security and a clean layered architecture from scratch.
The planning and the Kanban board are in [`PLANNING.md`](PLANNING.md).

## Quick start

```bash
# 1. Create the environment file
cp app/.env.example app/.env

# 2. Start PHP, Nginx and MySQL
docker compose up -d

# 3. Install Composer dependencies (autoloader, PHPUnit)
docker compose exec -w /var/www/html/app php composer install

# 4. Import the database (schema + demo data)
docker compose exec -T mysql mysql -u challenge -pchallenge challenge < database/data.sql
```

On Windows PowerShell, `<` is not supported. Use this for step 4:

```powershell
cmd /c "docker compose exec -T mysql mysql -u challenge -pchallenge challenge < database\data.sql"
```

The API runs at **http://localhost:8080**. `GET /health` returns `{"status": "ok"}`.
To start with an empty database instead, run `database/schema.sql`.

### Demo accounts

| Name | Email | Password | Data |
|---|---|---|---|
| John Doe | `john@example.com` | `password123` | 6 books with tags |
| Jane Doe | `jane@example.com` | `password123` | no books (useful to test isolation and 403) |

### Trying it out

[`http/requests.http`](http/requests.http) contains the requests I used for manual testing,
including the failure cases (400, 401, 403, 404). In PhpStorm, run the **Login** requests first:
they store the tokens in `{{token}}` and `{{token2}}`, so no real token is ever written into the file.

### Running the tests

```bash
docker compose exec -w /var/www/html/app php vendor/bin/phpunit tests
```

The unit tests cover the validators: input cleaning and every rule that rejects invalid input with `400`.

## Endpoints

All endpoints except `/register` and `/login` require `Authorization: Bearer <token>`.

| Method | Path | Success | Errors |
|---|---|---|---|
| `POST` | `/register` | `201` `{token}` | `400` |
| `POST` | `/login` | `200` `{token}` | `400`, `401` |
| `POST` | `/logout` | `204` | `401` |
| `POST` | `/books` | `201` book | `400`, `401` |
| `GET` | `/books?page=1&limit=10&tag=coding` | `200` `{data, page, limit, total}` | `400`, `401` |
| `PUT` | `/books/{id}` | `200` book | `400`, `401`, `403`, `404` |
| `PATCH` | `/books/{id}/status` | `200` book | `400`, `401`, `403`, `404` |
| `DELETE` | `/books/{id}` | `204` | `401`, `403`, `404` |

Errors always have the form `{"error": "<message>"}`. Unexpected errors return a generic `500`;
details are written to the PHP log only (`docker compose logs php`).

## Architecture

```
Nginx → index.php → Router → Controller → Validator → Service → Repository → MySQL
```

| Layer | Responsibility |
|---|---|
| `Http` | `Request` (wraps PHP globals), `Response` (JSON), `Router` (`{id}` placeholders, 404/405), `HttpException` |
| `Controller` | Translates between HTTP and the application: authenticate, validate, call the service, build the response |
| `Validation` | Checks the shape of client input and returns cleaned data, or throws `400` |
| `Service` | Business rules: password hashing, credential checks, ownership (`403`/`404`), transactions |
| `Repository` | SQL only, with PDO prepared statements; maps rows to objects |
| `Model` | `Book`, an immutable object (`readonly`) whose `toArray()` defines what the client sees |

```
app/
├── index.php            front controller: error handling, wiring (manual DI), routes
├── src/
│   ├── Controller/      AuthController, BookController
│   ├── Http/            Request, Response, Router, HttpException
│   ├── Model/           Book
│   ├── Repository/      UserRepository, TokenRepository, BookRepository, TagRepository
│   ├── Service/         AuthService, BookService, Authenticator
│   ├── Support/         Database (PDO connection)
│   └── Validation/      AuthValidator, BookValidator
└── tests/               unit tests for the validators
database/                schema.sql (hand-written), data.sql (dump with demo data)
http/requests.http       manual API tests
```

**Dependency injection** is done by hand in `index.php`: every object receives what it needs
through its constructor, and one PDO connection is shared by all repositories.

## Design decisions

**Authentication**
- Tokens are 32 random bytes, sent to the client once. The database stores only their SHA-256
  hash, so a database leak exposes no usable tokens. SHA-256 (not bcrypt) is enough because the
  token is already random, and it allows a fast lookup through a unique index.
- Every login creates a new token (one per session/device); logout deletes exactly that token.
  I chose database tokens over JWT because a JWT cannot be revoked before it expires.
- Login uses the same error message and the same response time for an unknown email and a wrong
  password, so registered emails cannot be discovered.
- `Authenticator` turns a Bearer token into a user id or throws `401`; every book action calls it first.

**Books and tags**
- Tags live in a `tags` table, scoped by `UNIQUE (user_id, name)`, and are linked through
  `book_tag`. Names are trimmed, lowercased and de-duplicated.
- `UNIQUE (user_id, title)` prevents duplicate books, for example from a double submit.
  Different users can have the same book.
- Creating or updating a book together with its tags runs in one transaction.
- `BookService::findOwned()` is the single place that returns `404` (book does not exist) or
  `403` (book belongs to another user). It runs before every update, status change and delete.
- `PUT` replaces the whole book; `PATCH /status` changes only the status.
- Listing loads the tags of a whole page in one `IN (...)` query, so a page always needs two
  queries regardless of its size (no N+1). `limit` is capped at 100.

**Code**
- `Book` hides `userId` in `toArray()`: the service needs it for the ownership check, the client does not.
- The router is a small custom class instead of a routing library, so the request flow stays easy to follow.
- Trade-off: services throw `HttpException` directly; a stricter design would use domain exceptions.

## Security

- Passwords hashed with bcrypt (`password_hash`), tokens stored only as hashes.
- PDO prepared statements everywhere, with real (not emulated) prepares.
- All input validated for type, length and allowed values.
- Users can only read and change their own books.
- Nginx routes every request to `index.php` and executes no other PHP file, so files like
  `.env` or `composer.json` can never be downloaded (I found and fixed this while testing).
- PHP errors are never displayed; warnings are converted to exceptions, logged, and returned as a generic `500`.

## What I would do next

- Token expiry and a "log out everywhere" endpoint.
- Keyset pagination (`WHERE id < ?`) instead of `OFFSET`, so deep pages stay fast.
- Random public ids (UUIDs), since sequential ids reveal how many books exist.
- Unit tests for the services: the repositories would need interfaces (they are `final`) so the
  services can receive fakes; constructor injection already makes this possible.
- Rate limiting on `/login` and `/register`.

## Credits

The Docker environment (PHP, Nginx, MySQL) started from a provided template; I adapted the
Nginx configuration for security. All application code is my own.
