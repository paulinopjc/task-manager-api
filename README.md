# Task Manager API

RESTful API for managing tasks with role-based access control, tagging, filtering, and cursor pagination. Built with Laravel 11 and tested with PHPUnit (42+ tests).

## Stack

- **PHP 8.3** / **Laravel 11**
- **MySQL 8.0** (local) / **PostgreSQL via Neon** (production)
- **Redis 7** (cache/sessions)
- **Laravel Sanctum** (token auth)
- **Docker Compose** (local development)
- **Render** (production deployment)

## API Endpoints

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Create account |
| POST | `/api/login` | Get auth token |
| POST | `/api/logout` | Revoke token |

### Tasks
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tasks` | List tasks (filtered, paginated) |
| POST | `/api/tasks` | Create task |
| GET | `/api/tasks/{id}` | View task |
| PUT | `/api/tasks/{id}` | Update task |
| DELETE | `/api/tasks/{id}` | Delete task |

### Tags
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/tags` | List all tags |
| POST | `/api/tasks/{id}/tags` | Attach tags to task |
| DELETE | `/api/tasks/{taskId}/tags/{tagId}` | Detach tag |

### Filtering & Sorting

`GET /api/tasks` supports:
- `?status=todo` | `in_progress` | `done`
- `?priority=low` | `medium` | `high`
- `?search=keyword` (searches title)
- `?due_before=2026-04-01`
- `?tag=backend`
- `?sort=-priority` (prefix `-` for descending)
- `?per_page=15` (max 50, cursor pagination)

## Roles & Authorization

| Role | Permissions |
|------|------------|
| **Admin** | Full access to all tasks |
| **Member** | Own tasks + tasks assigned to them |

## Local Development

**Prerequisites:** Docker Desktop

```bash
make build       # Build containers
make up          # Start containers
make migrate     # Run migrations
make test        # Run test suite
make shell       # Shell into PHP container
make fresh       # Reset DB with seeders
```

The API runs at `http://localhost:8088/api`.

## Testing

42+ tests covering auth, CRUD, authorization, filtering, sorting, pagination, and tags.

```bash
make test                          # Run all tests
make test-filter filter=LoginTest  # Run specific test
```

Tests use SQLite in-memory — no external database needed.

## Environment Variables

Copy `.env.example` to `.env` and configure:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=app
DB_PASSWORD=secret
```

## Deployment

Deployed to **Render** (Docker) with **Neon PostgreSQL**. See `Dockerfile.prod` for the production build.
