# Contract — M5 Admin / Reports (owner: module m5_admin)

Base path: `/api/admin/`. Requires an authenticated **admin** user (M1 with `is_admin=1`). Non-admin → `403`.

## POST /api/admin/products
Create a product.
- Request: `{ "category_id": 1, "name": "Marker", "price": 1.5, "stock": 60 }`
- `201`: `{ "id": 6, "category_id": 1, "name": "Marker", "price": 1.5, "stock": 60 }`
- `403`: `{ "error": "FORBIDDEN" }`
- `422`: `{ "error": "VALIDATION", "fields": { "name": "required" } }`

## PUT /api/admin/products/{id}
Update a product.
- `200`: `{ "id": 6, "category_id": 1, "name": "Marker", "price": 1.5, "stock": 60 }`
- `403`: `{ "error": "FORBIDDEN" }`
- `404`: `{ "error": "NOT_FOUND" }`

## DELETE /api/admin/products/{id}
- `200`: `{ "deleted": true }`
- `403`: `{ "error": "FORBIDDEN" }`
- `404`: `{ "error": "NOT_FOUND" }`

## GET /api/admin/reports/sales
Sales report (reads via the `v_sales` view).
- `200`: `{ "data": [ { "order_id": 10, "user_id": 2, "total": 25.0, "created_at": "..." } ], "revenue": 25.0 }`
- `403`: `{ "error": "FORBIDDEN" }`

## Admin product shape
Same as catalog product: `{ "id": int, "category_id": int, "name": string, "price": number, "stock": int }`
