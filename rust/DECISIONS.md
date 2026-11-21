# Migration decisions

## ADR-001: Rust/Svelte owns all new runtime behavior

Status: accepted (2026-11-22)

Laravel is retained only as read-only behavior and data evidence. New production behavior is
implemented in Axum, PostgreSQL, the worker, and SvelteKit.

## ADR-002: Preserve old URLs only as server-side compatibility redirects

Status: accepted (2026-11-22)

An old URL redirects only to a concrete Svelte workflow with equivalent ownership and permission.
Unknown URLs remain 404. A generic workspace must not be used to imply feature parity.

## ADR-003: One bounded context at a time

Status: accepted (2026-11-22)

Each remaining Laravel domain gets typed Rust persistence, tenant-scoped API endpoints,
authorization, Svelte screens, and tests before the next unit begins. Generic JSON records are
not a replacement for a domain model.
