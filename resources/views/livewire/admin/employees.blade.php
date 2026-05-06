<div>
    <x-admin.page-shell :title="__('Employee Management')" :description="__('Manage your organization\'s workforce, roles, and access.')">
        <x-slot name="actions">
            @if ($canManageEmployees)
                <x-actions.button wire:click="showCreating" size="icon" label="{{ __('Add Employee') }}">
                    <x-heroicon-m-plus class="h-5 w-5" />
                </x-actions.button>
            @endif
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-5">

                <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                    <x-forms.label for="employee-search" value="{{ __('Search employees') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                        <x-forms.input id="employee-search" wire:model.live.debounce.300ms="search" type="text"
                            placeholder="{{ __('Search name, NIP...') }}"
                            class="block w-full border-0 py-2.5 pl-10 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6" />
                    </div>
                </div>

                <div class="col-span-1">
                    <x-forms.label for="filter_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                    <x-forms.tom-select id="filter_division" wire:model.live="division"
                        placeholder="{{ __('All Divisions') }}" :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                </div>

                <div class="col-span-1">
                    <x-forms.label for="filter_jobTitle" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                    <x-forms.tom-select id="filter_jobTitle" wire:model.live="jobTitle"
                        placeholder="{{ __('All Job Titles') }}" :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
                </div>

                <div class="col-span-1">
                    <x-forms.label for="filter_education" value="{{ __('Education') }}" class="mb-1.5 block" />
                    <x-forms.tom-select id="filter_education" wire:model.live="education"
                        placeholder="{{ __('All Education') }}" :options="App\Models\Education::all()->map(fn($e) => ['id' => $e->id, 'name' => $e->name])" />
                </div>

                <div class="col-span-1">
                    <x-forms.label for="filter_employment_status" value="{{ __('Status') }}" class="mb-1.5 block" />
                    <x-forms.tom-select id="filter_employment_status" wire:model.live="employmentStatus"
                        placeholder="{{ __('All Statuses') }}" :options="collect($employmentStatuses)->map(
                            fn($statusLabel, $statusKey) => ['id' => $statusKey, 'name' => __($statusLabel)],
                        )" />
                </div>
            </x-admin.page-tools>
        </x-slot>

        <!-- Content -->
        <x-admin.panel>
            <div class="border-b border-emerald-100 bg-emerald-50/50 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/10 sm:px-5">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100/80 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                {{ __('Employee Directory') }}
                            </span>
                            <h2 class="text-sm font-semibold text-slate-950 dark:text-white">
                                {{ __('Visible employee records') }}
                            </h2>
                        </div>
                        <p class="mt-1 truncate text-xs text-slate-600 dark:text-slate-300">
                            {{ __('Quick access to role, unit, contact, and education data for each employee.') }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-2 gap-2 sm:grid-cols-5 xl:min-w-[40rem]">
                        <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-900/70">
                            <dt class="text-[0.68rem] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Total') }}</dt>
                            <dd class="mt-0.5 text-base font-semibold leading-5 text-slate-950 dark:text-white">{{ $users->total() }}</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-900/70">
                            <dt class="text-[0.68rem] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Showing') }}</dt>
                            <dd class="mt-0.5 text-base font-semibold leading-5 text-slate-950 dark:text-white">{{ $users->count() }}</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-900/70">
                            <dt class="text-[0.68rem] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Active') }}</dt>
                            <dd class="mt-0.5 text-base font-semibold leading-5 text-slate-950 dark:text-white">{{ $statusSummary['active'] }}</dd>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-900/70">
                            <dt class="truncate text-[0.68rem] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Deletion Requests') }}</dt>
                            <dd class="mt-0.5 text-base font-semibold leading-5 text-slate-950 dark:text-white">{{ $statusSummary['pending_deletion'] }}</dd>
                        </div>
                        <div class="col-span-2 rounded-lg border border-emerald-200 bg-white/80 px-3 py-2 dark:border-emerald-900/40 dark:bg-gray-900/70 sm:col-span-1">
                            <dt class="text-[0.68rem] font-semibold uppercase text-emerald-700 dark:text-emerald-300">{{ __('Filters') }}</dt>
                            <dd class="mt-0.5 truncate text-sm font-medium leading-5 text-slate-700 dark:text-slate-200">
                                {{ collect([$division, $jobTitle, $education, $employmentStatus, filled($search) ? $search : null])->filter()->count() ?: __('None') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-emerald-50/80 text-gray-500 dark:bg-emerald-950/20 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Role & Unit') }}</th>
                            <th scope="col" class="px-4 py-3 font-medium">{{ __('Contact & Identity') }}</th>
                            <th scope="col" class="px-4 py-3 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr class="group transition-colors hover:bg-emerald-50/60 dark:hover:bg-emerald-950/10">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-12 w-12 overflow-hidden rounded-full bg-emerald-100 ring-2 ring-emerald-100 dark:bg-emerald-950/40 dark:ring-emerald-900/40">
                                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                                class="h-full w-full object-cover">
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}
                                            </div>
                                            <div class="mt-2">
                                                <x-admin.status-badge :tone="$user->employmentStatusTone()" pill>
                                                    {{ $user->employmentStatusLabel() }}
                                                </x-admin.status-badge>
                                            </div>
                                            @if ($user->nip)
                                                <div class="mt-2 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                                    {{ __('NIP') }}: {{ $user->nip }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2">
                                        <x-admin.status-badge tone="success" class="w-fit">
                                            {{ $user->jobTitle ? json_decode($user->jobTitle)->name : __('No job title') }}
                                        </x-admin.status-badge>
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ $user->division ? json_decode($user->division)->name : __('No division') }}
                                        </div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('Direct Manager') }}:
                                            <span class="font-medium text-slate-700 dark:text-slate-200">
                                                {{ $user->directManager?->name ?: __('Not assigned') }}
                                            </span>
                                        </div>
                                        @if ($user->hasPendingAccountDeletionRequest())
                                            <div class="text-xs text-red-600 dark:text-red-300">
                                                {{ __('Deletion requested by employee') }}
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $user->phone ?: '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Gender') }}: {{ $user->gender ? __(ucfirst($user->gender)) : '-' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="show('{{ $user->id }}')"
                                            variant="primary" label="{{ __('View employee') }}: {{ $user->name }}">
                                            <x-heroicon-m-eye class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        @if ($canManageEmployees)
                                            <x-actions.icon-button wire:click="edit('{{ $user->id }}')"
                                                variant="primary" label="{{ __('Edit employee') }}: {{ $user->name }}">
                                                <x-heroicon-m-pencil-square class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button
                                                wire:click="confirmDeletion('{{ $user->id }}')"
                                                variant="danger" label="{{ __('Delete employee') }}: {{ $user->name }}">
                                                <x-heroicon-m-trash class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif
                                        @if ($canApproveDeletionRequests && $user->hasPendingAccountDeletionRequest())
                                            <x-actions.icon-button
                                                wire:click="confirmDeletionApproval('{{ $user->id }}')"
                                                variant="success" label="{{ __('Approve deletion request') }}: {{ $user->name }}">
                                                <x-heroicon-m-check class="h-5 w-5" />
                                            </x-actions.icon-button>
                                            <x-actions.icon-button
                                                wire:click="confirmDeletionRejection('{{ $user->id }}')"
                                                variant="warning" label="{{ __('Reject deletion request') }}: {{ $user->name }}">
                                                <x-heroicon-m-x-mark class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-users class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                                        <p class="font-medium">{{ __('No employees found') }}</p>
                                        <p class="text-sm">{{ __('Try adjusting your filters or search.') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile List -->
            <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 lg:hidden">
                @foreach ($users as $user)
                    <div class="space-y-4 bg-gradient-to-br from-emerald-50/80 via-white to-slate-50 p-4 dark:from-emerald-950/15 dark:via-gray-900 dark:to-slate-950">
                        <div class="flex items-start gap-3">
                            <img class="h-14 w-14 rounded-xl border-2 border-emerald-100 object-cover shadow-sm dark:border-emerald-900/40"
                                src="{{ $user->profile_photo_url }}"
                                alt="{{ $user->name }}" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h4 class="truncate pr-2 text-sm font-semibold leading-5 text-gray-900 dark:text-white">
                                        {{ $user->name }}</h4>
                                    <div class="flex flex-col items-end gap-2">
                                        <x-admin.status-badge tone="success" class="shrink-0">
                                            {{ $user->jobTitle ? json_decode($user->jobTitle)->name : __('No title') }}
                                        </x-admin.status-badge>
                                        <x-admin.status-badge :tone="$user->employmentStatusTone()" pill class="shrink-0">
                                            {{ $user->employmentStatusLabel() }}
                                        </x-admin.status-badge>
                                    </div>
                                </div>
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                <p class="mt-1 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    {{ $user->division ? json_decode($user->division)->name : __('No division') }}
                                </p>
                                <p class="mt-1 truncate text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">
                                    {{ __('Manager') }}: {{ $user->directManager?->name ?: __('Not assigned') }}
                                </p>
                                @if ($user->hasPendingAccountDeletionRequest())
                                    <p class="mt-1 text-[11px] font-medium tracking-wide text-red-600 dark:text-red-300">
                                        {{ __('Deletion requested by employee') }}
                                    </p>
                                @endif
                                @if ($user->nip)
                                    <p class="mt-1 text-[11px] font-medium tracking-wide text-slate-500 dark:text-slate-400">
                                        {{ __('NIP') }}: {{ $user->nip }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="rounded-xl border border-white/80 bg-white/80 px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Phone') }}</span>
                                <div class="mt-1 text-sm font-medium text-slate-900 dark:text-white">{{ $user->phone ?: '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-white/80 bg-white/80 px-3 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                                <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Gender') }}</span>
                                <div class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                                    {{ $user->gender ? __(ucfirst($user->gender)) : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-1">
                            <x-actions.button type="button" wire:click="show('{{ $user->id }}')"
                                variant="secondary" size="sm"
                                label="{{ __('View employee') }}: {{ $user->name }}">{{ __('View') }}</x-actions.button>
                            @if ($canManageEmployees)
                                <x-actions.button type="button" wire:click="edit('{{ $user->id }}')"
                                    variant="soft-primary" size="sm"
                                    label="{{ __('Edit employee') }}: {{ $user->name }}">{{ __('Edit') }}</x-actions.button>
                                <x-actions.button type="button"
                                    wire:click="confirmDeletion('{{ $user->id }}')"
                                    variant="soft-danger" size="sm"
                                    label="{{ __('Delete employee') }}: {{ $user->name }}">{{ __('Delete') }}</x-actions.button>
                            @elseif ($canApproveDeletionRequests && $user->hasPendingAccountDeletionRequest())
                                <x-actions.button type="button"
                                    wire:click="confirmDeletionApproval('{{ $user->id }}')"
                                    variant="soft-danger" size="sm"
                                    label="{{ __('Approve deletion request') }}: {{ $user->name }}">{{ __('Approve') }}</x-actions.button>
                                <x-actions.button type="button"
                                    wire:click="confirmDeletionRejection('{{ $user->id }}')"
                                    variant="secondary" size="sm"
                                    label="{{ __('Reject deletion request') }}: {{ $user->name }}">{{ __('Reject') }}</x-actions.button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        </x-admin.panel>
    </x-admin.page-shell>

    <!-- Modals (Confirmation & Edit/Create) -->
    <!-- Retaining original modal logic but ensuring styles are compatible -->
    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Employee') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?
            {{ __('This action cannot be undone.') }}</x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.danger-button class="ml-2" wire:click="delete"
                wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="confirmingDeletionReview">
        <x-slot name="title">
            {{ $deletionReviewAction === 'approve' ? __('Approve Account Deletion') : __('Reject Account Deletion') }}
        </x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Employee') }}: <span class="font-semibold text-slate-950 dark:text-white">{{ $deletionReviewEmployeeName }}</span>
                </p>

                <div>
                    <div class="text-sm font-medium text-slate-950 dark:text-white">{{ __('Request reason') }}</div>
                    <div class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        {{ $deletionReviewReason ?: '-' }}
                    </div>
                </div>

                <div>
                    <x-forms.label for="employee-deletion-review-notes" value="{{ __('Admin notes') }}" />
                    <x-forms.textarea
                        id="employee-deletion-review-notes"
                        class="mt-1 block w-full"
                        rows="4"
                        wire:model="deletionReviewNotes"
                        placeholder="{{ __('Optional notes for this review') }}"
                    />
                    <x-forms.input-error for="deletionReviewNotes" class="mt-2" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletionReview')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>
            @if ($deletionReviewAction === 'approve')
                <x-actions.danger-button class="ml-2" wire:click="approveDeletionRequest" wire:loading.attr="disabled">
                    {{ __('Approve and deactivate account') }}
                </x-actions.danger-button>
            @else
                <x-actions.button class="ml-2" wire:click="rejectDeletionRequest" wire:loading.attr="disabled">
                    {{ __('Reject request') }}
                </x-actions.button>
            @endif
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Create/Edit Modal -->
    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">{{ __('New Employee') }}</x-slot>
        <x-slot name="content">
            <form wire:submit="create">
                @csrf
                <!-- Form Fields (Same as original but cleaned up if needed) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="create_name" value="{{ __('Full Name') }}" />
                        <x-forms.input id="create_name" type="text" class="mt-1 block w-full"
                            wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-forms.label for="create_email" value="{{ __('Email') }}" />
                        <x-forms.input id="create_email" type="email" class="mt-1 block w-full"
                            wire:model="form.email" />
                        <x-forms.input-error for="form.email" class="mt-2" />
                    </div>

                    <!-- NIP -->
                    <div>
                        <x-forms.label for="create_nip" value="{{ __('NIP') }}" />
                        <x-forms.input id="create_nip" type="text" class="mt-1 block w-full"
                            wire:model="form.nip" />
                        <x-forms.input-error for="form.nip" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="create_password" value="{{ __('Password') }}" />
                        <x-forms.input id="create_password" type="password" class="mt-1 block w-full"
                            wire:model="form.password" placeholder="{{ __('Leave blank for default: password') }}" />
                        <x-forms.input-error for="form.password" class="mt-2" />
                    </div>

                    <!-- Phone -->
                    <div>
                        <x-forms.label for="create_phone" value="{{ __('Phone') }}" />
                        <x-forms.input id="create_phone" type="text" class="mt-1 block w-full"
                            wire:model="form.phone" />
                        <x-forms.input-error for="form.phone" class="mt-2" />
                    </div>

                    <!-- Gender -->
                    <div>
                        <x-forms.label value="{{ __('Gender') }}" />
                        <div class="mt-3 flex gap-4">
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="male" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="female" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Female') }}</span>
                            </label>
                        </div>
                        <x-forms.input-error for="form.gender" class="mt-2" />
                    </div>

                    <!-- Wilayah Selection (Create) -->
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="create_provinsi" value="{{ __('Provinsi') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="create_provinsi" wire:model.live="form.provinsi_kode"
                                    placeholder="{{ __('Pilih Provinsi') }}" :options="$provinces->map(fn($p) => ['id' => $p->kode, 'name' => $p->nama])" />
                            </div>
                            <x-forms.input-error for="form.provinsi_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kabupaten" value="{{ __('Kabupaten/Kota') }}" />
                            <div class="mt-1" wire:key="create-kab-{{ $form->provinsi_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kabupaten" wire:model.live="form.kabupaten_kode"
                                    placeholder="{{ __('Pilih Kabupaten/Kota') }}" :options="$regencies->map(fn($r) => ['id' => $r->kode, 'name' => $r->nama])" />
                            </div>
                            <x-forms.input-error for="form.kabupaten_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kecamatan" value="{{ __('Kecamatan') }}" />
                            <div class="mt-1" wire:key="create-kec-{{ $form->kabupaten_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kecamatan" wire:model.live="form.kecamatan_kode"
                                    placeholder="{{ __('Pilih Kecamatan') }}" :options="$districts->map(fn($d) => ['id' => $d->kode, 'name' => $d->nama])" />
                            </div>
                            <x-forms.input-error for="form.kecamatan_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_kelurahan" value="{{ __('Kelurahan/Desa') }}" />
                            <div class="mt-1" wire:key="create-kel-{{ $form->kecamatan_kode ?? 'empty' }}">
                                <x-forms.tom-select id="create_kelurahan" wire:model.live="form.kelurahan_kode"
                                    placeholder="{{ __('Pilih Kelurahan/Desa') }}" :options="$villages->map(fn($v) => ['id' => $v->kode, 'name' => $v->nama])" />
                            </div>
                            <x-forms.input-error for="form.kelurahan_kode" class="mt-2" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="create_address" value="{{ __('Address') }}" />
                        <x-forms.textarea id="create_address" class="mt-1 block w-full" wire:model="form.address"
                            rows="2" />
                        <x-forms.input-error for="form.address" class="mt-2" />
                    </div>

                    <!-- Division & Job Title (Full Width) -->
                    <div class="sm:col-span-2 space-y-4">
                        <div>
                            <x-forms.label for="create_division" value="{{ __('Division') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="create_division" wire:model.live="form.division_id"
                                    placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()
                                        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.division_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_jobTitle" value="{{ __('Job Title') }}" />
                            <div class="mt-1"
                                wire:key="create-job-title-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="create_jobTitle" wire:model.live="form.job_title_id"
                                    placeholder="{{ __('Select Job Title') }}" :options="$availableJobTitles
                                        ->map(fn($j) => ['id' => $j->id, 'name' => $j->name])
                                        ->values()" />
                            </div>
                            <x-forms.input-error for="form.job_title_id" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="create_manager" value="{{ __('Direct Manager') }}" />
                            <div class="mt-1" wire:key="create-manager-wrapper-{{ $form->user?->id ?? 'new' }}-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="create_manager" wire:model.live="form.manager_id"
                                    placeholder="{{ __('No direct manager') }}" :options="$managerOptions" />
                            </div>
                            <x-forms.input-error for="form.manager_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Basic Salary & Hourly Rate -->
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.basic_salary'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model);
                        $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="create_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-forms.input id="create_basic_salary" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 5.000.000" />
                            <x-forms.input-error for="form.basic_salary" class="mt-2" />
                        </div>

                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.hourly_rate'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model);
                        $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="create_hourly_rate" value="{{ __('Hourly Rate (Rp)') }}" />
                            <x-forms.input id="create_hourly_rate" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 25.000" />
                            <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to auto-calc (Salary / 173)') }}
                            </p>
                            <x-forms.input-error for="form.hourly_rate" class="mt-2" />
                        </div>
                    </div>

                    @if ($canManageEmployeeStatuses)
                        <div class="sm:col-span-2">
                            <x-forms.label for="create_employment_status" value="{{ __('Employment Status') }}" />
                            <x-forms.select id="create_employment_status" wire:model="form.employment_status"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($manualEmploymentStatuses as $statusKey)
                                    <option value="{{ $statusKey }}">{{ __($employmentStatuses[$statusKey]) }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="form.employment_status" class="mt-2" />
                        </div>
                    @endif
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="create"
                wire:loading.attr="disabled">{{ __('Save') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Edit Modal (Reusing similar structure) -->
    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">{{ __('Edit Employee') }}</x-slot>
        <x-slot name="content">
            <form wire:submit.prevent="update">
                <!-- Re-implement fields similarly or include a partial -->
                <!-- For brevity in this replace, I'll allow the existing form structure if it fits, but ideally we match the Create modal style -->
                <!-- ... (Fields for Edit) ... -->
                <!-- NOTE: I will keep the original Edit Form structure for safety but wrap it nicely -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Name -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_name" value="{{ __('Full Name') }}" />
                        <x-forms.input id="edit_name" type="text" class="mt-1 block w-full"
                            wire:model="form.name" />
                        <x-forms.input-error for="form.name" class="mt-2" />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-forms.label for="edit_email" value="{{ __('Email') }}" />
                        <x-forms.input id="edit_email" type="email" class="mt-1 block w-full"
                            wire:model="form.email" />
                        <x-forms.input-error for="form.email" class="mt-2" />
                    </div>

                    <!-- NIP -->
                    <div>
                        <x-forms.label for="edit_nip" value="{{ __('NIP') }}" />
                        <x-forms.input id="edit_nip" type="text" class="mt-1 block w-full"
                            wire:model="form.nip" />
                        <x-forms.input-error for="form.nip" class="mt-2" />
                    </div>

                    <!-- Password (Optional for Edit) -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_password" value="{{ __('Password') }}" />
                        <x-forms.input id="edit_password" type="password" class="mt-1 block w-full"
                            wire:model="form.password"
                            placeholder="{{ __('Leave blank to keep current password') }}" />
                        <x-forms.input-error for="form.password" class="mt-2" />
                    </div>

                    <!-- Phone -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_phone" value="{{ __('Phone') }}" />
                        <x-forms.input id="edit_phone" type="text" class="mt-1 block w-full"
                            wire:model="form.phone" />
                        <x-forms.input-error for="form.phone" class="mt-2" />
                    </div>

                    <!-- Gender -->
                    <div class="sm:col-span-2">
                        <x-forms.label value="{{ __('Gender') }}" />
                        <div class="mt-3 flex gap-4">
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="male" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <x-forms.radio name="gender" value="female" wire:model="form.gender" />
                                <span class="ml-2 text-sm">{{ __('Female') }}</span>
                            </label>
                        </div>
                        <x-forms.input-error for="form.gender" class="mt-2" />
                    </div>

                    <!-- Wilayah Selection (Edit) -->
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-forms.label for="edit_provinsi" value="{{ __('Provinsi') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="edit_provinsi" wire:model.live="form.provinsi_kode"
                                    placeholder="{{ __('Pilih Provinsi') }}" :options="$provinces->map(fn($p) => ['id' => $p->kode, 'name' => $p->nama])" />
                            </div>
                            <x-forms.input-error for="form.provinsi_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kabupaten" value="{{ __('Kabupaten/Kota') }}" />
                            <div class="mt-1" wire:key="edit-kab-{{ $form->provinsi_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kabupaten" wire:model.live="form.kabupaten_kode"
                                    placeholder="{{ __('Pilih Kabupaten/Kota') }}" :options="$regencies->map(fn($r) => ['id' => $r->kode, 'name' => $r->nama])" />
                            </div>
                            <x-forms.input-error for="form.kabupaten_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kecamatan" value="{{ __('Kecamatan') }}" />
                            <div class="mt-1" wire:key="edit-kec-{{ $form->kabupaten_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kecamatan" wire:model.live="form.kecamatan_kode"
                                    placeholder="{{ __('Pilih Kecamatan') }}" :options="$districts->map(fn($d) => ['id' => $d->kode, 'name' => $d->nama])" />
                            </div>
                            <x-forms.input-error for="form.kecamatan_kode" class="mt-2" />
                        </div>
                        <div>
                            <x-forms.label for="edit_kelurahan" value="{{ __('Kelurahan/Desa') }}" />
                            <div class="mt-1" wire:key="edit-kel-{{ $form->kecamatan_kode ?? 'empty' }}">
                                <x-forms.tom-select id="edit_kelurahan" wire:model.live="form.kelurahan_kode"
                                    placeholder="{{ __('Pilih Kelurahan/Desa') }}" :options="$villages->map(fn($v) => ['id' => $v->kode, 'name' => $v->nama])" />
                            </div>
                            <x-forms.input-error for="form.kelurahan_kode" class="mt-2" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <x-forms.label for="edit_address" value="{{ __('Address') }}" />
                        <x-forms.textarea id="edit_address" class="mt-1 block w-full" wire:model="form.address"
                            rows="2" />
                        <x-forms.input-error for="form.address" class="mt-2" />
                    </div>

                    <!-- Division & Job Title -->
                    <div class="sm:col-span-2 space-y-4">
                        <div>
                            <x-forms.label for="edit_division" value="{{ __('Division') }}" />
                            <div class="mt-1">
                                <x-forms.tom-select id="edit_division" wire:model.live="form.division_id"
                                    placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()
                                        ->map(fn($d) => ['id' => $d->id, 'name' => $d->name])
                                        ->values()" />
                            </div>
                        </div>
                        <div>
                            <x-forms.label for="edit_jobTitle" value="{{ __('Job Title') }}" />
                            <div class="mt-1" wire:key="edit-job-title-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="edit_jobTitle" wire:model.live="form.job_title_id"
                                    placeholder="{{ __('Select Job Title') }}" :options="$availableJobTitles
                                        ->map(fn($j) => ['id' => $j->id, 'name' => $j->name])
                                        ->values()" />
                            </div>
                        </div>
                        <div>
                            <x-forms.label for="edit_manager" value="{{ __('Direct Manager') }}" />
                            <div class="mt-1" wire:key="edit-manager-wrapper-{{ $form->user?->id ?? 'new' }}-{{ $form->division_id ?? 'all' }}">
                                <x-forms.tom-select id="edit_manager" wire:model.live="form.manager_id"
                                    placeholder="{{ __('No direct manager') }}" :options="$managerOptions" />
                            </div>
                            <x-forms.input-error for="form.manager_id" class="mt-2" />
                        </div>
                    </div>

                    <!-- Basic Salary & Hourly Rate -->
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.basic_salary'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model);
                        $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="edit_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-forms.input id="edit_basic_salary" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 5.000.000" />
                            <x-forms.input-error for="form.basic_salary" class="mt-2" />
                        </div>

                        <div x-data="{
                            displayValue: '',
                            model: @entangle('form.hourly_rate'),
                            format(value) {
                                if (!value) return '';
                                return new Intl.NumberFormat('id-ID').format(value);
                            },
                            update(event) {
                                let val = event.target.value.replace(/\./g, '');
                                if (isNaN(val)) val = 0;
                                this.model = val;
                                this.displayValue = this.format(val);
                            }
                        }" x-init="displayValue = format(model);
                        $watch('model', value => displayValue = format(value))">
                            <x-forms.label for="edit_hourly_rate" value="{{ __('Hourly Rate (Rp)') }}" />
                            <x-forms.input id="edit_hourly_rate" type="text" class="mt-1 block w-full"
                                x-model="displayValue" @input="update" placeholder="e.g. 25.000" />
                            <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to auto-calc (Salary / 173)') }}
                            </p>
                            <x-forms.input-error for="form.hourly_rate" class="mt-2" />
                        </div>
                    </div>

                    @if ($canManageEmployeeStatuses)
                        <div class="sm:col-span-2">
                            <x-forms.label for="edit_employment_status" value="{{ __('Employment Status') }}" />
                            <x-forms.select id="edit_employment_status" wire:model="form.employment_status"
                                class="mt-1 block w-full rounded-lg border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                @if (isset($employmentStatuses[$form->employment_status]) && ! in_array($form->employment_status, $manualEmploymentStatuses, true))
                                    <option value="{{ $form->employment_status }}">{{ __($employmentStatuses[$form->employment_status]) }}</option>
                                @endif
                                @foreach ($manualEmploymentStatuses as $statusKey)
                                    <option value="{{ $statusKey }}">{{ __($employmentStatuses[$statusKey]) }}</option>
                                @endforeach
                            </x-forms.select>
                            <x-forms.input-error for="form.employment_status" class="mt-2" />
                            @if (in_array($form->employment_status, [\App\Models\User::EMPLOYMENT_STATUS_DELETION_REQUESTED, \App\Models\User::EMPLOYMENT_STATUS_DELETED], true))
                                <p class="mt-2 text-xs text-amber-600 dark:text-amber-300">
                                    {{ __('Deletion lifecycle statuses are resolved through the review action, not this form.') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            </form>
        </x-slot>
        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('editing')"
                wire:loading.attr="disabled">{{ __('Cancel') }}</x-actions.secondary-button>
            <x-actions.button class="ml-2" wire:click="update"
                wire:loading.attr="disabled">{{ __('Update') }}</x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <!-- Detail Modal -->
    <x-overlays.modal wire:model="showDetail" max-width="5xl">
        @if ($form->user)
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-md dark:border-slate-800 dark:bg-slate-950">
                <div class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-4">
                            <img class="h-16 w-16 shrink-0 rounded-xl border border-slate-200 bg-slate-50 object-cover dark:border-slate-800 dark:bg-slate-900 sm:h-20 sm:w-20"
                                src="{{ $form->user->profile_photo_url }}" alt="{{ $form->user->name }}">
                            <div class="min-w-0">
                                <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ __('Employee Profile') }}
                                </div>
                                <h3 class="mt-2 truncate text-xl font-semibold text-slate-950 dark:text-white sm:text-2xl">
                                    {{ $form->user->name }}
                                </h3>
                                <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">{{ $form->user->email }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-admin.status-badge :tone="$form->user->employmentStatusTone()" pill>
                                        {{ $form->user->employmentStatusLabel() }}
                                    </x-admin.status-badge>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $form->user->jobTitle?->name ?? __('No job title') }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $form->user->division?->name ?? __('No division') }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200">
                                        {{ __('Manager') }}: {{ $form->user->directManager?->name ?? __('Not assigned') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <x-actions.icon-button type="button" wire:click="$set('showDetail', false)" variant="neutral" label="{{ __('Close employee detail') }}">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </x-actions.icon-button>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                            <div class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('NIP') }}</div>
                            <div class="mt-1 break-words text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->nip ?: '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                            <div class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Phone') }}</div>
                            <div class="mt-1 break-words text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->phone ?: '-' }}</div>
                        </div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
                            <div class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Education') }}</div>
                            <div class="mt-1 break-words text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->education?->name ?? '-' }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-5 py-5 dark:bg-slate-950 sm:px-6">
                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                        <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Professional') }}</div>
                                <span class="text-[11px] font-semibold uppercase text-slate-400">{{ __('Work profile') }}</span>
                            </div>
                            <dl class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Job Title') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->jobTitle?->name ?? '-' }}</dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Division') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->division?->name ?? '-' }}</dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Direct Manager') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->directManager?->name ?? '-' }}</dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Education') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->education?->name ?? '-' }}</dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Hourly Rate') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->hourly_rate ? 'Rp ' . number_format((float) $form->user->hourly_rate, 0, ',', '.') : '-' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Personal') }}</div>
                                <span class="text-[11px] font-semibold uppercase text-slate-400">{{ __('Identity') }}</span>
                            </div>
                            <dl class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Gender') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->gender ? __(\Illuminate\Support\Str::headline($form->user->gender)) : '-' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Birth Place') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->birth_place ?: '-' }}</dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Birth Date') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->birth_date ? \Illuminate\Support\Carbon::parse($form->user->birth_date)->translatedFormat('d M Y') : '-' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Phone') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">{{ $form->user->phone ?: '-' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Account Lifecycle') }}</div>
                                <span class="text-[11px] font-semibold uppercase text-slate-400">{{ __('Status & review') }}</span>
                            </div>
                            <dl class="mt-4 divide-y divide-slate-200 dark:divide-slate-800">
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Employment Status') }}</dt>
                                    <dd class="sm:text-right">
                                        <x-admin.status-badge :tone="$form->user->employmentStatusTone()" pill>
                                            {{ $form->user->employmentStatusLabel() }}
                                        </x-admin.status-badge>
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Deletion Requested At') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->account_deletion_requested_at?->translatedFormat('d M Y H:i') ?? '-' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Deletion Reason') }}</dt>
                                    <dd class="whitespace-pre-line text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->account_deletion_reason ?: '-' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Reviewed By') }}</dt>
                                    <dd class="text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->reviewedAccountDeletionBy?->name ?? '-' }}
                                    </dd>
                                </div>
                                <div class="grid gap-1 py-2.5 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Admin Notes') }}</dt>
                                    <dd class="whitespace-pre-line text-sm font-semibold text-slate-950 dark:text-white sm:text-right">
                                        {{ $form->user->account_deletion_review_notes ?: '-' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Address') }}</div>
                                <span class="text-[11px] font-semibold uppercase text-slate-400">{{ __('Location details') }}</span>
                            </div>
                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
                                {{ $form->user->address ?: __('No address saved.') }}
                            </div>
                            <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <dt class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Province') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->provinsi?->nama ?? '-' }}</dd>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <dt class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('City') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kabupaten?->nama ?? '-' }}</dd>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <dt class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('District') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kecamatan?->nama ?? '-' }}</dd>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                    <dt class="text-[11px] font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Village') }}</dt>
                                    <dd class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kelurahan?->nama ?? '-' }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </div>
            </div>
        @endif
    </x-overlays.modal>
</div>
