@props(['hasCheckedIn', 'hasCheckedOut', 'attendance', 'hasApprovedOvertime' => false])

<section aria-labelledby="attendance-card-title" class="attendance-panel">
    <div class="attendance-panel__header">
        <div>
            <p class="attendance-panel__eyebrow">{{ __('Attendance') }}</p>
            <h2 id="attendance-card-title" class="attendance-panel__title">
                {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
            </h2>
            <p class="attendance-panel__copy">
                {{ __('Track today\'s check-in and check-out from one place.') }}
            </p>
        </div>

        <div class="attendance-panel__badge attendance-panel__badge--live" role="status" aria-live="polite">
            <span class="relative flex h-2 w-2">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary-400 opacity-75"></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-primary-700 dark:bg-primary-300"></span>
            </span>
            <span>{{ __('Live') }}</span>
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

    @if (!$hasCheckedIn)
        <p class="attendance-panel__helper">{{ __('Ready to start your shift?') }}</p>

        <div class="attendance-panel__actions">
            <a
                href="{{ route('scan') }}"
                @mouseenter="window.prefetchAttendanceScan?.()"
                @touchstart.passive="window.prefetchAttendanceScan?.()"
                @focus="window.prefetchAttendanceScan?.()"
                class="attendance-panel__action attendance-panel__action--primary">
                <span class="attendance-panel__action-icon attendance-panel__action-icon--primary">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                <span class="attendance-panel__action-label">{{ __('Clock In') }}</span>
                <span class="attendance-panel__action-copy attendance-panel__action-copy--primary">{{ __('Start your day') }}</span>
            </a>

            <button type="button" disabled class="attendance-panel__action attendance-panel__action--disabled" aria-disabled="true">
                <span class="attendance-panel__action-icon attendance-panel__action-icon--disabled">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                <span class="attendance-panel__action-label">{{ __('Clock Out') }}</span>
                <span class="attendance-panel__action-copy attendance-panel__action-copy--disabled">{{ __('Available after check in') }}</span>
            </button>
        </div>
    @elseif (!$hasCheckedOut)
        @php
            $shiftEndTime = ($attendance && $attendance->shift)
                ? \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') . ' ' . $attendance->shift->end_time
                : null;
        @endphp

        <div x-data="shiftCountdown('{{ $shiftEndTime }}', @js((bool) $hasApprovedOvertime))" class="attendance-panel__helper">
            <template x-if="endTime && remaining > 0">
                <p>
                    {{ __('Shift ends in') }}:
                    <span class="font-mono font-bold text-primary-600 dark:text-primary-400" x-text="formatted"></span>
                </p>
            </template>
            <template x-if="endTime && remaining <= 0">
                <p
                    class="animate-pulse"
                    :class="hasApprovedOvertime ? 'text-amber-500 dark:text-amber-400' : 'text-orange-500 dark:text-orange-400'">
                    <span x-text="hasApprovedOvertime ? '{{ __('Overtime') }}' : '{{ __('Clock Out Pending') }}'"></span>
                </p>
            </template>
            <template x-if="!endTime">
                <p>{{ __('Don\'t forget to clock out when you\'re done.') }}</p>
            </template>
        </div>

@pushOnce('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('shiftCountdown', (initialEndTime, hasApprovedOvertime) => ({
            endTime: null,
            now: new Date().getTime(),
            remaining: 0,
            timer: null,
            hasApprovedOvertime,

            init() {
                if (initialEndTime) {
                    try {
                        let target = new Date(initialEndTime);
                        if (!isNaN(target.getTime())) {
                            this.endTime = target.getTime();
                            this.startTimer();
                        }
                    } catch (e) {
                        console.error('Timer init error', e);
                    }
                }
            },

            startTimer() {
                this.check();
                this.timer = setInterval(() => this.check(), 1000);
            },

            check() {
                this.now = new Date().getTime();
                this.remaining = this.endTime - this.now;
            },

            get formatted() {
                if (!this.endTime) return '--:--:--';
                if (this.remaining < 0) {
                    return this.hasApprovedOvertime ? '{{ __('Overtime') }}' : '{{ __('Clock Out Pending') }}';
                }

                let diff = this.remaining;
                let hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                let minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((diff % (1000 * 60)) / 1000);

                return String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');
            }
        }));
    });
</script>
@endpushOnce

        <div class="attendance-panel__actions">
            <button type="button" disabled class="attendance-panel__action attendance-panel__action--disabled" aria-disabled="true">
                <span class="attendance-panel__action-icon attendance-panel__action-icon--disabled">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                <span class="attendance-panel__action-label">{{ __('Clock In') }}</span>
                <span class="attendance-panel__action-copy attendance-panel__action-copy--disabled">{{ __('Already recorded') }}</span>
            </button>

            <a
                href="{{ route('scan') }}"
                @mouseenter="window.prefetchAttendanceScan?.()"
                @touchstart.passive="window.prefetchAttendanceScan?.()"
                @focus="window.prefetchAttendanceScan?.()"
                class="attendance-panel__action attendance-panel__action--accent">
                <span class="attendance-panel__action-icon attendance-panel__action-icon--accent">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </span>
                <span class="attendance-panel__action-label">{{ __('Clock Out') }}</span>
                <span class="attendance-panel__action-copy attendance-panel__action-copy--accent">{{ __('Complete today') }}</span>
            </a>
        </div>
    @endif
</section>
