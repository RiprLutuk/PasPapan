<x-admin.page-shell :title="__('HR Checklists')" :description="__('Run and track onboarding and offboarding checklists seamlessly.')">
    <x-slot name="actions">
        @can('manageHrChecklists')
            <x-actions.button wire:click="createCase" size="icon" label="{{ __('Start checklist case') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
            </x-actions.button>
        @endcan
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools>
            <div class="md:col-span-2 xl:col-span-3">
                <x-forms.label for="hr-checklist-tab" value="{{ __('View') }}" class="mb-1.5 block" />
                <div id="hr-checklist-tab" class="inline-flex w-full rounded-xl border border-gray-200 bg-white p-1 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <button type="button" wire:click="switchTab('cases')" @class([
                        'wcag-touch-target flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition',
                        'bg-primary-700 text-white' => $activeTab === 'cases',
                        'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $activeTab !== 'cases',
                    ])>{{ __('Cases') }}</button>
                    <button type="button" wire:click="switchTab('templates')" @class([
                        'wcag-touch-target flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition',
                        'bg-primary-700 text-white' => $activeTab === 'templates',
                        'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700' => $activeTab !== 'templates',
                    ])>{{ __('Templates') }}</button>
                </div>
            </div>

            <div class="md:col-span-2 xl:col-span-5">
                <x-forms.label for="hr-checklist-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                <x-forms.input id="hr-checklist-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search employee, NIP, or template...') }}" />
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="hr-checklist-type-filter" value="{{ __('Type') }}" class="mb-1.5 block" />
                <x-forms.select id="hr-checklist-type-filter" wire:model.live="typeFilter">
                    <option value="all">{{ __('All types') }}</option>
                    @foreach ($types as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                    @endforeach
                </x-forms.select>
            </div>

            <div class="xl:col-span-2">
                <x-forms.label for="hr-checklist-status-filter" value="{{ __('Status') }}" class="mb-1.5 block" />
                <x-forms.select id="hr-checklist-status-filter" wire:model.live="statusFilter">
                    <option value="all">{{ __('All statuses') }}</option>
                    @foreach ($caseStatuses as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    @include('components.feedback.alert-messages')

    @if ($activeTab === 'cases')
        @if ($selectedCase)
            <!-- Task Board Full Screen View -->
            <div class="mb-6 flex flex-col justify-between gap-4 rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800 sm:flex-row sm:items-center">
                <div>
                    <x-actions.button type="button" wire:click="unselectCase" variant="ghost" size="sm" class="mb-2 -ml-2 text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                        <x-heroicon-m-arrow-left class="mr-1 h-4 w-4" /> {{ __('Back to Cases') }}
                    </x-actions.button>
                    <h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ $selectedCase->user->name }} - {{ __('Checklist') }}</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $selectedCase->template->typeLabel() }} · {{ $selectedCase->progressPercent() }}% {{ __('Completed') }}</p>
                </div>
                <div>
                    @can('manageHrChecklists')
                        @if ($selectedCase->status !== \App\Models\HrChecklistCase::STATUS_CANCELLED)
                            <x-actions.button type="button" wire:click="cancelCase({{ $selectedCase->id }})" wire:confirm="{{ __('Cancel this checklist case?') }}" variant="soft-danger" size="sm">
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                                {{ __('Cancel Case') }}
                            </x-actions.button>
                        @endif
                    @endcan
                </div>
            </div>

            <!-- Trello-style Drag and Drop Kanban for Tasks -->
            <div class="grid grid-cols-1 items-start gap-4 pb-10 sm:grid-cols-2 xl:grid-cols-4">
                @foreach(['pending' => __('Pending'), 'blocked' => __('Blocked'), 'skipped' => __('Skipped'), 'done' => __('Done')] as $statusKey => $columnTitle)
                    <div
                        class="flex min-w-0 flex-col rounded-2xl bg-gray-100/80 p-3 shadow-inner transition-colors duration-200 dark:bg-gray-800/60 max-h-[75vh]"
                        x-data="{ isHovered: false }"
                        @dragover.prevent="isHovered = true"
                        @dragleave.prevent="isHovered = false"
                        @drop="isHovered = false; let taskId = $event.dataTransfer.getData('text/plain'); if(taskId) { $wire.updateTask(taskId, '{{ $statusKey }}') }"
                        :class="isHovered ? 'ring-2 ring-primary-500 bg-primary-50 dark:bg-primary-900/30' : ''"
                    >
                        <div class="mb-3 flex items-center justify-between px-1">
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ $columnTitle }}</h3>
                            <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                {{ $selectedCase->tasks->where('status', $statusKey)->count() }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-3 overflow-y-auto px-1 pb-2 hide-scrollbar">
                            @forelse($selectedCase->tasks->where('status', $statusKey) as $task)
                                @php
                                    $taskTone = match ($task->status) {
                                        \App\Models\HrChecklistTask::STATUS_DONE => 'success',
                                        \App\Models\HrChecklistTask::STATUS_SKIPPED => 'neutral',
                                        \App\Models\HrChecklistTask::STATUS_BLOCKED => 'danger',
                                        default => $task->isOverdue() ? 'warning' : 'primary',
                                    };
                                @endphp
                                <article
                                    wire:key="task-{{ $task->id }}"
                                    class="group relative cursor-grab rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-primary-400 hover:shadow-md focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 active:cursor-grabbing dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500"
                                    draggable="true"
                                    @dragstart="$event.dataTransfer.setData('text/plain', '{{ $task->id }}')"
                                >
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <x-admin.status-badge :tone="$taskTone" class="shrink-0 text-[10px] uppercase tracking-wider">{{ __($task->status) }}</x-admin.status-badge>
                                        <div class="flex items-center gap-1 text-[10px] font-medium text-gray-500 dark:text-gray-400">
                                            <x-heroicon-m-calendar class="h-3 w-3" />
                                            <span class="{{ $task->isOverdue() && $task->status === 'pending' ? 'text-danger-600 font-bold dark:text-danger-400' : '' }}">
                                                {{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}
                                            </span>
                                        </div>
                                    </div>
                                    <h4 class="font-semibold text-gray-900 leading-tight dark:text-white">{{ __($task->title) }}</h4>
                                    @if($task->description)
                                        <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ __($task->description) }}</p>
                                    @endif

                                    <div class="mt-4 flex items-center gap-2 border-t border-gray-100 pt-3 dark:border-gray-700/50">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-100 text-[10px] font-bold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400" title="{{ $task->assignee->name ?? __('Unassigned') }}">
                                            {{ substr($task->assignee->name ?? '?', 0, 1) }}
                                        </div>
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $task->assignee->name ?? __('Unassigned') }}</span>
                                    </div>

                                    @can('update', $task)
                                        <div class="mt-3">
                                            @if($task->status === \App\Models\HrChecklistTask::STATUS_PENDING)
                                                <div class="space-y-2">
                                                    <x-forms.textarea wire:model="taskNotes.{{ $task->id }}" rows="1" placeholder="{{ __('Add notes...') }}" class="w-full bg-gray-50/50 text-xs transition-colors focus:bg-white dark:bg-gray-900/30" />
                                                    <div class="grid grid-cols-3 gap-1.5">
                                                        <button type="button" wire:click="updateTask({{ $task->id }}, 'done')" class="rounded-lg bg-success-50 py-1.5 text-xs font-semibold text-success-700 transition hover:bg-success-100 dark:bg-success-900/20 dark:text-success-400 dark:hover:bg-success-900/40">{{ __('Done') }}</button>
                                                        <button type="button" wire:click="updateTask({{ $task->id }}, 'blocked')" class="rounded-lg bg-danger-50 py-1.5 text-xs font-semibold text-danger-700 transition hover:bg-danger-100 dark:bg-danger-900/20 dark:text-danger-400 dark:hover:bg-danger-900/40">{{ __('Block') }}</button>
                                                        <button type="button" wire:click="updateTask({{ $task->id }}, 'skipped')" class="rounded-lg bg-gray-100 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">{{ __('Skip') }}</button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex flex-col gap-2">
                                                    @if((string) ($task->notes ?? '') !== '')
                                                        <div class="rounded-md bg-gray-50 p-2 text-xs italic text-gray-600 dark:bg-gray-900/40 dark:text-gray-400">
                                                            &quot;{{ $task->notes }}&quot;
                                                        </div>
                                                    @endif
                                                    <button type="button" wire:click="updateTask({{ $task->id }}, 'pending')" class="flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                                        <x-heroicon-m-arrow-path class="h-3 w-3" /> {{ __('Reopen') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    @endcan
                                </article>
                            @empty
                                <div class="flex h-24 items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-white/50 dark:border-gray-700/50 dark:bg-gray-800/30">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Drag tasks here') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- Cases Board View -->
            <div class="grid items-start gap-6 md:grid-cols-3">
                @foreach (['active' => __('Active Cases'), 'completed' => __('Completed'), 'cancelled' => __('Cancelled')] as $statusKey => $columnTitle)
                    <div class="flex flex-col gap-3 rounded-xl bg-gray-50/80 p-3 shadow-inner dark:bg-gray-800/40">
                        <div class="flex items-center justify-between px-1">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700 dark:text-gray-300">{{ $columnTitle }}</h3>
                            <span class="rounded-full bg-white px-2.5 py-0.5 text-xs font-semibold text-gray-600 shadow-sm dark:bg-gray-700 dark:text-gray-300">
                                {{ $cases->where('status', $statusKey)->count() }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-3">
                            @forelse ($cases->where('status', $statusKey) as $case)
                                @php
                                    $statusTone = match ($case->status) {
                                        \App\Models\HrChecklistCase::STATUS_COMPLETED => 'success',
                                        \App\Models\HrChecklistCase::STATUS_CANCELLED => 'danger',
                                        default => 'primary',
                                    };
                                @endphp
                                <article wire:click="selectCase({{ $case->id }})" role="button" tabindex="0" class="group relative rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary-400 hover:shadow-md focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-primary-500">
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ $case->user->name }}</h4>
                                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $case->user->jobTitle->name ?? __('N/A') }}</p>
                                        </div>
                                        <x-admin.status-badge :tone="$statusTone" class="shrink-0">{{ $case->progressPercent() }}%</x-admin.status-badge>
                                    </div>
                                    <div class="mt-3">
                                        <p class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $case->template->typeLabel() }}</p>
                                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ __($case->template->name) }}</p>
                                    </div>
                                    <div class="mt-3">
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                            <div class="h-full rounded-full bg-primary-600 transition-all" style="width: {{ $case->progressPercent() }}%"></div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-right">
                                        <span class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ $case->effective_date->translatedFormat('d M Y') }}</span>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-lg border-2 border-dashed border-gray-200 p-6 text-center dark:border-gray-700">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('No cards') }}</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $cases->links() }}
            </div>
        @endif


    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($templates as $template)
                <article class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">{{ $template->typeLabel() }}</p>
                            <h3 class="mt-1 font-bold text-gray-950 dark:text-white">{{ __($template->name) }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ __($template->description ?? '') }}</p>
                        </div>
                        <x-admin.status-badge :tone="$template->is_active ? 'success' : 'neutral'">{{ $template->is_active ? __('Active') : __('Inactive') }}</x-admin.status-badge>
                    </div>
                    <ol class="mt-4 space-y-2">
                        @foreach ($template->items as $item)
                            <li class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-900/40">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ __($item->title) }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ \App\Models\HrChecklistTemplateItem::assigneeTypes()[$item->default_assignee_type] ?? __('HR') }} · {{ __('Day offset') }}: {{ $item->due_offset_days }}
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </article>
            @endforeach
        </div>
    @endif

    <x-overlays.dialog-modal wire:model="showCreateCaseModal">
        <x-slot name="title">{{ __('Start checklist case') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4 pb-48">
                <div>
                    <x-forms.label for="hr-case-employee" value="{{ __('Employee') }}" />
                    <x-forms.select id="hr-case-employee" wire:model="employeeId" class="mt-1">
                        <option value="">{{ __('Choose employee') }}</option>
                        @foreach ($employeeOptions as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}{{ $employee->nip ? ' · '.$employee->nip : '' }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.input-error for="employeeId" class="mt-2" />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="hr-case-type" value="{{ __('Type') }}" />
                        <x-forms.select id="hr-case-type" wire:model.live="type" class="mt-1">
                            @foreach ($types as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="type" class="mt-2" />
                    </div>
                    <div>
                        <x-forms.label for="hr-case-effective-date" value="{{ __('Effective Date') }}" />
                        <div wire:ignore>
                            <x-forms.input id="hr-case-effective-date" type="date" wire:model="effectiveDate" class="mt-1 block w-full" data-ui-picker-static="true" />
                        </div>
                        <x-forms.input-error for="effectiveDate" class="mt-2" />
                    </div>
                </div>
                <div wire:key="template-wrapper-{{ $type }}">
                    <x-forms.label for="hr-case-template" value="{{ __('Template') }}" />
                    <x-forms.select id="hr-case-template" wire:model="templateId" class="mt-1">
                        @foreach ($templateOptions as $template)
                            <option value="{{ $template->id }}">{{ __($template->name) }}</option>
                        @endforeach
                    </x-forms.select>
                    <x-forms.input-error for="templateId" class="mt-2" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('showCreateCaseModal', false)" wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="startCase" wire:loading.attr="disabled">{{ __('Start') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
