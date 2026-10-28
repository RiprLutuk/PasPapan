# Sessions and error pages

The API is the authority for authentication. Reloading a page keeps a valid session; it does not create a new session or restart its absolute lifetime.

## Time limits

Configure the API environment (restart the API after changing it):

```dotenv
SESSION_IDLE_SECONDS=1800
SESSION_TTL_SECONDS=28800
```

Defaults: 30 minutes without interaction, and 8 hours after sign-in even when active. Valid ranges: absolute 300–86400 seconds; idle 60 seconds up to the absolute limit. New values apply to newly issued sessions. Existing sessions keep their stored deadlines. Migration 0035 gives existing sessions an idle deadline of at most 30 minutes, capped by their original expiration.

Pointer, keyboard, touch and scroll interactions allow the frontend to submit a trusted-origin `POST /v1/auth/activity` at most once per minute. This extends only the idle deadline, never the absolute deadline. Session checks, notification polling and SSE do not extend either deadline. The browser timer uses server time to avoid device clock differences. Background tabs stop activity updates. Any tab with real activity can keep the shared session alive.

A warning appears during the final two minutes. The user can select **Stay signed in**. Before redirecting, the app confirms expiry with the API, so an active second tab is respected. Logout synchronizes across tabs. Revocation, account suspension and revoked memberships remain enforced server-side on every authenticated request. Profile → Sessions shows last activity, idle expiry and absolute limit; only unexpired sessions are listed.

An expired/revoked session returns 401 with a clearing cookie and redirects the app to sign-in with an explanation and a safe internal return path. Wrong passwords during account changes do not log the user out. Network/server failures show a retryable service error and do not pretend the session expired. Authenticated responses and HTML are not stored by the PWA cache.

## Error routes

Unknown URLs, including extra segments after valid routes, return HTTP 404 rather than constructing a generic module. Only explicitly registered legacy modules and aliases remain supported.

The shared error view covers 401, 403, 404, 500 and 502/503, with ID/EN copy, light/dark theme, retry where appropriate and a home/sign-in action. It never echoes an arbitrary URL, exception message or stack. `src/error.html` is the last-resort HTML view if SvelteKit cannot render the normal error page. API consumers continue receiving JSON error responses; application page errors render HTML.

## Verification

```sh
bun run test:web
bun --env-file=apps/api/.env apps/api/tests/session-lifecycle-smoke.ts
```

The integration test is restricted to localhost and the dedicated `paspapan_rewrite` database. It creates isolated users, manipulates only their session deadlines, verifies idle and absolute expiry, origin enforcement, activity renewal, revocation and clearing cookies, then removes its fixtures.
