# Compact workspace UI

This refactor extends the current Svelte 5 components. It does not replace API
contracts, permissions, routes or business workflows.

## Audit and implementation scope

| Area | Existing implementation | Refactor |
| --- | --- | --- |
| Login, registration, password recovery | AuthFrame, FormField, PasswordField, AlertBanner | Retain shared compact card; touch-sized password toggle and inputs; reduced-motion rules include auth |
| Navigation | AppShell, command palette, desktop sidebar, mobile dock | Restore visible keyboard focus; touch-sized search controls; deduplicate command results per URL after category filtering |
| Dashboard | AdminDashboard | Shared PageHeader and MetricCard; shorter heading copy; remove duplicated metric CSS |
| Requests and approvals | Requests | Shared header/metrics, compact filters, customer-facing attachment wording; unknown metrics use dashes on errors |
| Workforce modules | LegacyFeature, catch-all routes | Shared header/metrics, keyboard-scrollable tables, native modal focus containment/Escape, responsive dialog and touch-sized actions |
| Schedule/history/notifications | Activity | Larger touch controls and readable mobile input text while preserving dense calendar layout |
| Employees, shifts, QR/location, access, profile | Existing dedicated pages | Shared surface/control/header/filter rules; retain domain-specific forms, tables and actions |

## Foundation

- Local Plus Jakarta Sans only; Lucide icons retain accessible button names.
- Green remains the action/status accent, blue remains part of the brand mark.
- White content surfaces on a neutral canvas; flat panels and buttons. Elevation is
  reserved for dialogs and navigation overlays.
- Spacing tokens: 4, 8, 12, 16, 20, 24px (`--space-1` through `--space-6`).
- Radii: 8px controls, 12px surfaces, 16px overlays. Reuse existing tokens.
- Controls default to 44px. Fine-pointer desktop controls may use 36px. Mobile
  text inputs use 16px to avoid browser zoom; do not shrink hit areas for density.
- Page headers wrap actions instead of clipping them. Form grids start with one
  column, expanding at 1024px. Dense tables scroll inside a labeled focusable region.
- Calendar cells retain seven columns; on narrow phones their width follows the
  available space, with 44px height. This is an intentional compact-calendar exception.
- Respect reduced-motion preferences globally. Do not remove focus outlines
  without an equivalent visible focus indicator.

## Reuse

`ui/PageHeader.svelte`: title, optional description/eyebrow, actions snippet.
`ui/MetricCard.svelte`: label, value, optional destination, loading/unknown state.
`ui/modal.ts`: native `showModal()` action; callers handle close state. Native
dialog supplies focus containment, background inertness and keyboard dismissal.
Existing form/alert/auth primitives remain the default for new screens.

Domain-specific content stays in its page. No new UI dependency is required.
Dark mode is not introduced by this refactor; existing UI controls do not establish
complete dark-theme coverage.

## Validation boundary

Typecheck, frontend tests and production build are required. Browser layout checks
use isolated API fixtures at 320, 390, 768, 1024 and 1440px. They verify frontend
rendering and interaction, not live backend persistence or complete feature parity.
Physical-device camera, GPS, screen-reader and full domain-workflow acceptance remain
separate checks. Existing backend capabilities must not be inferred from UI coverage.

Verified checkpoint: 22 frontend tests pass, Svelte check has zero errors/warnings,
and production build succeeds. Browser fixtures passed 75 width checks across
15 pages (auth, employee home, dashboard, requests/approvals, assets, shifts,
employees, QR locations, history, schedule, notifications and profile), plus native
modal focus/Escape, command palette rendering and unknown metrics on request failure.
Four representative pages also passed at 844×390 landscape; the module dialog stays
within the short viewport and scrolls internally.
The temporary browser harness is not an installed test dependency or a CI suite.
