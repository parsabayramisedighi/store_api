# 🛒 Store API

A RESTful API for an online store, written in plain PHP (no framework) using the **MVC** pattern. It provides user registration and login with JWT-based authentication, role-based access control, product and category management, and a per-user shopping cart.

> ⚠️ **Note:** This project is not complete and contains some minor bugs. It is published as a portfolio sample.

## ✨ Features

- 🔐 Token-based authentication
- 👥 Role-based access: regular users, admins, and blocked accounts
- 🔍 Public product and category browsing with search, price range, category filter, and pagination
- 🛠️ Admin endpoints for managing products, categories, and users
- 🛍️ Per-user shopping cart with automatic price and total calculation
- 🌳 Hierarchical (parent / child) categories

## 🏗️ Architecture

The project follows the **MVC** pattern with a small custom front controller and router.

```
store_api/
├── index.php                     # Front controller: registers all routes
├── .htaccess                     # Rewrites every request to index.php
├── core/
│   ├── Router.php                # Custom router (exact method + path matching, middleware support)
│   ├── Auth.php                  # Holds the authenticated user for the current request
│   └── middlewares.php           # $auth and $admin middleware definitions
├── App/
│   ├── controllers/              # Request handling and validation
│   │   ├── AuthController.php
│   │   ├── UserController.php
│   │   ├── ProductController.php
│   │   ├── CategoryController.php
│   │   └── CartController.php
│   ├── Models/                   # Database access layer
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── ProductCategory.php
│   │   └── Cart.php
│   └── middlewares/
│       └── AuthMiddleware.php    # JWT + role verification
└── include/
    ├── config.php
    └── functions.php             # Helpers: send_json, pdo, JWT encode/decode, env loader
```

**Request flow:** `.htaccess` → `index.php` → `Router` → middleware (if the route is protected) → controller → model → JSON response.

## 🌐 Base URL

The router strips a leading `store_api/` segment from the request path. This means the project is meant to live in a folder named `store_api` inside your web server's root directory (for example `htdocs`), and the folder name is simply part of the URL.

All endpoints in this document are relative to that base URL:

```
http://localhost/store_api/api/
```

Usage example after downloading the project and placing it in `store_api/`:

- `GET http://localhost/store_api/api/categories` returns the category tree

## 📌 Conventions

- Request bodies are JSON (`Content-Type: application/json`) for all `POST` endpoints.
- Query-string parameters are used for `GET` endpoints and for identifying a resource on `POST` endpoints (e.g. `?id=5`).
- Successful responses contain `"success": true`; errors contain `"success": false` and a `"message"`.
- Error and status messages are returned in **Persian**.
- Requests to an unknown route return `404`.

## 🔑 Authentication

1. Register with `POST /api/register`.
2. Log in with `POST /api/login` to receive a token.
3. Send the token on protected endpoints in the `Authorization` header:

```
Authorization: Bearer <token>
```

Token details:

- Default lifetime is **15 minutes**. Send `"remember": true` on login for **30 days**.
- Logging in invalidates any previous token of the same user (one active session per user).
- `POST /api/logout` invalidates the current token.
- Expired tokens are removed on use.

### 🎭 Roles

The role is stored in the `user_lvl` field of a user:

| Value | Role |
|-------|------|
| `1` | Regular user |
| `2` | Admin (required for all `/api/admin/*` endpoints) |
| `3` | Blocked (token is revoked and requests return `403`) |

## 🗺️ Endpoints overview

| Method | Endpoint | Access | Description |
|--------|----------|--------|-------------|
| POST | `/api/register` | Public | Create an account |
| POST | `/api/login` | Public | Log in and get a token |
| POST | `/api/logout` | User | Invalidate the current token |
| GET | `/api/categories` | Public | List categories (tree) |
| GET | `/api/categories/show` | Public | Get a single category |
| GET | `/api/products` | Public | List active products |
| GET | `/api/products/show` | Public | Get a single active product |
| GET | `/api/user/profile` | User | Get own profile |
| POST | `/api/user/profile/update` | User | Update own profile |
| GET | `/api/user/cart` | User | View cart |
| POST | `/api/user/cart/update` | User | Add / decrease a cart item |
| POST | `/api/user/cart/delete` | User | Remove a cart item |
| GET | `/api/admin/products` | Admin | List all products (including inactive) |
| GET | `/api/admin/products/show` | Admin | Get any product |
| POST | `/api/admin/products/create` | Admin | Create a product |
| POST | `/api/admin/products/update` | Admin | Update a product |
| POST | `/api/admin/products/delete` | Admin | Delete a product |
| GET | `/api/admin/users` | Admin | List users |
| GET | `/api/admin/users/show` | Admin | Get a user |
| POST | `/api/admin/users/update` | Admin | Update a user |
| POST | `/api/admin/users/delete` | Admin | Delete a user |
| POST | `/api/admin/categories/create` | Admin | Create a category |
| POST | `/api/admin/categories/delete` | Admin | Delete a category |

---

## 🔐 Auth

### `POST /api/register`

Creates a new user account.

**Body (JSON)**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `username` | string | Yes | Min 4 characters, must be unique |
| `email` | string | Yes | Valid email, must be unique |
| `password` | string | Yes | Min 4 characters |
| `phone` | string | Yes | Normalized to start with `0`; must be 11 digits |

**Success response**

```json
{ "success": true, "message": "...", "userId": "12" }
```

---

### `POST /api/login`

**Body (JSON)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `username` | string | Yes | |
| `password` | string | Yes | |
| `remember` | bool | No | If truthy, the token lasts 30 days instead of 15 minutes |

**Success response**

```json
{ "success": true, "token": "<jwt>" }
```

---

### `POST /api/logout`

Requires authentication. No body.

**Success response**

```json
{ "success": true, "message": "..." }
```

---

## 🗂️ Categories

Categories have at most a parent/child structure: a category with `parent_id = null` is a top-level category.

### `GET /api/categories`

Returns all top-level categories with their children.

**Success response**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Electronics",
      "children": [
        { "id": 4, "name": "Phones" }
      ]
    }
  ]
}
```

### `GET /api/categories/show`

**Query parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Category ID |

### `POST /api/admin/categories/create`

**Body (JSON)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `name` | string | Yes | Must be unique |
| `parent_id` | int | No | ID of an existing category to nest under |

**Success response** (`201`)

```json
{ "success": true, "message": "...", "data": { "id": 7, "name": "Laptops", "parent_id": 1 } }
```

### `POST /api/admin/categories/delete`

**Query parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Category ID |

A category that still has sub-categories cannot be deleted.

---

## 📦 Products

Product objects have the following shape:

```json
{
  "ID": 1,
  "product_name": "Example",
  "product_description": "Description",
  "product_price": 100000,
  "product_discount_price": 90000,
  "active": true,
  "category": [ { "ID": 1, "name": "Electronics", "parent_id": null } ]
}
```

`product_discount_price` is `null` when there is no discount.

### `GET /api/products`

Lists **active** products with filtering and pagination.

**Query parameters (all optional)**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `search` | string | | Partial match on product name |
| `min_price` | number | | Minimum `product_price` |
| `max_price` | number | | Maximum `product_price` |
| `category_id` | int | | Only products in this category |
| `page` | int | `1` | Page number |
| `size` | int | `10` | Items per page |

**Success response**

```json
{
  "success": true,
  "page": 1,
  "size": 10,
  "total": 42,
  "data": [ /* product objects */ ]
}
```

### `GET /api/products/show`

Returns a single **active** product.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Product ID |

### `GET /api/admin/products`

Same query parameters and response as `GET /api/products`, but also includes inactive products.

### `GET /api/admin/products/show`

Same as `GET /api/products/show`, but also returns inactive products.

### `POST /api/admin/products/create`

**Body (JSON)**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_name` | string | Yes | |
| `product_description` | string | Yes | |
| `product_price` | number | Yes | Numeric |
| `product_discount_price` | number | No | Numeric, must be lower than `product_price` |
| `categories` | array of ints | Yes | At least one existing category ID |
| `active` | `0` or `1` | Yes | Whether the product is visible publicly |

**Category behavior:** selecting a top-level category also assigns all of its sub-categories; selecting a sub-category also assigns its parent.

**Success response** (`201`)

```json
{ "success": true, "message": "...", "product_id": "15" }
```

### `POST /api/admin/products/update`

**Query parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Product ID |

**Body (JSON)** – send only the fields you want to change; at least one field is required.

| Field | Type | Description |
|-------|------|-------------|
| `product_name` | string | |
| `product_description` | string | |
| `product_price` | number | |
| `product_discount_price` | number / `null` | Send `null` or `""` to remove the discount |
| `categories` | array of ints | Replaces the product's categories (same parent/child behavior as create) |
| `active` | `0` or `1` | |

**Success response**

```json
{ "success": true, "message": "...", "data": { /* updated product */ } }
```

### `POST /api/admin/products/delete`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | Product ID (query string) |

---

## 👤 User profile

### `GET /api/user/profile`

Returns the authenticated user's data.

```json
{ "success": true, "data": { /* user fields */ } }
```

### `POST /api/user/profile/update`

**Body (JSON)** – send only the fields you want to change.

| Field | Type | Rules |
|-------|------|-------|
| `username` | string | Min 4 characters, must be unique |
| `email` | string | Valid email, must be unique |
| `phone` | string | Normalized to start with `0`; must be 11 digits |


---

## 🛍️ Cart

### `GET /api/user/cart`

Returns the user's cart.

```json
{
  "success": true,
  "data": {
    "items": [
      { "product_id": 3, "count": 2, "name": "Phone", "price": 18000000, "total": 36000000 }
    ],
    "grand_total": 36000000
  }
}
```

### `POST /api/user/cart/update`

**Body (JSON)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | int | Yes | Existing product ID |
| `action` | string | Yes | `add` (add the product or increase count) or `minus` (decrease count) |

The count can never go below 1; use the delete endpoint to remove an item.

**Success response**

```json
{ "success": true, "count": 2, "action": "add" }
```

### `POST /api/user/cart/delete`

**Body (JSON)**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `product_id` | int | Yes | Product to remove from the cart |

---

## 🧑‍💼 User management (admin)

### `GET /api/admin/users`

Returns all users.

### `GET /api/admin/users/show`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | User ID |

### `POST /api/admin/users/update`

**Query parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | User ID |

**Body (JSON)** – send only the fields you want to change.

| Field | Type | Rules |
|-------|------|-------|
| `username` | string | Min 4 characters, must be unique |
| `email` | string | Valid email, must be unique |
| `password` | string | Min 4 characters (stored hashed) |
| `phone` | string | Normalized to start with `0`; must be 11 digits |
| `user_lvl` | int | `1` (user), `2` (admin), or `3` (blocked) |

### `POST /api/admin/users/delete`

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | int | Yes | User ID (query string) |