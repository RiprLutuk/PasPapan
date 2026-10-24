# PasPapan visual identity

Direction: **dua arah, satu tempat**. Evolve the original paired blue/green flowing
forms, rather than replacing them with a generic initial or an unrelated mascot.
This is a visual interpretation of presence, collaboration and orderly work, not a
claim about a formally registered brand or a machine-readable QR code.

## What stays / what changes

- Keep the blue/green pair and opposing diagonal movement from the original logo.
- Simplify the flowing forms into two connected, solid-color strokes with an open
  center. Remove gradients, hairlines and decorative QR pixels that disappear at 16px.
- Keep scan framing as a secondary visual vocabulary, not a fake working QR code.
- Preserve the compact workspace and existing green actions. Blue identifies the
  brand; it does not replace the application's success/error/status colors.
- Wordmark spelling is always `PasPapan`, never `Paspapan` or all caps in navigation.

## Tokens

| Role | Color |
| --- | --- |
| Mark blue | `#2183b0` |
| Mark green | `#57944a` |
| Accessible green action | `#44733a` |
| Strong text | `#1e331a` |
| Canvas | `#f6f8f6` |
| Border | `#dde4df` |

Wordmark: bold compact sans serif, slightly tightened tracking. The UI keeps its
existing system sans stack; no remote font or network dependency is introduced.
Exports use Arial/sans-serif. Monochrome SVG uses `currentColor` for print/stamps.
Do not use the brighter mark colors for small body text on white.

## Assets and ownership

`mark.svg` is the editable master. Never edit generated PNGs by hand.

```sh
bun run brand:build
bun run brand:check
```

The generator uses the existing Playwright Chromium dependency. If it is missing,
run `bunx playwright install chromium` first. Export destinations:

- `apps/web/static/brand/`: Svelte favicon, PWA tiles, mark, wordmark and share card.
- `public/brand/`: equivalent exports for retained Laravel pages.
- `public/images/icons/`: compatibility aliases for existing PDF/email/manifest consumers.
- `public/hero-banner.png`: README/share image, rendered from the same mark.

Use the full-color mark on a light surface; use the inverse variant on dark surfaces.
Keep clear space of at least 1/8 of the mark width. Do not stretch, crop, shadow or
rotate the mark. At 16–32px use the favicon tile, without a wordmark or fine detail.
Maskable PWA exports keep the artwork inside the central safe zone and have a solid
full-bleed background. Apple touch icons are 180×180; PWA PNGs are truly 192/512px
(the previous files labelled 512px were actually 2020×2064).

Installed PWAs may retain their old launcher icon until the browser refreshes the
manifest or the app is reinstalled. No app/OS cache is forcibly erased by the UI.
