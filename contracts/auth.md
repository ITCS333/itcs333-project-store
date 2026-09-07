# Contract — M1 Auth (owner: module m1_auth)

Base path: `/api/auth/`. All bodies JSON. Errors use the envelope `{"error": CODE, ...}`.

## POST /api/auth/register
Create a user. Passwords must be hashed (`password_hash`).
- Request: `{ "username": "ali", "password": "secret", "is_admin": 0 }` (`is_admin` optional, default 0)
- `201`: `{ "id": 3, "username": "ali", "is_admin": 0 }`
- `409`: `{ "error": "USERNAME_TAKEN" }`
- `422`: `{ "error": "VALIDATION", "fields": { "username": "required" } }`

## POST /api/auth/login
Establish a session (`session_regenerate_id(true)` on success).
- Request: `{ "username": "ali", "password": "secret" }`
- `200`: `{ "id": 3, "username": "ali", "is_admin": 0, "token": "<csrf-or-session-marker>" }`
- `401`: `{ "error": "INVALID_CREDENTIALS" }`

## POST /api/auth/logout
Destroy the session.
- `200`: `{ "ok": true }`

## GET /api/auth/me
Current session user.
- `200`: `{ "id": 3, "username": "ali", "is_admin": 0 }`
- `401`: `{ "error": "AUTH_REQUIRED" }`

## Session contract (consumed by other modules)
After login, `$_SESSION['user_id']` and `$_SESSION['username']` are set.
Other modules check `$_SESSION['user_id']` for auth and the user's admin flag via `/api/auth/me` or a shared `current_user()` helper.
