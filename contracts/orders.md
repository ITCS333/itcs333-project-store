# Contract — M4 Orders / Checkout (owner: module m4_orders)

Base path: `/api/orders/`. Requires an authenticated user (M1). Reads the user's cart (M3) to place an order.

## POST /api/orders
Checkout: convert the current user's cart into an order, decrement stock, clear the cart.
- `201`: `{ "id": 10, "user_id": 2, "total": 25.0, "created_at": "...", "items": [ { "product_id": 1, "qty": 2, "unit_price": 3.5 } ] }`
- `400`: `{ "error": "CART_EMPTY" }`
- `401`: `{ "error": "AUTH_REQUIRED" }`
- `409`: `{ "error": "INSUFFICIENT_STOCK", "product_id": 1 }`

## GET /api/orders
Current user's order history.
- `200`: `{ "data": [ { "id": 10, "total": 25.0, "created_at": "..." } ] }`

## GET /api/orders/{id}
One order with its items (only the owner, or an admin).
- `200`: `{ "id": 10, "user_id": 2, "total": 25.0, "created_at": "...", "items": [ ... ] }`
- `403`: `{ "error": "FORBIDDEN" }`
- `404`: `{ "error": "NOT_FOUND" }`

## Order shape
`{ "id": int, "user_id": int, "total": number, "created_at": string, "items": [ { "product_id": int, "qty": int, "unit_price": number } ] }`
