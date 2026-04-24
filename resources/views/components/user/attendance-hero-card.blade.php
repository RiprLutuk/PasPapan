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
                <x-heroicon-o-check class="h-3 w-3" />
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
            <x-heroicon-o-check-circle class="h-5 w-5" />
        </div>
        <div>
            <h3 class="attendance-panel__summary-title">{{ __('Good job, you\'re done!') }}</h3>
            <p class="attendance-panel__summary-copy">
                {{ __('You have successfully completed today\'s attendance.') }}
            </p>
        </div>
    </div>
</section>
