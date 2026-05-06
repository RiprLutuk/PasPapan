<x-admin.page-shell :title="__('Announcements')" :description="__('Broadcast news and updates to all employees.')">
    <x-slot name="actions">
        <x-actions.button wire:click="create" size="icon" label="{{ __('Add Announcement') }}">
            <x-heroicon-m-plus class="h-5 w-5" />
        </x-actions.button>
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools>

            <div class="md:col-span-2 xl:col-span-6">
                <x-forms.label for="announcement-search" value="{{ __('Search announcements') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input id="announcement-search" type="search" wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search title, message, or creator...') }}" class="w-full pl-11" />
                </div>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="announcement-priority-filter" value="{{ __('Priority') }}" class="mb-1.5 block" />
                <x-forms.select id="announcement-priority-filter" wire:model.live="priorityFilter" class="w-full">
                    <option value="all">{{ __('All priorities') }}</option>
                    <option value="high">{{ __('High') }}</option>
                    <option value="normal">{{ __('Normal') }}</option>
                    <option value="low">{{ __('Low') }}</option>
                </x-forms.select>
            </div>

            <div class="xl:col-span-3">
                <x-forms.label for="announcement-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select id="announcement-status-filter" wire:model.live="statusFilter" class="w-full">
                    <option value="all">{{ __('All statuses') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="inactive">{{ __('Inactive') }}</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <!-- Content -->
    <x-admin.panel>
        @if ($announcements->isEmpty())
            <div class="px-6 py-16">
                <div class="flex flex-col items-center justify-center text-center">
                    <x-heroicon-o-megaphone class="h-12 w-12 text-gray-300 dark:text-gray-600" />
                    <h3 class="mt-4 text-lg font-bold text-gray-900 dark:text-white">{{ __('No announcements yet') }}
                    </h3>
                    <p class="mt-2 max-w-2xl text-gray-500 dark:text-gray-400">
                        {{ __('Create your first announcement to broadcast updates to all employees.') }}
                    </p>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 p-6 bg-gray-50/50 dark:bg-gray-900/20">
                @foreach ($announcements as $announcement)
                    @php
                        $styles = match($announcement->priority) {
                            'high' => ['bar' => 'bg-rose-500', 'badge_bg' => 'bg-rose-50 dark:bg-rose-900/20', 'badge_text' => 'text-rose-700 dark:text-rose-400', 'badge_ring' => 'ring-rose-600/20'],
                            'normal' => ['bar' => 'bg-sky-500', 'badge_bg' => 'bg-sky-50 dark:bg-sky-900/20', 'badge_text' => 'text-sky-700 dark:text-sky-400', 'badge_ring' => 'ring-sky-600/20'],
                            'low' => ['bar' => 'bg-slate-500', 'badge_bg' => 'bg-slate-50 dark:bg-slate-900/20', 'badge_text' => 'text-slate-700 dark:text-slate-400', 'badge_ring' => 'ring-slate-600/20'],
                            default => ['bar' => 'bg-slate-500', 'badge_bg' => 'bg-slate-50 dark:bg-slate-900/20', 'badge_text' => 'text-slate-700 dark:text-slate-400', 'badge_ring' => 'ring-slate-600/20'],
                        };
                        $isHighPriority = $announcement->priority === 'high';
                        $requiresAck = $isHighPriority && $announcement->modal_behavior === 'acknowledge';
                        $ackPercentage = $requiresAck && $totalActiveUsers > 0
                            ? round(($announcement->dismissed_by_users_count / $totalActiveUsers) * 100)
                            : 0;
                        $progressColor = $ackPercentage >= 100 ? 'bg-success-500' : 'bg-primary-500';
                        $textProgressColor = $ackPercentage >= 100 ? 'text-success-600 dark:text-success-400' : 'text-primary-600 dark:text-primary-400';
                    @endphp

                    <div class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition-all hover:-translate-y-1 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <!-- Priority Top Bar -->
                        <div class="h-1.5 w-full {{ $styles['bar'] }}"></div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $styles['badge_bg'] }} {{ $styles['badge_text'] }} {{ $styles['badge_ring'] }}">
                                            {{ __(ucfirst($announcement->priority)) }}
                                        </span>
                                        @if($requiresAck)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-400">
                                                {{ __('Wajib Baca') }}
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="font-bold text-gray-900 dark:text-white line-clamp-2" title="{{ $announcement->title }}">
                                        {{ $announcement->title }}
                                    </h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('By') }} {{ $announcement->creator?->name ?? 'System' }} &bull; {{ $announcement->publish_date->translatedFormat('d M Y') }}
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <x-forms.switch wire:click="toggleActive({{ $announcement->id }})"
                                        :checked="$announcement->is_active" size="sm" :label="__('Toggle announcement status')" />
                                </div>
                            </div>

                            <div class="mt-4 flex-1">
                                <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3">
                                    {{ Str::limit(strip_tags($announcement->content), 120) }}
                                </p>
                            </div>

                            @if($requiresAck)
                                <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700/50">
                                    <div class="flex items-center justify-between text-xs mb-1.5">
                                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('Acknowledgement Status') }}</span>
                                        <span class="font-bold {{ $textProgressColor }}">{{ $announcement->dismissed_by_users_count }}/{{ $totalActiveUsers }}</span>
                                    </div>
                                    <div class="w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700 h-1.5">
                                        <div class="h-full rounded-full {{ $progressColor }} transition-all duration-500" style="width: {{ $ackPercentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-gray-50/50 px-5 py-3 dark:border-gray-700/50 dark:bg-gray-800/50">
                            @if ($requiresAck)
                                <x-actions.button type="button" wire:click="viewStatus({{ $announcement->id }})" variant="soft-primary" size="sm">
                                    <x-heroicon-m-users class="mr-1.5 h-4 w-4" /> {{ __('Status') }}
                                </x-actions.button>
                            @endif
                            <x-actions.button type="button" wire:click="edit({{ $announcement->id }})" variant="soft-secondary" size="sm">
                                <x-heroicon-m-pencil-square class="mr-1.5 h-4 w-4" /> {{ __('Edit') }}
                            </x-actions.button>
                            <x-actions.button type="button" wire:click="delete({{ $announcement->id }})" wire:confirm="{{ __('Are you sure?') }}" variant="soft-danger" size="sm">
                                <x-heroicon-m-trash class="h-4 w-4" />
                            </x-actions.button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div
                class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                {{ $announcements->links() }}
            </div>
        @endif
    </x-admin.panel>

    <!-- Modal -->
    <x-overlays.dialog-modal wire:model="showModal">
        <x-slot name="title">
            {{ $editMode ? __('Edit Announcement') : __('New Announcement') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="save">
                <div class="space-y-4">
                    <div>
                        <x-forms.label for="title" value="{{ __('Title') }}" />
                        <x-forms.input id="title" type="text" class="mt-1 block w-full" wire:model="title"
                            required />
                        <x-forms.input-error for="title" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="content" value="{{ __('Content') }}" />
                        <x-forms.textarea wire:model="content" rows="4" class="mt-1 block w-full" required />
                        <x-forms.input-error for="content" class="mt-2" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="priority" value="{{ __('Priority') }}" />
                            <x-forms.select id="priority" wire:model.live="priority" class="mt-1 block w-full">
                                <option value="low">{{ __('Low') }}</option>
                                <option value="normal">{{ __('Normal') }}</option>
                                <option value="high">{{ __('High') }}</option>
                            </x-forms.select>
                        </div>
                        <div class="flex items-center gap-2 pt-6">
                            <x-forms.checkbox id="is_active" wire:model="is_active" />
                            <x-forms.label for="is_active" value="{{ __('Active') }}" />
                        </div>
                    </div>
                    @if ($priority === 'high')
                        <div>
                            <x-forms.label for="modal_behavior" value="{{ __('High Priority Modal Behavior') }}" />
                            <x-forms.select id="modal_behavior" wire:model="modal_behavior"
                                class="mt-1 block w-full">
                                <option value="once">{{ __('Show Once') }}</option>
                                <option value="acknowledge">{{ __('Require Confirmation') }}</option>
                            </x-forms.select>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Show Once: the modal appears one time per user. Require Confirmation: it keeps appearing on user pages until they press the confirmation button or the announcement expires.') }}
                            </p>
                        </div>
                    @endif
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="publish_date" value="{{ __('Publish Date') }}" />
                            <x-forms.input id="publish_date" type="date" class="mt-1 block w-full"
                                wire:model="publish_date" required />
                        </div>
                        <div>
                            <x-forms.label for="expire_date" value="{{ __('Expire Date') }} (Optional)" />
                            <x-forms.input id="expire_date" type="date" class="mt-1 block w-full"
                                wire:model="expire_date" />
                        </div>
                    </div>
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="save" wire:loading.attr="disabled">
                {{ $editMode ? __('Update') : __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Status Modal -->
    <x-overlays.dialog-modal wire:model="showStatusModal" maxWidth="2xl">
        <x-slot name="title">
            {{ __('Acknowledgement Status') }}
        </x-slot>

        <x-slot name="content">
            @if($this->acknowledgementStatus)
                <div class="mb-6 grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Acknowledged') }}</div>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $this->acknowledgementStatus['acknowledged_count'] }}</span>
                            <span class="text-sm text-gray-500">/ {{ $this->acknowledgementStatus['total'] }}</span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Pending') }}</div>
                        <div class="mt-1 flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $this->acknowledgementStatus['pending_count'] }}</span>
                            <span class="text-sm text-gray-500">/ {{ $this->acknowledgementStatus['total'] }}</span>
                        </div>
                    </div>
                </div>

                <div x-data="{ tab: 'pending' }">
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="-mb-px flex gap-6" aria-label="{{ __('Tabs') }}">
                            <button @click="tab = 'pending'" :class="tab === 'pending' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                {{ __('Pending') }}
                            </button>
                            <button @click="tab = 'acknowledged'" :class="tab === 'acknowledged' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                                {{ __('Acknowledged') }}
                            </button>
                        </nav>
                    </div>

                    <div class="mt-4 max-h-[400px] overflow-y-auto">
                        <!-- Pending List -->
                        <div x-show="tab === 'pending'" class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($this->acknowledgementStatus['pending'] as $item)
                                <div class="flex items-center gap-3 py-3">
                                    <img src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" class="h-8 w-8 rounded-full object-cover">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $item['user']->name }}</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-md bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20 dark:bg-warning-900/20 dark:text-warning-400">{{ __('Pending') }}</span>
                                </div>
                            @empty
                                <div class="py-8 text-center text-sm text-gray-500">{{ __('Everyone has acknowledged.') }}</div>
                            @endforelse
                        </div>

                        <!-- Acknowledged List -->
                        <div x-show="tab === 'acknowledged'" style="display: none;" class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($this->acknowledgementStatus['acknowledged'] as $item)
                                <div class="flex items-center gap-3 py-3">
                                    <img src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" class="h-8 w-8 rounded-full object-cover">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">{{ $item['user']->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $item['dismissed_at'] }}</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-900/20 dark:text-success-400">{{ __('Acknowledged') }}</span>
                                </div>
                            @empty
                                <div class="py-8 text-center text-sm text-gray-500">{{ __('No one has acknowledged yet.') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showStatusModal', false)">
                {{ __('Close') }}
            </x-actions.secondary-button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
