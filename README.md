# CodeGuardian AI

> **Analyze. Improve. Validate. Report.**

An autonomous engineering copilot for **Laravel** and **Flutter** teams. Think SonarQube + Snyk + CodeRabbit + GitHub Copilot Review + QA Automation — all in one platform.

---

## What It Does

CodeGuardian AI runs **8 specialized AI agents** on your codebase:

| Agent | What It Finds |
|---|---|
| **Laravel Architect** | SOLID violations, fat controllers, missing service layers |
| **Flutter Architect** | Widget tree issues, state management problems, rebuild waste |
| **Security** | SQL injection, XSS, missing auth, hardcoded secrets |
| **Performance** | N+1 queries, missing indexes, widget rebuild issues |
| **QA** | Generates unit, feature, API, and widget tests |
| **Tech Debt** | Dead code, duplicate logic, god classes |
| **DevOps** | Docker issues, CI/CD gaps, deployment risks |
| **Reporting** | Executive summary, risk summary, remediation timeline |

---

## Architecture

```
codeguardian-ai/
├── backend/           # Laravel 13 + PHP 8.4 API
├── frontend/          # Flutter 3 mobile & web app
├── docker/            # Container configs (Nginx, PHP-FPM, Postgres)
├── docker-compose.yml # Full local dev stack
└── .github/workflows/ # CI/CD pipelines
```

### Backend Stack

- **Laravel 13** / PHP 8.4
- **PostgreSQL 16** with Row-Level Security
- **Redis 7** — cache, queue broker, WebSocket
- **Laravel Horizon** — queue workers (5 dedicated queues)
- **Laravel Reverb** — WebSocket server for real-time analysis updates
- **Laravel Sanctum** — JWT-like token auth with refresh token rotation
- **Spatie Permissions** — RBAC with 5 roles
- **MinIO** — Object storage for code snapshots and reports

### Frontend Stack

- **Flutter 3** — mobile + web from one codebase
- **Riverpod 2** — reactive state management
- **GoRouter** — declarative navigation
- **Dio** — HTTP client with interceptors + auto token refresh
- **Freezed** — immutable data models
- **Responsive Framework** — mobile/tablet/desktop layouts

### AI Provider Abstraction

```php
// Switch providers without changing business logic
AiProviderFactory::make('openai');   // GPT-4o
AiProviderFactory::make('claude');   // Claude 3.5 Sonnet
AiProviderFactory::make('gemini');   // Gemini 1.5 Pro
```

---

## Quick Start

### Prerequisites

- Docker + Docker Compose
- Git

### 1. Clone & Configure

```bash
git clone https://github.com/your-org/code-guardian-ai.git
cd code-guardian-ai

cp backend/.env.example backend/.env
# Edit backend/.env and add your AI API keys + OAuth credentials
```

### 2. Start the Stack

```bash
docker compose up -d
```

Services started:
| Service | URL |
|---|---|
| API | http://localhost:8000/api/v1 |
| Horizon Dashboard | http://localhost:8000/horizon |
| WebSocket | ws://localhost:8080 |
| MinIO Console | http://localhost:9001 |
| Mailpit | http://localhost:8025 |

### 3. Initialize Database

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Default credentials:
- **Admin**: `admin@codeguardian.ai` / `Admin@123456`
- **Demo**: `demo@codeguardian.ai` / `Demo@123456`

### 4. Run Flutter App

```bash
cd frontend
flutter pub get
dart run build_runner build --delete-conflicting-outputs
flutter run
```

---

## API Overview

```
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me

GET    /api/v1/organizations
POST   /api/v1/organizations

GET    /api/v1/organizations/:id/repositories
POST   /api/v1/organizations/:id/repositories

GET    /api/v1/organizations/:id/projects
POST   /api/v1/organizations/:id/projects

POST   /api/v1/organizations/:id/analyses       # Trigger analysis
GET    /api/v1/organizations/:id/analyses/:aid/issues
GET    /api/v1/organizations/:id/analyses/:aid/recommendations
GET    /api/v1/organizations/:id/analyses/:aid/tests
GET    /api/v1/organizations/:id/analyses/:aid/report

POST   /api/v1/webhooks/:provider/:repoId       # Webhook endpoint
```

---

## Repository Providers

| Provider | Status |
|---|---|
| **Bitbucket Cloud** | ✅ Priority 1 — implemented |
| **GitHub** | ✅ Priority 2 — implemented |
| **GitLab** | ✅ Priority 3 — implemented |

New providers can be added by implementing `RepositoryProviderContract` and registering in `RepositoryProviderFactory`.

---

## Queue Architecture

| Queue | Workers (prod) | Purpose |
|---|---|---|
| `default` | 5 | General jobs |
| `ai-analysis` | 3 | AI agent pipeline (10 min timeout) |
| `repository-processing` | 4 | Clone & index repos |
| `code-parsing` | 4 | AST parsing |
| `report-generation` | 3 | PDF/JSON reports |
| `notifications` | 3 | Emails, webhooks |

---

## Security

- JWT + rotating refresh tokens (single-use)
- RBAC with 5 roles: `super_admin`, `org_admin`, `eng_manager`, `developer`, `qa_engineer`
- 4-layer multi-tenant isolation (app scope → PostgreSQL RLS → MinIO policies → queue context)
- All secrets encrypted with `encrypt()` before storage
- Webhook signature verification (HMAC-SHA256)
- OWASP Top 10 compliance in the AI Security Agent

---

## Scalability

Designed for: **1,000 organizations** | **10,000 projects** | **100,000 analyses/month**

- Event-driven: all heavy work is queued
- Horizontal scaling: PHP-FPM + Kubernetes HPA
- PostgreSQL: connection pooling via PgBouncer
- Redis: pub/sub for real-time + cache-aside for hot data

---

## Sprints

| Sprint | Focus | Status |
|---|---|---|
| 1 | Foundation — auth, orgs, Docker, CI | ✅ Done |
| 2 | OAuth (GitHub/Google), teams | 🔄 Next |
| 3 | Repository adapters, webhooks | ⏳ Planned |
| 4 | Code parser (PHP AST, Dart AST) | ⏳ Planned |
| 5–6 | All 8 AI agents + orchestrator | ⏳ Planned |
| 7 | Flutter UI — analysis screens | ⏳ Planned |
| 8 | Report generation, PDF export | ⏳ Planned |
| 9 | Test validation engine | ⏳ Planned |
| 10 | Billing, enterprise features | ⏳ Planned |
