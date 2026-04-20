<div>
    <x-admin.page-shell :title="__('Employee Management')" :description="__('Manage your organization\'s workforce, roles, and access.')">
        <x-slot name="actions">
            <x-actions.button wire:click="showCreating" size="icon" label="{{ __('Add Employee') }}">
                <x-heroicon-m-plus class="h-5 w-5" />
            </x-actions.button>
        </x-slot>

        <x-slot name="toolbar">
            <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                    <x-forms.label for="employee-search" value="{{ __('Search employees') }}" class="mb-1.5 block" />
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                        <input id="employee-search" wire:model.live.debounce.300ms="search" type="text"
                            placeholder="{{ __('Search name, NIP...') }}"
                            class="block w-full rounded-lg border-0 py-2.5 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
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
            </x-admin.page-tools>
        </x-slot>

        <!-- Content -->
        <x-admin.panel>
            <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-emerald-50 px-6 py-5 dark:border-emerald-900/40 dark:from-emerald-950/30 dark:via-gray-900 dark:to-emerald-950/20">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100/80 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            {{ __('Employee Directory') }}
                        </div>
                        <h2 class="mt-3 text-lg font-semibold text-slate-950 dark:text-white">
                            {{ __('Visible employee records') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                            {{ __('Quick access to role, unit, contact, and education data for each employee.') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-emerald-200 bg-white/90 px-4 py-3 shadow-sm dark:border-emerald-900/40 dark:bg-gray-900/80">
                            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Total') }}</div>
                            <div class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ $users->total() }}</div>
                        </div>
                        <div class="rounded-2xl border border-emerald-200 bg-white/90 px-4 py-3 shadow-sm dark:border-emerald-900/40 dark:bg-gray-900/80">
                            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Showing') }}</div>
                            <div class="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{{ $users->count() }}</div>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-emerald-200 bg-white/90 px-4 py-3 shadow-sm dark:border-emerald-900/40 dark:bg-gray-900/80 sm:col-span-1">
                            <div class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">{{ __('Filters') }}</div>
                            <div class="mt-1 text-sm font-medium text-slate-700 dark:text-slate-200">
                                {{ collect([$division, $jobTitle, $education, filled($search) ? $search : null])->filter()->count() ?: __('None') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-emerald-50/80 text-gray-500 dark:bg-emerald-950/20 dark:text-gray-300">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Role & Unit') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Contact & Identity') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr class="group transition-colors hover:bg-emerald-50/60 dark:hover:bg-emerald-950/10">
                                <td class="px-6 py-4">
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
                                            @if ($user->nip)
                                                <div class="mt-2 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                                    {{ __('NIP') }}: {{ $user->nip }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-2">
                                        <x-admin.status-badge tone="success" class="w-fit">
                                            {{ $user->jobTitle ? json_decode($user->jobTitle)->name : __('No job title') }}
                                        </x-admin.status-badge>
                                        <div class="text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ $user->division ? json_decode($user->division)->name : __('No division') }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $user->phone ?: '-' }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ __('Gender') }}: {{ $user->gender ? __(ucfirst($user->gender)) : '-' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="show('{{ $user->id }}')"
                                            variant="primary" label="{{ __('View employee') }}: {{ $user->name }}">
                                            <x-heroicon-m-eye class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button wire:click="edit('{{ $user->id }}')"
                                            variant="primary" label="{{ __('Edit employee') }}: {{ $user->name }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </x-actions.icon-button>
                                        <x-actions.icon-button
                                            wire:click="confirmDeletion('{{ $user->id }}')"
                                            variant="danger" label="{{ __('Delete employee') }}: {{ $user->name }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </x-actions.icon-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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

            <!-- Mobile List (Optimized) -->
            <div class="grid grid-cols-1 divide-y divide-gray-200 dark:divide-gray-700 sm:hidden">
                @foreach ($users as $user)
                    <div class="space-y-3 bg-gradient-to-r from-emerald-50/70 to-white p-4 dark:from-emerald-950/10 dark:to-gray-900">
                        <div class="flex items-start gap-3">
                            <img class="h-12 w-12 rounded-full border-2 border-emerald-100 object-cover dark:border-emerald-900/40" src="{{ $user->profile_photo_url }}"
                                alt="{{ $user->name }}" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between">
                                    <h4 class="truncate pr-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $user->name }}</h4>
                                    <x-admin.status-badge tone="success">
                                        {{ $user->jobTitle ? json_decode($user->jobTitle)->name : __('No title') }}
                                    </x-admin.status-badge>
                                </div>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                                    {{ $user->division ? json_decode($user->division)->name : __('No division') }}
                                </p>
                                @if ($user->nip)
                                    <p class="mt-1 text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                        {{ __('NIP') }}: {{ $user->nip }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <div>
                                <span class="block text-gray-400">{{ __('Phone') }}</span>
                                {{ $user->phone ?: '-' }}
                            </div>
                            <div>
                                <span class="block text-gray-400">{{ __('Gender') }}</span>
                                {{ $user->gender ? __(ucfirst($user->gender)) : '-' }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <x-actions.button type="button" wire:click="edit('{{ $user->id }}')"
                                variant="soft-primary" size="sm"
                                label="{{ __('Edit employee') }}: {{ $user->name }}">{{ __('Edit') }}</x-actions.button>
                            <x-actions.button type="button"
                                wire:click="confirmDeletion('{{ $user->id }}')"
                                variant="soft-danger" size="sm"
                                label="{{ __('Delete employee') }}: {{ $user->name }}">{{ __('Delete') }}</x-actions.button>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
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
                                <input type="radio" class="form-radio" name="gender" value="male"
                                    wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="female"
                                    wire:model="form.gender">
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
                                <input type="radio" class="form-radio" name="gender" value="male"
                                    wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="female"
                                    wire:model="form.gender">
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
    <x-overlays.modal wire:model="showDetail">
        @if ($form->user)
            <div class="overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-white shadow-[0_18px_48px_-32px_rgba(15,23,42,0.35)] dark:border-slate-800 dark:bg-slate-950">
                <div class="border-b border-slate-200/80 bg-white px-8 py-7 dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-5">
                            <img class="h-20 w-20 rounded-full border border-slate-200 bg-slate-50 object-cover dark:border-slate-700 dark:bg-slate-900"
                                src="{{ $form->user->profile_photo_url }}" alt="{{ $form->user->name }}">
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary-700 dark:text-primary-300">
                                    {{ __('Employee Profile') }}
                                </div>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ $form->user->name }}</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $form->user->email }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $form->user->jobTitle?->name ?? __('No job title') }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        {{ $form->user->division?->name ?? __('No division') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:min-w-[28rem]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('NIP') }}</div>
                                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $form->user->nip ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Phone') }}</div>
                                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $form->user->phone ?: '-' }}</div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-900">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Education') }}</div>
                                <div class="mt-1.5 text-sm font-semibold text-slate-900 dark:text-white">{{ $form->user->education?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50/80 px-8 py-8 dark:bg-slate-950">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                            <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Professional') }}</div>
                            <dl class="mt-5 space-y-4">
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Job Title') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->jobTitle?->name ?? '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Division') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->division?->name ?? '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Education') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->education?->name ?? '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-6">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Hourly Rate') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $form->user->hourly_rate ? 'Rp ' . number_format((float) $form->user->hourly_rate, 0, ',', '.') : '-' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                            <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Personal') }}</div>
                            <dl class="mt-5 space-y-4">
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Gender') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $form->user->gender ? __(\Illuminate\Support\Str::headline($form->user->gender)) : '-' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Birth Place') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->birth_place ?: '-' }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-6 border-b border-slate-200 pb-4 dark:border-slate-800">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Birth Date') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">
                                        {{ $form->user->birth_date ? \Illuminate\Support\Carbon::parse($form->user->birth_date)->translatedFormat('d M Y') : '-' }}
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-6">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ __('Phone') }}</dt>
                                    <dd class="text-right text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->phone ?: '-' }}</dd>
                                </div>
                            </dl>
                        </section>

                        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
                            <div class="text-sm font-semibold text-slate-950 dark:text-white">{{ __('Address') }}</div>
                            <div class="mt-5 grid grid-cols-1 gap-6 lg:grid-cols-[1.4fr_1fr]">
                                <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-7 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200">
                                    {{ $form->user->address ?: __('No address saved.') }}
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-950">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Province') }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->provinsi?->nama ?? '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-950">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('City') }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kabupaten?->nama ?? '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-950">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('District') }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kecamatan?->nama ?? '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3.5 dark:border-slate-800 dark:bg-slate-950">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Village') }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{{ $form->user->kelurahan?->nama ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        @endif
    </x-overlays.modal>
</div>
