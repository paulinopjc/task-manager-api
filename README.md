# Task Manager API

A RESTful API for managing tasks with role-based access control, tagging, filtering, and cursor pagination.

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-blue) ![Laravel 13](https://img.shields.io/badge/Laravel-13-red) ![Tests](https://img.shields.io/badge/Tests-42%2B-green) ![License](https://img.shields.io/badge/license-MIT-lightgrey)

---

## Technical Overview

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Language | PHP 8.3 |
| Database (local) | MySQL 8.0 |
| Database (production) | PostgreSQL via Neon |
| Cache / Sessions | Redis 7 |
| Authentication | Laravel Sanctum v4 (token-based) |
| Local dev | Docker Compose |
| Deployment | Render |

---

## Why This Architecture?

Controllers in this project do one thing: validate the incoming request and delegate to a service. All business logic lives in `AuthService` and `TaskService`.

```
Request → FormRequest (validates) → Controller (delegates) → Service (logic) → Model
```

This matters for testing. A service method like `TaskService::list()` can be called directly in a unit test with a fake user and filter array — no HTTP request, no middleware, no database seeding ceremony. The HTTP layer and the business layer stay separate and independently verifiable.

---

## TDD Approach

**42+ automated tests** across 11 test files.

| Suite | What it covers |
|-------|---------------|
| Auth | Register, login, logout — happy paths and validation failures |
| Task CRUD | Create, read, update, delete — including authorization failures |
| Task Filtering | Filter by status, priority, search, due date, tag, combined filters |
| Task Authorization | Members see only own/assigned tasks; admins see all |
| Pagination | Cursor pagination structure and `per_page` limits |
| Tags | Attach and detach, auto-create on attach |

**Testing strategy:**

- `RefreshDatabase` on every test — SQLite in-memory so migrations run fresh each time, no state leaks between tests
- Factories for `User`, `Task`, and `Tag` — deterministic fake data
- Write the failing test first (`assertStatus(403)` before adding the Gate check, etc.)
- `actingAs($user)` for authenticated requests; `getJson()` / `postJson()` for assertions

Run all tests:

```bash
make test
```

---

## Security Checklist

- [x] **Token authentication** — Laravel Sanctum; token required on all protected routes
- [x] **Rate limiting** — 5 requests/min on `/register` and `/login`; 60 requests/min on all other routes
- [x] **Role-based authorization** — `TaskPolicy` enforces Admin vs Member rules on view, update, and delete
- [x] **Scoped queries** — `TaskService::list()` restricts non-admins to tasks they created or were assigned to; no raw ID filtering
- [x] **Mass-assignment protection** — explicit `$fillable` on all models; no `guarded = []` shortcuts
- [x] **Password hashing** — Laravel's built-in `Hashed` cast; plain-text passwords never stored

---

## Performance Wins

**Cursor pagination** instead of offset pagination. Offset queries (`LIMIT 15 OFFSET 300`) get slower as the offset grows because the database scans and discards rows. Cursor queries use an indexed column as a bookmark — cost stays constant regardless of how deep you page.

**Indexed filter columns.** Every column used in a `WHERE` clause has a database index:

| Column | Used for |
|--------|---------|
| `creator_id` | Scope member queries to own tasks |
| `assigned_to` | Scope member queries to assigned tasks |
| `status` | Filter by task status |
| `priority` | Filter by priority |
| `due_date` | Filter by due before/after |

---

## Endpoints

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/register` | No | Register a new user |
| POST | `/api/login` | No | Login, receive Bearer token |
| POST | `/api/logout` | Yes | Revoke all tokens for current user |
| GET | `/api/tasks` | Yes | List tasks with filters and cursor pagination |
| POST | `/api/tasks` | Yes | Create a new task |
| GET | `/api/tasks/{id}` | Yes | Get a single task |
| PUT | `/api/tasks/{id}` | Yes | Update a task (all fields optional) |
| DELETE | `/api/tasks/{id}` | Yes | Delete a task |
| POST | `/api/tasks/{id}/tags` | Yes | Attach tags to a task by name |
| DELETE | `/api/tasks/{taskId}/tags/{tagId}` | Yes | Detach a tag from a task |
| GET | `/api/tags` | Yes | List all tags |

### Query Parameters — `GET /api/tasks`

| Parameter | Type | Description |
|-----------|------|-------------|
| `status` | string | `todo`, `in_progress`, `review`, `done` |
| `priority` | string | `low`, `medium`, `high`, `urgent` |
| `assigned_to` | integer | Filter by assignee user ID |
| `search` | string | Search title and description (LIKE) |
| `due_before` | date | Tasks due on or before this date |
| `due_after` | date | Tasks due on or after this date |
| `tag` | string | Filter by tag name |
| `sort` | string | Sort field; prefix `-` for descending (e.g. `-due_date`) |
| `per_page` | integer | Max 50, default 15 |
| `cursor` | string | Cursor token from previous response |

---

## Roles & Authorization

| Role | What they can do |
|------|-----------------|
| `admin` | Full CRUD on all tasks |
| `member` | CRUD on tasks they created or were assigned to |

Authorization is enforced in `TaskPolicy` and called via `Gate::authorize()` in the controller.

---

## Setup Instructions

### Prerequisites

Docker and Docker Compose

### Installation

```bash
# Clone the repo
git clone <repo-url> task-manager-api
cd task-manager-api

# Copy environment config
cp .env.example .env
# Edit .env and set DB_DATABASE, DB_USERNAME, DB_PASSWORD, REDIS_HOST

# Build and start containers
make build
make up

# Run database migrations
make migrate

# Seed the database
make shell
php artisan db:seed
exit
```

### Running Tests

```bash
make test
```

### Reset Database

```bash
make fresh
```

### Make Commands

| Command | Description |
|---------|-------------|
| `make build` | Build Docker images |
| `make up` | Start containers |
| `make down` | Stop containers |
| `make migrate` | Run database migrations |
| `make fresh` | Drop all tables, re-migrate, and reseed |
| `make test` | Run the full test suite |
| `make shell` | Open a bash shell in the app container |
| `make tinker` | Open Laravel Tinker REPL |

The API runs at `http://localhost:8080/api`.

---

## Documentation

API documentation is generated from `postman_collection.json` and rendered via [Redoc](https://github.com/Redocly/redoc).

**Generate the OpenAPI spec locally:**

```bash
npm run docs
```

This reads `postman_collection.json` and outputs `openapi.yaml` at the project root.

**View the docs locally:**

Open `docs/index.html` in a browser (or use VS Code Live Server). Redoc fetches `../openapi.yaml` and renders the full interactive spec.

**Live docs (GitHub Pages):**

1. Push to `main` — GitHub Actions runs automatically, generates `openapi.yaml`, and commits it back
2. Go to repository Settings → Pages → Source: Deploy from branch → `main` → `/` (root)
3. Docs are live at: `https://<username>.github.io/<repo>/docs/`

---

## License

MIT — see [LICENSE](LICENSE)
