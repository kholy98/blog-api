# ✍️ Laravel Blog API

A RESTful API for a blog platform built with **Laravel 10**, implementing **JWT authentication**, **roles and permissions**, **CRUD operations**, **filtering**, **search**, **pagination**, **caching**, and **comments**.

---

## 🧭 Table of Contents
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Environment Setup](#environment-setup)
- [Authentication](#authentication)
- [API Endpoints](#api-endpoints)
  - [Posts](#posts)
  - [Comments](#comments)
- [Roles and Permissions](#roles-and-permissions)
- [Filtering, Search & Pagination](#filtering-search--pagination)
- [Caching](#caching)
- [Running Tests](#running-tests)
---

##  Features
* JWT-based authentication
* Role-based access: **admin** and **author**
* CRUD operations for blog posts
    * **Admin**: full access
    * **Author**: create/update/delete own posts only
* Filter posts by category, author, date range
* Search posts by title, category, author name
* Pagination support
* Post caching for faster response
* Commenting on posts
* Unit & feature tests included

---

##  Tech Stack
* PHP 8+
* Laravel 10
* MySQL / SQLite
* JWT Authentication (`tymon/jwt-auth`)
* Spatie Laravel Permission for roles
* Laravel Resource API Responses
* PHPUnit for testing

---

##  Installation
1.  Clone the repository:
    ```bash
    git clone [https://github.com/yourusername/blog-api.git](https://github.com/yourusername/blog-api.git)
    cd blog-api
    ```
2.  Install dependencies:
    ```bash
    composer install
    ```
3.  Copy `.env` file and configure database:
    ```bash
    cp .env.example .env
    ```
4.  Generate application key:
    ```bash
    php artisan key:generate
    ```
5.  Generate JWT secret:
    ```bash
    php artisan jwt:secret
    ```
6.  Run migrations and seed roles:
    ```bash
    php artisan migrate --seed
    ```
7.  Start development server:
    ```bash
    php artisan serve
    ```

---

##  Environment Setup
Configure your `.env` file for database and cache (Redis or file-based):

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=blog
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
```


##  Authentication

Use the returned token in the **Authorization: Bearer <JWT_TOKEN_HERE>** header for protected routes. 

[Image of JWT authentication flow]


---

### Register

`POST /api/auth/register`

| Field | Type | Description |
| :--- | :--- | :--- |
| `name` | string | User's full name. |
| `email` | string | Unique email address. |
| `password` | string | User's password (min 8 chars). |
| `password_confirmation` | string | Must match `password`. |

**Request Example:**

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Login

`POST /api/auth/login`

| Field | Type | Description |
| :--- | :--- | :--- |
| `email` | string | User's registered email address. |
| `password` | string | User's password. |

**Request Example:**

```json
{
  "email": "john@example.com",
  "password": "password"
}
```

**Response Example:**

```json
{
  "access_token": "JWT_TOKEN_HERE",
  "token_type": "bearer",
  "expires_in": 3600
}
```
##  API Endpoints

### Posts

| Method | Endpoint | Description | Roles Allowed |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/posts` | List posts with search, filters, pagination | All authenticated users |
| `POST` | `/api/posts` | Create a new post | Admin, Author |
| `GET` | `/api/posts/{id}` | Get single post with author & comments | All authenticated users |
| `PUT` | `/api/posts/{id}` | Update post | Admin or post owner |
| `DELETE` | `/api/posts/{id}` | Delete post | Admin or post owner |



### Comments

| Method | Endpoint | Description | Roles Allowed |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/posts/{post}/comment` | Add comment to a post | Authenticated users |

**Request:**

```json
{
  "body": "This is a comment."
}
```

**Response:**

```json
{
  "id": 1,
  "post_id": 2,
  "user": {
    "id": 3,
    "name": "Jane Doe"
  },
  "body": "This is a comment.",
  "created_at": "2025-11-21T12:00:00"
}
```


##  Roles and Permissions
- **Admin**: Full access to all endpoints and operations.
- **Author**: Can create posts and manage (update/delete) only their own posts.

Implemented using Spatie Laravel Permission.

##  Filtering, Search & Pagination

The `/api/posts` endpoint accepts the following query parameters to filter, search, and manage the returned data:

| Parameter | Description |
| :--- | :--- |
| `search` | Searches posts by **title**, **category**, or **author** name. |
| `category` | Filters posts by a specific category name. |
| `author_id` | Filters posts by a specific author's ID. |
| `from` / `to` | Filters posts created within a specific **date range**. |
| `sort` | Sorts results by a specified field. Prefix with `-` for **descending** order (e.g., `-created_at`). |
| `per_page` | Specifies the number of posts to return per page. |

**Example:**
`GET /api/posts?search=tech&category=Technology&from=2025-11-01&to=2025-11-21&sort=-created_at&per_page=5`


##  Caching

* The list of posts is cached for **60 minutes** using a unique cache key that incorporates the **query parameters** (ensuring unique cache for unique requests).
* The cache is automatically **cleared** whenever posts are created, updated, or deleted, guaranteeing data freshness.
* This feature is implemented in the `PostController@index` method using **Laravel's Cache facade**.

##  Running Tests

Before running tests, ensure a testing database is configured in the `.env.testing` file.

Run PHPUnit using the following command:

```bash
php artisan test

