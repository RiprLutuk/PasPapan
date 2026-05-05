<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="hr-tasks-title" class="user-page-surface">
            <x-user.page-header
                :back-href="route('home')"
                :title="__('HR Tasks')"
                :description="__('Complete onboarding and offboarding follow-ups assigned to you.')"
                title-id="hr-tasks-title">
                <x-slot name="icon">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body bg-gray-50/50 dark:bg-gray-900/20">
                @include('components.feedback.alert-messages')

                <div class="mb-5 grid gap-3 sm:grid-cols-[1fr_220px]">
                    <div>
                        <x-forms.label for="hr-task-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="hr-task-search" type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search task or employee...') }}" />
                    </div>
                    <div>
                        <x-forms.label for="hr-task-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="hr-task-status" wire:model.live="statusFilter">
                            <option value="all">{{ __('All statuses') }}</option>
                            @foreach ($statuses as $statusKey => $statusLabel)
                                <option value="{{ $statusKey }}">{{ $statusLabel }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse ($tasks as $task)
                        @php
                            $taskTone = match ($task->status) {
                                \App\Models\HrChecklistTask::STATUS_DONE => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
                                \App\Models\HrChecklistTask::STATUS_SKIPPED => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                                \App\Models\HrChecklistTask::STATUS_BLOCKED => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                default => $task->isOverdue() ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200' : 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-200',
                            };
                        @endphp
                        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-100 p-4 dark:border-gray-700 sm:p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $task->case->template->typeLabel() }}</p>
                                        <h2 class="mt-1 text-lg font-bold leading-tight text-gray-950 dark:text-white">{{ __($task->title) }}</h2>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ __($task->description ?? '') }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $taskTone }}">
                                        {{ $task->statusLabel() }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-4 p-4 sm:p-5">
                                <dl class="grid gap-3 text-sm sm:grid-cols-3">
                                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Employee') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $task->case->user->name }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Due Date') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $task->due_date?->translatedFormat('d M Y') ?? '-' }}</dd>
                                    </div>
                                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-900/40">
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Effective Date') }}</dt>
                                        <dd class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $task->case->effective_date->translatedFormat('d M Y') }}</dd>
                                    </div>
                                </dl>

                                @can('update', $task)
                                    <div class="space-y-3">
                                        <x-forms.label for="task-note-{{ $task->id }}" value="{{ __('Note') }}" />
                                        <x-forms.textarea id="task-note-{{ $task->id }}" wire:model="taskNotes.{{ $task->id }}" rows="2" placeholder="{{ __('Add a short note...') }}" />
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'done')" variant="soft-success" size="sm">{{ __('Done') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'blocked')" variant="soft-warning" size="sm">{{ __('Blocked') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'skipped')" variant="soft-primary" size="sm">{{ __('Skip') }}</x-actions.button>
                                            <x-actions.button type="button" wire:click="updateTask({{ $task->id }}, 'pending')" variant="ghost" size="sm">{{ __('Reopen') }}</x-actions.button>
                                        </div>
                                    </div>
                                @endcan
                            </div>
                        </article>
                    @empty
                        <div class="user-empty-state">
                            <div class="user-empty-state__icon">
                                <x-heroicon-o-clipboard-document-check class="h-12 w-12 text-gray-300 dark:text-gray-500" />
                            </div>
                            <h3 class="user-empty-state__title">{{ __('No HR tasks found') }}</h3>
                            <p class="user-empty-state__copy">{{ __('Assigned checklist tasks will appear here when HR starts an onboarding or offboarding case.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-5 rounded-2xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                    {{ $tasks->links() }}
                </div>
            </div>
        </section>
    </div>
</div>
