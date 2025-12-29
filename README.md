# 🔌⚡ Laravel Swoole WebSocket (laravel-swoole-ws)

A **high-performance WebSocket server library for Laravel** powered by **Swoole / OpenSwoole**, designed for:

* Real-time applications
* IoT / device communication
* Command-based device protocols
* Channel & room broadcasting
* Scalable multi-connection state management (memory / table / Redis)

This package provides a **Laravel-native DX** while running outside the HTTP lifecycle.

> ⚠️ **Status:** Under active development
> APIs are stabilizing, but breaking changes may occur.

---

## ✨ Features (at a glance)

### Core

* 🚀 Swoole / OpenSwoole WebSocket server
* 🔌 Laravel service provider & facade
* 🧭 WebSocket routing (`WS::route`)
* 🧠 Command-based device protocol (`WS::command`, `WS::response`)
* 🌐 Handshake URL path scoping (`/pub/chat`, `/attendance`, etc.)

### Security & Middleware

* 🔐 Middleware support (`ws.auth`, custom middleware)
* 👥 Channels & presence authorization

### Messaging

* 📡 Broadcast to rooms / users
* 🧩 Scoped command routing

### State & Scaling

* 🗃 Connection stores:

  * In-memory
  * Swoole\Table
  * Redis (multi-server ready)

### Tooling

* ⚙️ Artisan commands (`ws:start`, `ws:stop`, `ws:reload`, `ws:list`)
* 🧪 Testbench + PHPUnit support

---

## 📦 Requirements

* PHP **8.2+**
* Laravel **10+**
* **Swoole** or **OpenSwoole** extension enabled

```bash
php -m | grep swoole
php -m | grep openswoole
```

---

## 📥 Installation

```bash
composer require erfanvahabpour/laravel-swoole-ws
```

Laravel auto-discovery is enabled.

---

## 🗂 Publish Config & Routes

```bash
php artisan vendor:publish --tag=ws-config
php artisan vendor:publish --tag=ws-routes
php artisan vendor:publish --tag=ws-channels
```

Creates:

* `config/ws.php`
* `routes/ws.php`
* `routes/ws_channels.php`

---

## ▶️ Starting the WebSocket Server

```bash
php artisan ws:start
```

```
WS server starting on 0.0.0.0:9502
```

Stop / reload:

```bash
php artisan ws:stop
php artisan ws:reload
```

> ℹ️ On every `ws:start`, the server clears the active connection index to prevent stale connections from appearing in `ws:list`.

---

## ⚙️ Artisan Commands

```bash
php artisan ws:start
php artisan ws:stop
php artisan ws:reload
php artisan ws:list
```

### `ws:list`

Lists active WebSocket connections.

Example:

```
+---+----+-------------+-----------+------------+
| # | FD | Scope       | User      | Connected  |
+---+----+-------------+-----------+------------+
| 1 | 12 | /pub/chat   | guest     | 2m 14s     |
| 2 | 18 | /attendance| user#42   | 12m 03s    |
+---+----+-------------+-----------+------------+
```

Options:

```bash
php artisan ws:list --count
php artisan ws:list --json
```

> ℹ️ With **Swoole\Table**, `ws:list` reflects only the current WS process.
> Use **Redis** for cross-process visibility.

---

## 🔁 WebSocket Protocols Supported

This library supports **two protocols simultaneously**.

---

## 1️⃣ Legacy Route Protocol (`WS::route`)

### Client → Server

```json
{
  "path": "/chat",
  "action": "send",
  "data": { "text": "hello" },
  "meta": {}
}
```

### Route

```php
WS::route('/chat', 'send', function ($ctx, $data) {
    return ['ok' => true];
});
```

---

## 2️⃣ Command / Device Protocol (`WS::command`)

Designed for **IoT / terminal / attendance devices**.

### Client → Server

```json
{
  "cmd": "reg",
  "sn": "ABC123",
  "version": "1.0"
}
```

### Server → Client

```json
{
  "ret": "reg",
  "result": true,
  "cloudtime": "2025-01-01 12:00:00"
}
```

---

## 🌐 Handshake Path Scoping

Devices can connect using different URLs:

```
ws://127.0.0.1:9502/pub/chat
ws://127.0.0.1:9502/attendance
```

Each path becomes a **routing scope**, allowing the same command names with different logic.

---

## 🧭 Scoped Command Routing

```php
WS::scope('/pub/chat')->command('reg', fn ($ctx, $payload) => ['pong' => true]);

WS::scope('/attendance')->command('reg', function ($ctx, $payload) {
    return [
        'device' => 'attendance',
        'sn' => $payload['sn'] ?? null,
    ];
});
```

---

## 🔄 Automatic Replies

Returning an array automatically sends:

```json
{ "ret": "<cmd>", "result": true, ... }
```

Manual reply:

```php
$ctx->replyRet('reg', true, ['cloudtime' => now()]);
```

---

## 🔌 Connection Lifecycle Helpers

```php
$ctx->isEstablished();
$ctx->disconnect();
$ctx->disconnectAndForget();
```

Use `disconnectAndForget()` for:

* kicking users
* invalid devices
* admin disconnects

---

## 🔐 Middleware & Authentication

* Built-in: `ws.auth`
* Handshake token: `?token=TOKEN`
* Message token: `{ "meta": { "auth": "TOKEN" } }`

Custom resolver:

```php
config()->set('ws.auth.resolver', fn ($token) => (object)['id' => 1]);
```

---

## 📡 Channels & Presence

```php
WS::channel('private-chat.{id}', fn ($user, $id) => true);
```

---

## 🗃 Connection Stores

* `memory` – simple, single worker
* `table` – fast shared memory
* `redis` – recommended for scaling & CLI introspection

---

## 🧪 Testing & CI

```bash
vendor/bin/phpunit
```

* PHP 8.2 / 8.3
* PHPUnit
* GitHub Actions

---

## 🗺 Roadmap

* [x] WebSocket routing
* [x] Command protocol
* [x] Scoped connections
* [ ] Presence broadcasting
* [ ] Rate limiting
* [ ] Metrics endpoint
* [ ] Binary protocol support

---

## License

MIT © Erfan Vahabpour
