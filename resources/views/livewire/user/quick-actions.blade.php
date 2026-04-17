@php
    $user = Auth::user();
    $hasSubordinates = $user->subordinates->isNotEmpty();

    $primaryItems = [
        [
            'kind' => 'link',
            'href' => route('attendance-history'),
            'label' => __('History'),
            'description' => __('Review attendance records.'),
            'icon' => 'history',
            'tone' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-200',
        ],
        [
            'kind' => 'link',
            'href' => route('apply-leave'),
            'label' => __('Leave'),
            'description' => __('Send leave requests.'),
            'icon' => 'leave',
            'tone' => 'bg-teal-100 text-teal-700 dark:bg-teal-950/40 dark:text-teal-200',
        ],
        [
            'kind' => 'link',
            'href' => route('reimbursement'),
            'label' => __('Claim'),
            'description' => __('Submit reimbursement.'),
            'icon' => 'reimbursement',
            'tone' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-200',
        ],
        [
            'kind' => 'link',
            'href' => route('overtime'),
            'label' => __('Overtime'),
            'description' => __('Track overtime requests.'),
            'icon' => 'clock',
            'tone' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-200',
        ],
        [
            'kind' => 'link',
            'href' => route('my-schedule'),
            'label' => __('My Schedule'),
            'description' => __('Check shifts and work hours.'),
            'icon' => 'calendar',
            'tone' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-200',
        ],
    ];

    $moreItems = [
        [
            'kind' => \App\Helpers\Editions::attendanceLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::attendanceLocked() ? null : route('face.enrollment'),
            'label' => __('Face ID'),
            'description' => __('Manage face verification.'),
            'icon' => 'face',
            'tone' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-200',
            'locked' => \App\Helpers\Editions::attendanceLocked(),
            'lockTitle' => 'Face ID Locked',
            'lockMessage' => 'Face ID Biometrics is an Enterprise Feature 🔒. Please Upgrade.',
        ],
        [
            'kind' => \App\Helpers\Editions::payrollLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::payrollLocked() ? null : route('my-payslips'),
            'label' => __('Payslip'),
            'description' => __('Open salary statements.'),
            'icon' => 'payslip',
            'tone' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200',
            'locked' => \App\Helpers\Editions::payrollLocked(),
            'lockTitle' => 'Payroll Locked',
            'lockMessage' => 'Payroll Access is an Enterprise Feature 🔒. Please Upgrade.',
        ],
        [
            'kind' => \App\Helpers\Editions::payrollLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::payrollLocked() ? null : route('my-kasbon'),
            'label' => __('Kasbon'),
            'description' => __('Track cash advance requests.'),
            'icon' => 'kasbon',
            'tone' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-200',
            'locked' => \App\Helpers\Editions::payrollLocked(),
            'lockTitle' => 'Kasbon Locked',
            'lockMessage' => 'Kasbon Access is an Enterprise Feature 🔒. Please Upgrade.',
        ],
        [
            'kind' => \App\Helpers\Editions::assetLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::assetLocked() ? null : route('my-assets'),
            'label' => __('Assets'),
            'description' => __('Review assigned company assets.'),
            'icon' => 'assets',
            'tone' => 'bg-stone-100 text-stone-700 dark:bg-stone-900/50 dark:text-stone-200',
            'locked' => \App\Helpers\Editions::assetLocked(),
            'lockTitle' => __('Assets Locked'),
            'lockMessage' => __('Company Asset Management is an Enterprise Feature') . ' 🔒. ' . __('Please Upgrade.'),
        ],
        [
            'kind' => \App\Helpers\Editions::appraisalLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::appraisalLocked() ? null : route('my-performance'),
            'label' => __('Performance'),
            'description' => __('Check KPI and appraisal results.'),
            'icon' => 'performance',
            'tone' => 'bg-lime-100 text-lime-700 dark:bg-lime-950/40 dark:text-lime-200',
            'locked' => \App\Helpers\Editions::appraisalLocked(),
            'lockTitle' => __('Performance Locked'),
            'lockMessage' =>
                __('KPI & Performance Appraisal is an Enterprise Feature') . ' 🔒. ' . __('Please Upgrade.'),
        ],
        [
            'kind' => 'link',
            'href' => route('profile.show'),
            'label' => __('Profile'),
            'description' => __('Update account and security.'),
            'icon' => 'profile',
            'tone' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            'locked' => false,
        ],
        [
            'kind' => 'form',
            'action' => route('logout'),
            'label' => __('Log Out'),
            'description' => __('Sign out from your account.'),
            'icon' => 'logout',
            'tone' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-200',
            'locked' => false,
        ],
    ];

    if ($hasSubordinates) {
        array_splice($moreItems, 1, 0, [
            [
                'kind' => 'link',
                'href' => route('approvals'),
                'label' => __('Approvals'),
                'description' => __('Review team requests.'),
                'icon' => 'approvals',
                'tone' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-200',
                'locked' => false,
            ],
        ]);

        $moreItems[] = [
            'kind' => \App\Helpers\Editions::payrollLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::payrollLocked() ? null : route('team-kasbon'),
            'label' => __('Team Kasbon'),
            'description' => __('Follow team cash advance requests.'),
            'icon' => 'team',
            'tone' => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-200',
            'locked' => \App\Helpers\Editions::payrollLocked(),
            'lockTitle' => 'Team Kasbon Locked',
            'lockMessage' => 'Team Kasbon Access is an Enterprise Feature 🔒. Please Upgrade.',
        ];
    }
@endphp

<div x-data="{ showMore: false }" class="space-y-6" aria-label="{{ __('User shortcuts') }}">
    <section class="quick-wallet-surface" aria-labelledby="quick-wallet-title">
        <div class="quick-wallet-header">
            <div>
                <p class="quick-wallet-badge">{{ __('Quick Access') }}</p>
                <h3 id="quick-wallet-title" class="quick-wallet-title">{{ __('Start With Today’s Priorities') }}</h3>
                <p class="quick-wallet-copy">
                    {{ __('Use the fastest actions first, then open the rest only when you need them.') }}</p>
            </div>
        </div>

        <ul class="quick-wallet-grid" role="list">
            @foreach ($primaryItems as $item)
                <li>
                    <a href="{{ $item['href'] }}" class="quick-wallet-action"
                        aria-describedby="primary-{{ $loop->index }}">
                        <div class="quick-wallet-action__icon {{ $item['tone'] }}">
                            <x-user.quick-menu-icon :name="$item['icon']" />
                        </div>
                        <div class="quick-wallet-action__label">{{ $item['label'] }}</div>
                        <p id="primary-{{ $loop->index }}" class="quick-wallet-action__description">
                            {{ $item['description'] }}</p>
                    </a>
                </li>
            @endforeach

            <li>
                <button type="button" class="quick-wallet-action" :aria-expanded="showMore.toString()"
                    aria-haspopup="dialog" aria-controls="quick-access-more-panel" @click="showMore = !showMore">
                    <div
                        class="quick-wallet-action__icon bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-200">
                        <x-user.quick-menu-icon name="more" />
                    </div>
                    <div class="quick-wallet-action__label">{{ __('More') }}</div>
                    <p class="quick-wallet-action__description">{{ __('Open the rest of your services.') }}</p>
                </button>
            </li>
        </ul>
    </section>

    <div id="quick-access-more-panel" x-cloak x-show="showMore" x-trap.inert.noscroll="showMore"
        x-on:keydown.escape.window="showMore = false" class="quick-wallet-modal" role="dialog" aria-modal="true"
        aria-labelledby="quick-access-more-title" style="display: none;">
        <div class="quick-wallet-modal__backdrop" x-on:click="showMore = false"></div>

        <div class="relative flex min-h-full items-end justify-center sm:items-center" x-show="showMore"
            x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="quick-wallet-modal__panel" x-show="showMore" x-transition:enter="ease-out duration-200"
                x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100"
                x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95">
                <div class="quick-wallet-modal__header">
                    <div>
                        <h4 id="quick-access-more-title" class="quick-wallet-modal__title">{{ __('More Menu') }}</h4>
                        <p class="quick-wallet-modal__copy">
                            {{ __('Extra services stay here without pushing the page downward.') }}</p>
                    </div>
                    <button type="button" class="quick-wallet-modal__close" @click="showMore = false">
                        {{ __('Close') }}
                    </button>
                </div>

                <div class="quick-wallet-modal__body">
                    <ul class="quick-wallet-more-grid" role="list">
                        @foreach ($moreItems as $item)
                            <li>
                                @if ($item['kind'] === 'link')
                                    <a href="{{ $item['href'] }}" class="quick-wallet-more-item relative"
                                        aria-label="{{ $item['label'] }}. {{ $item['description'] }}">
                                        <div class="quick-wallet-more-item__icon {{ $item['tone'] }}">
                                            <x-user.quick-menu-icon :name="$item['icon']" class="h-5 w-5" />
                                        </div>
                                        <div class="quick-wallet-more-item__label">{{ $item['label'] }}</div>
                                        @if ($item['locked'])
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                aria-hidden="true">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 0h10.5A1.5 1.5 0 0118.75 12v6a1.5 1.5 0 01-1.5 1.5H6.75A1.5 1.5 0 015.25 18v-6a1.5 1.5 0 011.5-1.5z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </a>
                                @elseif ($item['kind'] === 'button')
                                    <button type="button" class="quick-wallet-more-item relative"
                                        aria-label="{{ $item['label'] }}. {{ $item['description'] }}"
                                        @click.prevent="$dispatch('feature-lock', { title: @js($item['lockTitle']), message: @js($item['lockMessage']) })">
                                        <div class="quick-wallet-more-item__icon {{ $item['tone'] }}">
                                            <x-user.quick-menu-icon :name="$item['icon']" class="h-5 w-5" />
                                        </div>
                                        <div class="quick-wallet-more-item__label">{{ $item['label'] }}</div>
                                        @if ($item['locked'])
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                aria-hidden="true">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 0h10.5A1.5 1.5 0 0118.75 12v6a1.5 1.5 0 01-1.5 1.5H6.75A1.5 1.5 0 015.25 18v-6a1.5 1.5 0 011.5-1.5z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                @else
                                    <form method="POST" action="{{ $item['action'] }}">
                                        @csrf
                                        <button type="submit" class="quick-wallet-more-item relative"
                                            aria-label="{{ $item['label'] }}. {{ $item['description'] }}">
                                            <div class="quick-wallet-more-item__icon {{ $item['tone'] }}">
                                                <x-user.quick-menu-icon :name="$item['icon']" class="h-5 w-5" />
                                            </div>
                                            <div class="quick-wallet-more-item__label">{{ $item['label'] }}</div>
                                            @if ($item['locked'])
                                                <span
                                                    class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                    aria-hidden="true">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="1.8"
                                                            d="M16.5 10.5V7.875a4.5 4.5 0 10-9 0V10.5m-.75 0h10.5A1.5 1.5 0 0118.75 12v6a1.5 1.5 0 01-1.5 1.5H6.75A1.5 1.5 0 015.25 18v-6a1.5 1.5 0 011.5-1.5z" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
