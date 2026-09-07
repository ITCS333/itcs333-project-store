# ITCS333 — Group Project: Campus Marketplace (20%)

A small online store built by a **team of 3–5 students** in **vanilla JS + vanilla PHP (PDO) + MySQL**. The project is **modular**: each student owns one feature module end-to-end, isolated from teammates by **frozen JSON API contracts**.

> **Why modular?** If a teammate drops the course (or a module is incomplete), the others are unaffected: every module is graded independently against contract-compliant **stubs** of its neighbors, and the frontend degrades gracefully when a dependency is down.

## The five modules
| Module | Owns (pages + endpoints + tables) | Contract |
|---|---|---|
| **m1_auth** | register/login/logout/me, sessions, `users` | `contracts/auth.md` |
| **m2_catalog** | product list/detail/categories, `products`, `categories` | `contracts/catalog.md` |
| **m3_cart** | cart add/update/remove/list, `cart_items` | `contracts/cart.md` |
| **m4_orders** | checkout, order history/detail, `orders`, `order_items` | `contracts/orders.md` |
| **m5_admin** | product CRUD, sales report, `admin_log` | `contracts/admin.md` |

**Team-size rules:** 5 members → one module each; 4 members → one takes **m3+m4**; 3 members → m3+m4 and **m1+m5**.

## How modules stay isolated
- Each module lives in `modules/<mX>/handler.php` (+ its pages in `public/<area>/`).
- Modules talk **only** through the JSON contracts in `contracts/` — never by including each other's code.
- The frontend uses `apiFetch()` (see `public/app.js`), which shows an **inline banner** when a dependency returns 503/`MODULE_UNAVAILABLE` instead of crashing.
- `contracts/` are **frozen at Milestone 1 (Week 6)** and cannot change without instructor approval.

## Database — portable SQL (SQLite / MySQL / PgSQL)
- The grader and CI use **SQLite**. You may develop locally against **MySQL or PgSQL**.
- Write SQL in the **portable subset**: lowercase snake_case, prepared statements, no `ENUM`/`ON DUPLICATE KEY`/backticks/engine functions. Use `db_insert_and_get_id()` (in `src/db.php`) instead of `lastInsertId()`.
- `src/db.php` honors `DB_DRIVER=sqlite|mysql|pgsql` and the `DB_*` env vars.
- The lint job (`./run_tests.sh --lint`) flags engine-specific SQL in `modules/`.

## Repo layout
```
public/            shared index.html + app.js + per-module page folders + api.php (router)
modules/<mX>/      YOUR handler.php (the part you implement)
src/               PROVIDED: db.php, http.php (do not modify)
stubs/             PROVIDED: contract-compliant reference of every module
contracts/         frozen API contracts
tests/             PROVIDED: contract + integration + degradation suites, lint
TEAM.md            members + module allocation + weekly contribution log
schema.sql seed.sql
run_tests.sh       --module <m> | --all-modules | --integration | --degradation | --lint | --all
```

## Run tests locally
```bash
composer install --working-dir=tests   # first time only
./run_tests.sh --module m2_catalog     # your module against stubs of the rest
./run_tests.sh --all                   # everything (modules + integration + degradation + lint)
```

## Grading (20% of course) — 70% individual / 30% shared
- **Individual (70):** your module's contract tests (40) + security checklist (15) + oral defense (15).
- **Shared (30):** integration suite (10) + degradation behavior (5) + team demo (5) + docs/`TEAM.md` (5) + style/commit hygiene (5).
- One **team fork** is submitted once in Blackboard. The instructor's grader overlays pristine tests + stubs and runs the 5-module matrix + integration. A dropped member's share of the **shared 30%** redistributes to the remaining members.

## Milestones
| Week | Deliverable |
|---|---|
| 4 | Teams + proposal + module allocation (`TEAM.md`) |
| 6 | **Contract freeze** (`contracts/`) |
| 10 | Per-module checkpoint (contract suite vs stubs) |
| 13 | Integration sprint |
| 14 | Final demo + oral discussion |

## Rules
Group work, but each module is individually authored and defended. Do not modify `tests/`, `stubs/`, `src/`, `.github/`, or `contracts/` after freeze. Official grading uses the instructor's pristine copies.
