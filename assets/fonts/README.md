# Local typography

Plus Jakarta Sans by Tokotype is the only application typeface. No Google Fonts
CSS, CDN requests, installed-system font lookup, or browser-specific font stack.

- Web: Fontsource Variable 5.3.0, Latin normal and italic WOFF2, weights 200–800.
  Sources: `@fontsource-variable/plus-jakarta-sans/files/`.
- Brand exports: `PlusJakartaSans.ttf` from Google Fonts' `ofl/plusjakartasans`.
  Source: https://github.com/google/fonts/tree/main/ofl/plusjakartasans
- License: `OFL.txt`; preserve it when redistributing.

WOFF2 files are served from each application's `/fonts/` directory. The primary
face is preloaded; the PWA caches both faces for the offline screen. Font failure
is a test failure, not a reason to silently choose a system font. Unsupported
characters may still require the browser's glyph fallback.

SVG wordmarks use local-font outlines so they render without a font installed.
