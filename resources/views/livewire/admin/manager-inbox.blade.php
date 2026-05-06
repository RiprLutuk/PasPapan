<div>
    <x-admin.page-shell :title="__('Manager Inbox')" :description="__('Review and process pending approvals across all modules.')">
        <div class="space-y-4">
            @php
                $tabConfigs = [
                    'leaves' => ['label' => __('Leaves'), 'icon' => 'heroicon-o-calendar', 'color' => 'blue'],
                    'overtime' => ['label' => __('Overtime'), 'icon' => 'heroicon-o-clock', 'color' => 'indigo'],
                    'attendance_corrections' => ['label' => __('Corrections'), 'icon' => 'heroicon-o-adjustments-horizontal', 'color' => 'orange'],
                    'reimbursements' => ['label' => __('Reimbursements'), 'icon' => 'heroicon-o-banknotes', 'color' => 'green'],
                    'cash_advances' => ['label' => __('Cash Advances'), 'icon' => 'heroicon-o-currency-dollar', 'color' => 'emerald'],
                    'shift_swaps' => ['label' => __('Shift Swaps'), 'icon' => 'heroicon-o-arrows-right-left', 'color' => 'purple'],
                    'document_requests' => ['label' => __('Documents'), 'icon' => 'heroicon-o-document-text', 'color' => 'gray'],
                ];

                $tabs = array_intersect_key($tabConfigs, array_flip($availableTabs ?? []));
                $activeTabId = 'manager-inbox-tab-'.str_replace('_', '-', $activeTab);
            @endphp

            <nav
                class="flex gap-1.5 overflow-x-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden dark:border-gray-700 dark:bg-gray-800 sm:flex-wrap sm:overflow-visible"
                role="tablist"
                aria-label="{{ __('Manager Inbox') }}"
            >
                @foreach($tabs as $key => $tab)
                    @php
                        $count = $this->counts[$key] ?? 0;
                        $isActive = $activeTab === $key;
                        $tabId = 'manager-inbox-tab-'.str_replace('_', '-', $key);
                        $statusLabel = $count > 0 ? $count.' '.__('pending') : __('Caught up');
                        
                        $iconColors = match($tab['color']) {
                            'blue' => 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30',
                            'indigo' => 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30',
                            'orange' => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30',
                            'green' => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30',
                            'emerald' => 'text-emerald-600 bg-emerald-100 dark:text-emerald-400 dark:bg-emerald-900/30',
                            'purple' => 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-purple-900/30',
                            'gray' => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-700',
                            default => 'text-primary-600 bg-primary-100 dark:text-primary-400 dark:bg-primary-900/30'
                        };
                        
                        $inactiveIconColor = 'text-gray-400 bg-gray-100 dark:text-gray-500 dark:bg-gray-800/50';
                    @endphp
                    <button
                        type="button"
                        wire:click="switchTab('{{ $key }}')"
                        id="{{ $tabId }}"
                        role="tab"
                        aria-controls="manager-inbox-panel"
                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                        aria-label="{{ $tab['label'] }}: {{ $statusLabel }}"
                        class="wcag-touch-target inline-flex shrink-0 items-center gap-2 rounded-lg px-2.5 py-1.5 text-sm font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 sm:px-3
                        {{ $isActive
                            ? 'bg-primary-700 text-white shadow-sm dark:bg-primary-500 dark:text-white'
                            : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 hover:text-gray-950 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700 dark:hover:text-white' }}"
                    >
                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-md {{ $count > 0 || $isActive ? $iconColors : $inactiveIconColor }}" aria-hidden="true">
                            @svg($tab['icon'], 'w-4 h-4')
                        </span>
                        <span class="whitespace-nowrap">{{ $tab['label'] }}</span>
                        @if($count > 0)
                            <span class="ml-0.5 inline-flex min-w-6 items-center justify-center rounded-full bg-rose-500 px-1.5 py-0.5 text-xs font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800" aria-hidden="true">
                                {{ $count > 99 ? '99+' : $count }}
                            </span>
                        @endif
                        <span class="sr-only">{{ $statusLabel }}</span>
                    </button>
                @endforeach
            </nav>

            <!-- List Content -->
            <div
                id="manager-inbox-panel"
                role="tabpanel"
                aria-labelledby="{{ $activeTabId }}"
                tabindex="0"
                class="space-y-4"
            >
                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $tabs[$activeTab]['label'] ?? __('Items') }}</h2>
                    <div class="relative w-full sm:w-72">
                        <x-forms.input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee...') }}" class="w-full text-sm pl-10 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm" />
                        <span class="absolute left-3 top-2.5 text-gray-400">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </span>
                    </div>
                </div>

                @if($items->isEmpty())
                    <x-admin.empty-state :title="__('Inbox Zero!')" :description="__('You have caught up with all your pending requests.')" :framed="true" class="border-dashed">
                        <x-slot name="icon">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-50 dark:bg-success-900/20">
                                <x-heroicon-o-check-badge class="h-6 w-6 text-success-500" />
                            </div>
                        </x-slot>
                    </x-admin.empty-state>
                @else
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($items as $item)
                            @php
                                $detailRoute = match($activeTab) {
                                    'leaves' => route('admin.leaves'),
                                    'overtime' => route('admin.overtime'),
                                    'attendance_corrections' => route('admin.attendance-corrections'),
                                    'reimbursements' => route('admin.reimbursements'),
                                    'cash_advances' => route('admin.manage-kasbon'),
                                    'shift_swaps' => route('admin.shift-swaps'),
                                    'document_requests' => route('admin.document-requests'),
                                    default => '#'
                                };

                                $accentColor = match($tabs[$activeTab]['color']) {
                                    'blue' => 'bg-blue-500',
                                    'indigo' => 'bg-indigo-500',
                                    'orange' => 'bg-orange-500',
                                    'green' => 'bg-green-500',
                                    'emerald' => 'bg-emerald-500',
                                    'purple' => 'bg-purple-500',
                                    'gray' => 'bg-gray-500',
                                    default => 'bg-primary-500'
                                };
                            @endphp
                            <div class="group relative flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600" wire:key="item-{{ $item->id }}">
                                <!-- Top Accent Bar -->
                                <div class="h-1 w-full {{ $accentColor }}"></div>
                            
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->user->profile_photo_url }}" alt="{{ $item->user->name }}" class="h-10 w-10 rounded-full object-cover shadow-sm ring-2 ring-white dark:ring-gray-800">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="truncate text-base font-bold text-gray-900 dark:text-white">{{ $item->user->name }}</h3>
                                        <p class="truncate text-xs font-medium text-gray-500">{{ $item->user->jobTitle?->name ?? 'Employee' }}</p>
                                    </div>
                                    
                                    <a href="{{ $detailRoute }}" class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors dark:bg-gray-800 dark:hover:bg-gray-700" title="{{ __('View Module') }}">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                                    </a>
                                </div>
                                
                                <div class="mt-3 flex-1 rounded-lg bg-gray-50/50 p-3 dark:bg-gray-900/20 border border-gray-100 dark:border-gray-700/50">
                                    <div class="flex flex-col gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        @if($activeTab === 'leaves')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Leave Type') }}</span> <span class="font-bold text-primary-600 dark:text-primary-400">{{ $item->leaveType->name }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</span></div>
                                            @if($item->reason)<div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->reason }}</div>@endif
                                        
                                        @elseif($activeTab === 'overtime')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->date)->translatedFormat('d M Y') }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Time') }}</span> <span>{{ \Carbon\Carbon::parse($item->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->end_time)->format('H:i') }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Duration') }}</span> <span class="rounded-md bg-indigo-50 px-2 py-0.5 font-bold text-indigo-600 dark:bg-indigo-900/20 dark:text-indigo-400">{{ $item->duration_text }}</span></div>
                                        
                                        @elseif($activeTab === 'attendance_corrections')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Type') }}</span> <span class="font-bold text-orange-600 dark:text-orange-400">{{ $item->requestTypeLabel() }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->attendance_date)->translatedFormat('d M Y') }}</span></div>
                                        
                                        @elseif($activeTab === 'reimbursements')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Type') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ ucfirst($item->type) }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Amount') }}</span> <span class="font-bold text-green-600 dark:text-green-400 text-lg">{{ \Illuminate\Support\Number::currency($item->amount, 'IDR', 'id') }}</span></div>
                                        
                                        @elseif($activeTab === 'cash_advances')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Amount') }}</span> <span class="font-bold text-emerald-600 dark:text-emerald-400 text-lg">{{ \Illuminate\Support\Number::currency($item->amount, 'IDR', 'id') }}</span></div>
                                            <div class="mt-2 text-xs text-gray-500 italic border-t border-gray-100 dark:border-gray-700 pt-2">{{ $item->purpose }}</div>
                                        
                                        @elseif($activeTab === 'shift_swaps')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Date') }}</span> <span>{{ \Carbon\Carbon::parse($item->schedule->date)->translatedFormat('d M Y') }}</span></div>
                                            <div class="flex justify-between items-center mt-2 p-2 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700">
                                                <span class="text-xs font-semibold text-gray-500">{{ $item->schedule->shift->name }}</span>
                                                <x-heroicon-o-arrow-right class="h-3 w-3 text-gray-400 mx-2" />
                                                <span class="text-xs font-bold text-purple-600 dark:text-purple-400">{{ $item->requestedShift->name }}</span>
                                            </div>
                                        
                                        @elseif($activeTab === 'document_requests')
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Document') }}</span> <span class="font-bold text-gray-900 dark:text-white">{{ $item->documentTypeLabel() }}</span></div>
                                            <div class="flex justify-between items-center"><span class="text-xs text-gray-400">{{ __('Due Date') }}</span> <span class="{{ $item->due_date && \Carbon\Carbon::parse($item->due_date)->isPast() ? 'text-danger-600 font-bold' : '' }}">{{ $item->due_date ? \Carbon\Carbon::parse($item->due_date)->translatedFormat('d M Y') : 'N/A' }}</span></div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-px bg-gray-200 dark:bg-gray-700">
                                @if($activeTab !== 'document_requests')
                                    <button wire:click="confirmReject({{ $item->id }})" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-danger-600 transition-colors hover:bg-danger-50 hover:text-danger-700 focus:outline-none dark:bg-gray-800 dark:text-danger-400 dark:hover:bg-danger-900/20">
                                        <x-heroicon-m-x-mark class="h-5 w-5" /> {{ __('Reject') }}
                                    </button>
                                    <button wire:click="approve({{ $item->id }})" class="flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-success-600 transition-colors hover:bg-success-50 hover:text-success-700 focus:outline-none dark:bg-gray-800 dark:text-success-400 dark:hover:bg-success-900/20">
                                        <x-heroicon-m-check class="h-5 w-5" /> {{ __('Approve') }}
                                    </button>
                                @else
                                    <a href="{{ $detailRoute }}" class="col-span-2 flex items-center justify-center gap-2 bg-white px-3 py-2.5 text-sm font-semibold text-primary-600 transition-colors hover:bg-primary-50 hover:text-primary-700 focus:outline-none dark:bg-gray-800 dark:text-primary-400 dark:hover:bg-primary-900/20">
                                        <x-heroicon-o-arrow-top-right-on-square class="h-5 w-5" /> {{ __('Open Document Requests') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($items->hasPages())
                <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                    {{ $items->links() }}
                </div>
            @endif
            </div>
        </div>

        <!-- Rejection Modal -->
        <x-overlays.dialog-modal wire:model="confirmingRejection">
            <x-slot name="title">{{ __('Reject Request') }}</x-slot>
            <x-slot name="content">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Are you sure you want to reject this request? Please provide a reason.') }}</p>
                <div class="mt-4">
                    <x-forms.label value="{{ __('Rejection Reason') }}" />
                    <x-forms.textarea wire:model="rejectionReason" class="mt-1 w-full" rows="3" placeholder="{{ __('Optional') }}" />
                </div>
            </x-slot>
            <x-slot name="footer">
                <x-actions.secondary-button wire:click="cancelReject">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button wire:click="reject" variant="danger" class="ml-2">{{ __('Reject Request') }}</x-actions.button>
            </x-slot>
        </x-overlays.dialog-modal>
    </x-admin.page-shell>
</div>
