# Changelog

All notable changes to this project will be documented in this file.

## [0.1.3] - 2025-12-29
### 🐛 Bug Fixes
- Fixed an issue where `ws:list` could show stale file descriptors after restarting the WebSocket server.

### 🧠 Improvements
- Added `clearAllFds()` to the `ConnectionStore` contract.
- Implemented connection index cleanup for Memory, Table, and Redis connection stores.
- Automatically clear the active connection index when running `ws:start` to ensure a clean server state.

### Notes
- This ensures accurate WebSocket connection listings across server restarts.
- Especially important when using the Redis connection store in daemonized or long-running environments.
- No breaking changes.

## [0.1.2] - 2025-12-29
### ✨ New Features
- Added `ws:list` Artisan command to list active WebSocket connections.
- Display connection metadata including scope, user, and connection age.
- Added optional `--count` and `--json` flags for scripting and monitoring use cases.

### Improvements
- Extended connection store abstraction to track connection timestamps and activity.
- Added in-memory connection store for development and single-worker setups.
- Improved Redis connection store reliability for long-running servers.

### Bug Fixes
- Fixed an issue where authenticated connections could be missing from connection listings.
- Improved connection cleanup to ensure closed connections are fully removed from stores.

### ⚠️ Notes
- When using the Table (Swoole\Table) connection store, `ws:list` only reflects connections inside the WebSocket server process.
  For cross-process visibility (e.g. from Artisan), use the Redis connection store.

## [0.1.1] - 2025-12-29
### Bug Fixes
- Fixed an issue where WebSocket command handlers using `$payload` instead of `$data` caused a `BindingResolutionException` when defined as class callables.

### ✨ Improvements
- Added helper methods to `WsContext` for managing WebSocket connections:
    - `isEstablished()`
    - `disconnect()`
    - `close()` (alias)
    - `disconnectAndForget()`

### Notes
- No breaking changes. Existing handlers using `$data` continue to work as before.

## [0.1.0] - 2025-12-18
### v0.1.0 — Initial Developer Preview
First public developer preview of laravel-swoole-ws: a Laravel-native WebSocket server built on Swoole / OpenSwoole, designed for command-based protocols, scoped connections, and real-time device communication.

### ✨ Core Features
- WebSocket server powered by Swoole / OpenSwoole
- Laravel-style routing via `WS::route()`
- Command-based messaging protocol
    - `WS::command()` for incoming device/client commands
    - `WS::response()` for handling responses
- Scoped connections by handshake URL
- Channel system with authorization
- Middleware support (global + per-route)
- Authentication middleware (`ws.auth`)
- Connection stores (in-memory, Swoole Table, Redis-backed)
- Built-in subscribe / unsubscribe routes
- CLI commands (e.g. `php artisan ws:start`)
