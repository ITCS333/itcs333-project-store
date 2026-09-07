# Contract — M2 Catalog (owner: module m2_catalog)

Base path: `/api/catalog/`. Read-only for shoppers; writes happen via M5 admin.

## GET /api/catalog/products
List products. Optional `?category_id=N`, `?q=search`.
- `200`: `{ "data": [ { "id": 1, "category_id": 1, "name": "Notebook", "price": 3.5, "stock": 50 } ] }`

## GET /api/catalog/products/{id}
- `200`: `{ "id": 1, "category_id": 1, "name": "Notebook", "price": 3.5, "stock": 50 }`
- `404`: `{ "error": "NOT_FOUND" }`

## GET /api/catalog/categories
- `200`: `{ "data": [ { "id": 1, "name": "Stationery" } ] }`

## Product shape (consumed by cart/orders)
`{ "id": int, "category_id": int, "name": string, "price": number, "stock": int }`
