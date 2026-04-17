<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#f5faf4">
        <title>@yield('title') | {{ config('app.name') }}</title>
        <style>
            :root {
                color-scheme: light dark;
                --bg: #f5faf4;
                --surface: rgba(255, 255, 255, 0.96);
                --border: rgba(87, 148, 74, 0.18);
                --text: #163020;
                --text-muted: #486253;
                --accent: #57944a;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #07110c;
                    --surface: rgba(17, 24, 39, 0.92);
                    --border: rgba(132, 193, 120, 0.2);
                    --text: #f3f7f2;
                    --text-muted: #c6d6c4;
                    --accent: #84c178;
                }
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 1rem;
                font-family: Figtree, "Segoe UI", Arial, sans-serif;
                background: var(--bg);
                color: var(--text);
            }

            .card {
                width: min(100%, 32rem);
                border: 1px solid var(--border);
                border-radius: 1.5rem;
                background: var(--surface);
                padding: 1.5rem;
                box-shadow: 0 24px 56px -36px rgba(34, 64, 41, 0.4);
            }

            .code {
                display: inline-flex;
                min-height: 2.5rem;
                align-items: center;
                justify-content: center;
                padding: 0.5rem 0.875rem;
                border-radius: 999px;
                background: rgba(87, 148, 74, 0.08);
                color: var(--accent);
                font-size: 0.875rem;
                font-weight: 800;
                letter-spacing: 0.18em;
                text-transform: uppercase;
            }

            h1 {
                margin: 1rem 0 0;
                font-size: 1.875rem;
                line-height: 1.15;
                letter-spacing: -0.03em;
            }

            p {
                margin: 0.75rem 0 0;
                color: var(--text-muted);
                line-height: 1.7;
            }
        </style>
    </head>
    <body>
        <main class="card" aria-labelledby="minimal-error-title">
            <div class="code">@yield('code')</div>
            <h1 id="minimal-error-title">@yield('message')</h1>
            <p>{{ __('The requested page is currently unavailable.') }}</p>
        </main>
    </body>
</html>
