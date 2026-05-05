<x-admin.page-shell :title="__('HR Checklists')" :description="__('Run onboarding and offboarding checklists without leaving the shared-hosting friendly workflow.')">
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
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(360px,440px)]">
            <x-admin.panel>
                <div class="grid gap-3 p-4">
                    @forelse ($cases as $case)
                        @php
                            $statusTone = match ($case->status) {
                                \App\Models\HrChecklistCase::STATUS_COMPLETED => 'success',
                                \App\Models\HrChecklistCase::STATUS_CANCELLED => 'danger',
                                default => 'primary',
                            };
                        @endphp
                        <article class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $case->user->name }}</h3>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $case->user->jobTitle->name ?? __('N/A') }}</p>
                                    <p class="mt-2 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $case->template->typeLabel() }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __($case->template->name) }}</p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                                    <x-admin.status-badge :tone="$statusTone">{{ $case->statusLabel() }}</x-admin.status-badge>
                                    <x-admin.status-badge tone="primary">{{ $case->progressPercent() }}%</x-admin.status-badge>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="flex items-center justify-between gap-3 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span>{{ __('Progress') }}</span>
                                    <span>{{ __('Effective Date') }}: {{ $case->effective_date->translatedFormat('d M Y') }}</span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-full rounded-full bg-primary-600" style="width: {{ $case->progressPercent() }}%"></div>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <x-actions.button type="button" wire:click="selectCase({{ $case->id }})" variant="soft-primary" size="sm">
                                    <x-heroicon-o-eye class="h-4 w-4" />
                                    {{ __('Open') }}
                                </x-actions.button>
                                @can('manageHrChecklists')
                                    @if ($case->status !== \App\Models\HrChecklistCase::STATUS_CANCELLED)
                                        <x-actions.button type="button" wire:click="cancelCase({{ $case->id }})" wire:confirm="{{ __('Cancel this checklist case?') }}" variant="soft-danger" size="sm">
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                            {{ __('Cancel') }}
                                        </x-actions.button>
                                    @endif
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No checklist cases found.') }}</div>
                    @endforelse
                </div>

                <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                    {{ $cases->links() }}
                </div>
            </x-admin.panel>

            <x-admin.panel>
                @if ($selectedCase)
                    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Selected case') }}</p>
                        <h2 class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $selectedCase->user->name }}</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $selectedCase->template->typeLabel() }} · {{ $selectedCase->progressPercent() }}%</p>
                    </div>

                    <div class="space-y-4 p-5">
                        @foreach ($selectedCase->tasks as $task)
                            @php
                                $taskTone = match ($task->status) {
                                    \App\Models\HrChecklistTask::STATUS_DONE => 'success',
                                    \App\Models\HrChecklistTask::STATUS_SKIPPED => 'neutral',
                                    \App\Models\HrChecklistTask::STATUS_BLOCKED => 'danger',
                                    default => $task->isOverdue() ? 'warning' : 'primary',
                                };
                            @endphp
                            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-gray-950 dark:text-white">{{ __($task->title) }}</h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ __($task->description ?? '') }}</p>
                                    </div>
                                    <x-admin.status-badge :tone="$taskTone">{{ $task->statusLabel() }}</x-admin.status-badge>
                                </div>
                                <dl class="mt-3 grid gap-2 text-xs text-gray-500 dark:text-gray-400 sm:grid-cols-2">
                                    <div><dt class="font-semibold">{{ __('Assignee') }}</dt><dd>{{ $task->assignee->name ?? __('Unassigned') }}</dd></div>
                                    <div><dt class="font-semibold">{{ __('Due Date') }}</dt><dd>{{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}</dd></div>
                                </dl>
                                @can('update', $task)
                                    <div class="mt-3 space-y-3">
                                        <x-forms.textarea wire:model="taskNotes.{{ $task->id }}" rows="2" placeholder="{{ __('Add a short note...') }}" />
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'done')" variant="soft-success" size="sm">{{ __('Done') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'blocked')" variant="soft-warning" size="sm">{{ __('Blocked') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'skipped')" variant="soft-primary" size="sm">{{ __('Skip') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'pending')" variant="ghost" size="sm">{{ __('Reopen') }}</x-actions.button>
                                        </div>
                                    </div>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center">
                        <x-heroicon-o-clipboard-document-check class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
                        <h3 class="mt-3 font-semibold text-gray-900 dark:text-white">{{ __('Open a checklist case') }}</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Select a case to review task ownership, due dates, and progress.') }}</p>
                    </div>
                @endif
            </x-admin.panel>
        </div>
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
            <div class="space-y-4">
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
                        <x-forms.input id="hr-case-effective-date" type="date" wire:model="effectiveDate" class="mt-1" />
                        <x-forms.input-error for="effectiveDate" class="mt-2" />
                    </div>
                </div>
                <div>
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
