@php
    $user = Auth::user();
    $canReviewSubordinateRequests = $user->can('reviewSubordinateRequests');
    $hasFaceRegistered = $user->hasFaceRegistered();
    $canRequestKasbon = (float) ($user->basic_salary ?? 0) > 0;
    $cashAdvanceLocked = \App\Helpers\Editions::cashAdvanceLocked();

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
            'href' => route('attendance-corrections'),
            'label' => __('Correction'),
            'description' => __('Fix missing or wrong attendance.'),
            'icon' => 'correction',
            'tone' => 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-200',
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
    ];

    $moreItems = [
        [
            'kind' => 'link',
            'href' => route('my-schedule'),
            'label' => __('My Schedule'),
            'description' => __('Check shifts and work hours.'),
            'icon' => 'calendar',
            'tone' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-200',
            'locked' => false,
            'completed' => false,
        ],
        [
            'kind' => 'link',
            'href' => route('shift-swap-requests'),
            'label' => __('Shift Swap'),
            'description' => __('Request schedule changes.'),
            'icon' => 'swap',
            'tone' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-200',
            'locked' => false,
            'completed' => false,
        ],
        [
            'kind' => 'link',
            'href' => route('document-requests'),
            'label' => __('Documents'),
            'description' => __('Request HR letters.'),
            'icon' => 'document',
            'tone' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200',
            'locked' => false,
            'completed' => false,
        ],
        [
            'kind' => \App\Helpers\Editions::attendanceLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::attendanceLocked() ? null : route('face.enrollment'),
            'label' => __('Face ID'),
            'description' => $hasFaceRegistered ? __('Face ID is ready to use.') : __('Manage face verification.'),
            'icon' => 'face',
            'tone' => $hasFaceRegistered
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200'
                : 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-200',
            'locked' => \App\Helpers\Editions::attendanceLocked(),
            'completed' => $hasFaceRegistered,
            'lockTitle' => __('Face ID Locked'),
            'lockMessage' => __('Face ID Biometrics is an Enterprise Feature. Please Upgrade.'),
        ],
        [
            'kind' => \App\Helpers\Editions::payrollLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::payrollLocked() ? null : route('my-payslips'),
            'label' => __('Payslip'),
            'description' => __('Open salary statements.'),
            'icon' => 'payslip',
            'tone' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-200',
            'locked' => \App\Helpers\Editions::payrollLocked(),
            'completed' => false,
            'lockTitle' => __('Payroll Locked'),
            'lockMessage' => __('Payroll Access is an Enterprise Feature. Please Upgrade.'),
        ],
        [
            'kind' => $cashAdvanceLocked ? 'button' : (!$canRequestKasbon ? 'disabled' : 'link'),
            'href' => ($cashAdvanceLocked || !$canRequestKasbon) ? null : route('my-kasbon'),
            'label' => __('Kasbon'),
            'description' => __('Track cash advance requests.'),
            'icon' => 'kasbon',
            'tone' => !$canRequestKasbon
                ? 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500'
                : 'bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-200',
            'locked' => $cashAdvanceLocked,
            'disabled' => !$canRequestKasbon && ! $cashAdvanceLocked,
            'completed' => false,
            'lockTitle' => __('Kasbon Locked'),
            'lockMessage' => $cashAdvanceLocked
                ? __('Kasbon Access is an Enterprise Feature. Please Upgrade.')
                : null,
            'disabledMessage' => __('Kasbon is available after your basic salary has been updated.'),
        ],
        [
            'kind' => \App\Helpers\Editions::assetLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::assetLocked() ? null : route('my-assets'),
            'label' => __('Assets'),
            'description' => __('Review assigned company assets.'),
            'icon' => 'assets',
            'tone' => 'bg-stone-100 text-stone-700 dark:bg-stone-900/50 dark:text-stone-200',
            'locked' => \App\Helpers\Editions::assetLocked(),
            'completed' => false,
            'lockTitle' => __('Assets Locked'),
            'lockMessage' => __('Company Asset Management is an Enterprise Feature. Please Upgrade.'),
        ],
        [
            'kind' => \App\Helpers\Editions::appraisalLocked() ? 'button' : 'link',
            'href' => \App\Helpers\Editions::appraisalLocked() ? null : route('my-performance'),
            'label' => __('Performance'),
            'description' => __('Check KPI and appraisal results.'),
            'icon' => 'performance',
            'tone' => 'bg-lime-100 text-lime-700 dark:bg-lime-950/40 dark:text-lime-200',
            'locked' => \App\Helpers\Editions::appraisalLocked(),
            'completed' => false,
            'lockTitle' => __('Performance Locked'),
            'lockMessage' => __('KPI & Performance Appraisal is an Enterprise Feature. Please Upgrade.'),
        ],
        [
            'kind' => 'link',
            'href' => route('profile.show'),
            'label' => __('Profile'),
            'description' => __('Update account and security.'),
            'icon' => 'profile',
            'tone' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            'locked' => false,
            'completed' => false,
        ],
        [
            'kind' => 'form',
            'action' => route('logout'),
            'label' => __('Log Out'),
            'description' => __('Sign out from your account.'),
            'icon' => 'logout',
            'tone' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-200',
            'locked' => false,
            'completed' => false,
        ],
    ];

    if ($canReviewSubordinateRequests) {
        array_splice($moreItems, 1, 0, [
            [
                'kind' => 'link',
                'href' => route('approvals'),
                'label' => __('Approvals'),
                'description' => __('Review team requests.'),
                'icon' => 'approvals',
                'tone' => 'bg-blue-100 text-blue-700 dark:bg-blue-950/40 dark:text-blue-200',
                'locked' => false,
                'completed' => false,
            ],
        ]);

        $moreItems[] = [
            'kind' => $cashAdvanceLocked ? 'button' : 'link',
            'href' => $cashAdvanceLocked ? null : route('team-kasbon'),
            'label' => __('Team Kasbon'),
            'description' => __('Follow team cash advance requests.'),
            'icon' => 'team',
            'tone' => 'bg-green-100 text-green-700 dark:bg-green-950/40 dark:text-green-200',
            'locked' => $cashAdvanceLocked,
            'completed' => false,
            'lockTitle' => __('Team Kasbon Locked'),
            'lockMessage' => __('Team Kasbon Access is an Enterprise Feature. Please Upgrade.'),
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
                                        @if ($item['completed'] ?? false)
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                aria-label="{{ __('Registered') }}">
                                                <x-heroicon-o-check class="h-3.5 w-3.5" />
                                            </span>
                                        @endif
                                        @if ($item['locked'])
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                aria-hidden="true">
                                                <x-heroicon-o-lock-closed class="h-3.5 w-3.5" />
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
                                        @if ($item['completed'] ?? false)
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                aria-label="{{ __('Registered') }}">
                                                <x-heroicon-o-check class="h-3.5 w-3.5" />
                                            </span>
                                        @endif
                                        @if ($item['locked'])
                                            <span
                                                class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                aria-hidden="true">
                                                <x-heroicon-o-lock-closed class="h-3.5 w-3.5" />
                                            </span>
                                        @endif
                                    </button>
                                @elseif ($item['kind'] === 'disabled')
                                    <button type="button" class="quick-wallet-more-item relative cursor-not-allowed opacity-70"
                                        disabled title="{{ $item['disabledMessage'] ?? $item['description'] }}"
                                        aria-label="{{ $item['label'] }}. {{ $item['disabledMessage'] ?? $item['description'] }}">
                                        <div class="quick-wallet-more-item__icon {{ $item['tone'] }}">
                                            <x-user.quick-menu-icon :name="$item['icon']" class="h-5 w-5" />
                                        </div>
                                        <div class="quick-wallet-more-item__label">{{ $item['label'] }}</div>
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
                                            @if ($item['completed'] ?? false)
                                                <span
                                                    class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-emerald-200 bg-emerald-50 text-emerald-600 shadow-sm dark:border-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                    aria-label="{{ __('Registered') }}">
                                                    <x-heroicon-o-check class="h-3.5 w-3.5" />
                                                </span>
                                            @endif
                                            @if ($item['locked'])
                                                <span
                                                    class="absolute right-3 top-3 inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                                    aria-hidden="true">
                                                    <x-heroicon-o-lock-closed class="h-3.5 w-3.5" />
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
