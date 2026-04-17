@props(['attendance'])

<section aria-labelledby="attendance-finished-title" class="attendance-panel">
    <div class="attendance-panel__header">
        <div>
            <p class="attendance-panel__eyebrow">{{ __('Attendance') }}</p>
            <h2 id="attendance-finished-title" class="attendance-panel__title">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </h2>
            <p class="attendance-panel__copy">
                {{ __('Today\'s attendance has been recorded completely.') }}
            </p>
        </div>

        <div class="attendance-panel__badge attendance-panel__badge--done" role="status" aria-live="polite">
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </span>
            <span>{{ __('Finished') }}</span>
        </div>
    </div>

    <div class="attendance-panel__stats" role="list" aria-label="{{ __('Today attendance times') }}">
        <article class="attendance-panel__stat" role="listitem">
            <div class="attendance-panel__stat-head">
                <span class="attendance-panel__stat-indicator attendance-panel__stat-indicator--in">
                    <span class="attendance-panel__stat-dot"></span>
                </span>
                <span class="attendance-panel__stat-label">{{ __('Check In') }}</span>
            </div>
            <div class="attendance-panel__time">
                {{ $attendance?->time_in ? \Carbon\Carbon::parse($attendance->time_in)->format('H:i') : '--:--' }}
            </div>
        </article>

        <article class="attendance-panel__stat" role="listitem">
            <div class="attendance-panel__stat-head">
                <span class="attendance-panel__stat-indicator attendance-panel__stat-indicator--out">
                    <span class="attendance-panel__stat-dot"></span>
                </span>
                <span class="attendance-panel__stat-label">{{ __('Check Out') }}</span>
            </div>
            <div class="attendance-panel__time">
                {{ $attendance?->time_out ? \Carbon\Carbon::parse($attendance->time_out)->format('H:i') : '--:--' }}
            </div>
        </article>
    </div>

    <div class="attendance-panel__summary">
        <div class="attendance-panel__summary-icon">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div>
            <h3 class="attendance-panel__summary-title">{{ __('Good job, you\'re done!') }}</h3>
            <p class="attendance-panel__summary-copy">
                {{ __('You have successfully completed today\'s attendance.') }}
            </p>
        </div>
    </div>
</section>
