<div>
    <x-admin.page-shell
        :title="__('Roles & Permissions')"
        :description="__('Manage checklist-based admin access without replacing the existing policy and gate layer.')"
    >
        <x-slot name="actions">
            <x-actions.button type="button" wire:click="showCreate" label="{{ __('Create Role') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
                <span>{{ __('Create Role') }}</span>
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-1">
                    <x-forms.label for="role-search" value="{{ __('Search roles') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5" />
                        </span>
                        <x-forms.input
                            id="role-search"
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            class="w-full pl-11"
                            placeholder="{{ __('Search by role name or slug...') }}"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border border-indigo-100 bg-indigo-50/80 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-100">
                    <p class="font-semibold">{{ __('Admin-first scope') }}</p>
                    <p class="mt-1 text-indigo-700 dark:text-indigo-200">
                        {{ __('This page currently manages admin menu access and admin-side actions only.') }}
                    </p>
                </div>

                <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-100">
                    <p class="font-semibold">{{ __('Role assignment') }}</p>
                    <p class="mt-1 text-emerald-700 dark:text-emerald-200">
                        {{ __('Role assignment is enforced separately so normal admins do not gain access automatically.') }}
                    </p>
                </div>
            </x-admin.page-tools>
        </x-slot>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(320px,0.75fr)]">
            <x-admin.panel>
                <div class="border-b border-gray-200/70 px-6 py-5 dark:border-gray-700/70">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Role Directory') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('System roles can be updated, while custom roles can also be removed.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 p-4">
                    @forelse ($roles as $role)
                        <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-950 dark:text-white">{{ $role->name }}</h3>
                                        @if ($role->is_super_admin)
                                            <x-admin.status-badge tone="danger">{{ __('Super Admin') }}</x-admin.status-badge>
                                        @endif
                                        @if ($role->is_system)
                                            <x-admin.status-badge tone="primary">{{ __('System Role') }}</x-admin.status-badge>
                                        @endif
                                    </div>

                                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400">{{ $role->slug }}</p>

                                    @if ($role->description)
                                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $role->description }}</p>
                                    @endif

                                    <div class="flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                                        <span>{{ __('Users assigned: :count', ['count' => $role->users_count]) }}</span>
                                        <span>{{ __('Permissions: :count', ['count' => count($role->permissions ?? $allPermissions)]) }}</span>
                                    </div>
                                </div>

                                <div class="flex shrink-0 gap-2">
                                    <x-actions.icon-button
                                        type="button"
                                        wire:click="edit('{{ $role->id }}')"
                                        variant="primary"
                                        label="{{ __('Edit role') }}: {{ $role->name }}"
                                    >
                                        <x-heroicon-m-pencil-square class="h-5 w-5" />
                                    </x-actions.icon-button>

                                    @unless ($role->is_system)
                                        <x-actions.icon-button
                                            type="button"
                                            wire:click="confirmDeletion('{{ $role->id }}')"
                                            variant="danger"
                                            label="{{ __('Delete role') }}: {{ $role->name }}"
                                        >
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    @endunless
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-admin.empty-state
                            :title="__('No roles found')"
                            :description="__('Create your first checklist role to start assigning menu-based access.')"
                            class="border-0 bg-transparent p-8 shadow-none dark:bg-transparent"
                        >
                            <x-slot name="icon">
                                <x-heroicon-o-shield-check class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                            </x-slot>
                        </x-admin.empty-state>
                    @endforelse
                </div>
            </x-admin.panel>

            <x-admin.panel>
                <div class="border-b border-gray-200/70 px-6 py-5 dark:border-gray-700/70">
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Permission Matrix Preview') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('The checklist below mirrors the real admin modules found in the repository.') }}
                    </p>
                </div>

                <div class="space-y-5 p-4">
                    @foreach ($groupedModules as $section)
                        <section class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                            <div class="mb-4">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">
                                    {{ __($section['meta']['label']) }}
                                </h3>
                                @if (! empty($section['meta']['description']))
                                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __($section['meta']['description']) }}</p>
                                @endif
                            </div>

                            <div class="space-y-3">
                                @foreach ($section['modules'] as $module)
                                    <div class="rounded-xl border border-white/80 bg-white/90 p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950/70">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __($module['label']) }}</h4>
                                            @if ($module['enterprise'])
                                                <x-admin.status-badge tone="warning">{{ __('Enterprise') }}</x-admin.status-badge>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __($module['description']) }}</p>

                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($module['actions'] as $action)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                    {{ __($action['label']) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>
            </x-admin.panel>
        </div>
    </x-admin.page-shell>

    <x-overlays.dialog-modal wire:model="showEditor">
        <x-slot name="title">
            {{ $editingRole ? __('Edit Role') : __('Create Role') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-5">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="role-name" value="{{ __('Role Name') }}" />
                        <x-forms.input id="role-name" type="text" class="mt-1 block w-full" wire:model.live="name" />
                        <x-forms.input-error for="name" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="role-slug" value="{{ __('Role Slug') }}" />
                        <x-forms.input id="role-slug" type="text" class="mt-1 block w-full" wire:model.live="slug" />
                        <x-forms.input-error for="slug" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-forms.label for="role-description" value="{{ __('Description') }}" />
                    <textarea
                        id="role-description"
                        rows="3"
                        wire:model="description"
                        class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-primary-600 dark:focus:ring-primary-600"
                    ></textarea>
                    <x-forms.input-error for="description" class="mt-2" />
                </div>

                @if ($editingRole?->is_super_admin)
                    <div class="rounded-2xl border border-red-100 bg-red-50/80 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-100">
                        <p class="font-semibold">{{ __('Super Admin role stays full access.') }}</p>
                        <p class="mt-1 text-red-700 dark:text-red-200">
                            {{ __('The super admin preset always keeps every admin permission enabled.') }}
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Permission Checklist') }}</h3>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ __('Select the menus and actions this role can open or perform.') }}
                            </p>
                        </div>

                        @foreach ($groupedModules as $sectionKey => $section)
                            <section class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-300">
                                        {{ __($section['meta']['label']) }}
                                    </h4>
                                    @if (! empty($section['meta']['description']))
                                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __($section['meta']['description']) }}</p>
                                    @endif
                                </div>

                                <div class="space-y-4">
                                    @foreach ($section['modules'] as $moduleKey => $module)
                                        <div class="rounded-xl border border-white/90 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-950/80">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <h5 class="font-semibold text-slate-900 dark:text-white">{{ __($module['label']) }}</h5>
                                                @if ($module['enterprise'])
                                                    <x-admin.status-badge tone="warning">{{ __('Enterprise') }}</x-admin.status-badge>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ __($module['description']) }}</p>

                                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                                @foreach ($module['actions'] as $actionKey => $action)
                                                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50/80 px-3 py-3 text-sm text-slate-700 transition hover:border-primary-300 hover:bg-primary-50/50 dark:border-gray-700 dark:bg-gray-900/70 dark:text-slate-200 dark:hover:border-primary-700 dark:hover:bg-primary-950/20">
                                                        <x-forms.checkbox
                                                            wire:model="permissions"
                                                            value="{{ $action['permission'] }}"
                                                            class="mt-0.5"
                                                        />
                                                        <span>
                                                            <span class="block font-medium text-slate-900 dark:text-white">{{ __($action['label']) }}</span>
                                                            <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ $action['permission'] }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="cancelEditor" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" type="button" wire:click="save" wire:loading.attr="disabled">
                {{ $editingRole ? __('Save Changes') : __('Create Role') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Role') }}</x-slot>
        <x-slot name="content">
            {{ __('Deleting this role will also remove it from assigned users. This action cannot be undone.') }}
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$set('confirmingDeletion', false)" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="deleteRole" wire:loading.attr="disabled">
                {{ __('Delete Role') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>
</div>
