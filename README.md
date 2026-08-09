# muchiri_os

Engineering Control Plane (ECP) — the durable coordination layer between AI agents and Andrew's projects, repositories, infrastructure, and knowledge. Agents (initially OpenClaw) call this instead of learning every external API directly; this app translates high-level calls like `get_project_context()` into whatever GitHub/infra/knowledge operations they map to.

Full design rationale lives in the original planning doc (not in this repo). This README only tracks what's actually built.

## Status: Phase 1 MVP

Built so far:
- Project / Repository / Environment / Decision / KnowledgeItem / AuditEvent domain model
- Owner dashboard (Breeze auth) — project list + detail
- API (Sanctum token auth): `GET /api/projects`, `GET /api/projects/{slug}/context`
- Seeded with two real projects: **Tenthline** (pilot) and **Bancassurance**

Not yet built: work items, GitHub/Todoist integration, agent runs, approvals, deployments. Those are later phases.

## Local setup

- Served via Laravel Valet at `http://muchiri_os.test`
- MySQL database `muchiri_os` (root / see local `.env` for password)
- `php artisan migrate --seed` to rebuild from scratch

## API

```bash
curl http://muchiri_os.test/api/projects/tenthline/context \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json"
```

Tokens are issued via Sanctum (`$user->createToken('name')`) — there's no token-management UI yet.
