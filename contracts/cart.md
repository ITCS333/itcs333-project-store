# Contract — M3 Cart (owner: module m3_cart)

Base path: `/api/cart/`. Requires an authenticated user (M1). Cart is per-user.

## GET /api/cart
Current user's cart with line subtotals and total.
- `200`: `{ "data": [ { "id": 1, "product_id": 1, "qty": 2, "unit_price": 3.5, "subtotal": 7.0 } ], "total": 7.0 }`
- `401`: `{ "error": "AUTH_REQUIRED" }`

## POST /api/cart
Add a product to the cart.
- Request: `{ "product_id": 1, "qty": 2 }`
- `201`: `{ "id": 1, "product_id": 1, "qty": 2, "unit_price": 3.5, "subtotal": 7.0 }`
- `401`: `{ "error": "AUTH_REQUIRED" }`
- `404`: `{ "error": "PRODUCT_NOT_FOUND" }`
- `422`: `{ "error": "VALIDATION", "fields": { "qty": "must be >= 1" } }`

## PATCH /api/cart/{id}
Change quantity of a cart line.
- Request: `{ "qty": 5 }`
- `200`: `{ "id": 1, "product_id": 1, "qty": 5, "unit_price": 3.5, "subtotal": 17.5 }`
- `404`: `{ "error": "NOT_FOUND" }`

## DELETE /api/cart/{id}
- `200`: `{ "deleted": true }`
- `404`: `{ "error": "NOT_FOUND" }`

## Cart line shape
`{ "id": int, "product_id": int, "qty": int, "unit_price": number, "subtotal": number }`
