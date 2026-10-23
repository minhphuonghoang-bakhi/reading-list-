# 1. Goal

Build a REST API for a personal reading list.

- Users can register, log in, and manage their own books. 

- Each book has a title, description, reading status, and optional tags.


# 2. Technology Stack

- **PHP 8.5** for the backend, without any framework.
- **MySQL 8.4** for database.
- **Nginx and Docker Compose** for the provided application environment.
- **Composer** for dependency management and PSR-4 autoloading.
- **PDO** with prepared statements for database access.

# 3. Architecture

I plan to use a simple MVC architecture with a front controller.

The request flow will be:

`Nginx → index.php → Router → Authentication → Controller → Service → Repository → MySQL`

The main components will be:

- **Http:** Request and JSON response handling, routing, and HTTP status codes.
- **Controller:** Handles incoming requests, calls the appropriate services, and returns responses.
- **Service:** Contains business logic, authentication, and validation.
- **Repository:** Encapsulates database queries and persistence logic.
- **Model:** Represents the main application entities, such as users and books.
- **Configuration:** Loads environment variables and initializes application dependencies.

# 4. API Endpoints

## Authentication

- `POST /register` : Register a user and return an authentication token.
- `POST /login` : Validate credentials and return a token.
- `POST /logout` : Invalidate the current token.

## Books

All book endpoints require authentication.

- `POST /books` : Create a book with the default status `unread`.
- `GET /books` : List the authenticated user's books, with pagination and optional tag filtering.
- `PUT /books/{id}` : Update a book's title, description, and tags.
- `PATCH /books/{id}/status` : Update a book's reading status.
- `DELETE /books/{id}` : Delete a book.

Responses will follow the specification, using JSON and appropriate HTTP status codes.

# 5. Database Architecture

I plan to use the following tables:

- **users:** User information, unique email address, and password hash.
- **api_tokens:** Hashed authentication tokens linked to users.
- **books:** Book information, reading status, and owner.
- **tags:** Tags associated with individual users.
- **book_tag:** Many-to-many relationship between books and tags.

Books and tags will be scoped to their owners. Foreign keys, uniqueness constraints, and indexes will be used where appropriate.

# 6. Security 

- Hash passwords using `password_hash()` and verify them using `password_verify()`.
- Generate unpredictable authentication tokens using `random_bytes()` and store only their hashes.
- Require a valid Bearer token for all protected endpoints.
- Invalidate tokens on logout.
- Enforce ownership checks for every book operation.
- Use PDO prepared statements for database queries.
- Validate request bodies, email addresses, required fields, tags, and allowed reading statuses.
- Return consistent JSON error responses without exposing SQL details, stack traces, or sensitive information.

Missing or invalid authentication will return `401`. Operations on books owned by another user will return `403`.

# 7. Implementation Order

1. Set up the project structure, Composer, configuration, and routing.

2. Create the database tables and implement registration, login, and logout.

3. Implement the book endpoints (create, list, update, delete, and change status).

4. Add tags, pagination, and filtering.

5. Test the API, especially authentication, validation, and user ownership.

Implement optional bonus features if time permits.

# 8. Testing and Submission

I will test the API using an HTTP client such as Postman or curl, including successful requests and failure cases.

Special attention will be given to authentication, data validation, and ensuring that users cannot access or modify other users' books.

The final submission will include the application source code, database schema and required data export, together with README documentation describing setup, usage, and relevant design decisions.

# 9. Todos (Kanban Style)

## To do
- [ ] Books: create + list (pagination, tag filter)
- [ ] Books: update, status, delete
- [ ] Security review
- [ ] Tests, README, database dump

## Doing
- [ ] AuthMiddleware + Logout

## Done
- [x] Setup (Docker, Composer, .env)
- [x] PLANNING.md
- [x] HTTP foundation
- [x] Register
- [x] Login


## Later (as bonus)
- [ ] Search books by author
- [ ] Rate limiting
- [ ] Unit tests for services
