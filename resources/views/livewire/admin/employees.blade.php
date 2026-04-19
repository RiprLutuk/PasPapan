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
            <!-- Desktop Table -->
            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Details') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Contact') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="h-10 w-10 overflow-hidden rounded-full bg-gray-100 ring-2 ring-white dark:bg-gray-700 dark:ring-gray-800">
                                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}"
                                                class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <x-admin.status-badge tone="info" class="w-fit">
                                            {{ $user->jobTitle ? json_decode($user->jobTitle)->name : '-' }}
                                        </x-admin.status-badge>
                                        <span
                                            class="text-xs text-gray-500">{{ $user->division ? json_decode($user->division)->name : '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $user->phone }}</div>
                                    <div class="text-xs text-gray-500">NIP: {{ $user->nip }}</div>
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
                                            wire:click="confirmDeletion('{{ $user->id }}', @js($user->name))"
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
                    <div class="space-y-3 p-4">
                        <div class="flex items-start gap-3">
                            <img class="h-10 w-10 rounded-full object-cover" src="{{ $user->profile_photo_url }}"
                                alt="{{ $user->name }}" />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between">
                                    <h4 class="truncate pr-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $user->name }}</h4>
                                    <x-admin.status-badge tone="info">
                                        {{ $user->jobTitle ? json_decode($user->jobTitle)->name : '-' }}
                                    </x-admin.status-badge>
                                </div>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <div>
                                <span class="block text-gray-400">NIP</span>
                                {{ $user->nip }}
                            </div>
                            <div>
                                <span class="block text-gray-400">Division</span>
                                {{ $user->division ? json_decode($user->division)->name : '-' }}
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <x-actions.button type="button" wire:click="edit('{{ $user->id }}')"
                                variant="soft-primary" size="sm"
                                label="{{ __('Edit employee') }}: {{ $user->name }}">{{ __('Edit') }}</x-actions.button>
                            <x-actions.button type="button"
                                wire:click="confirmDeletion('{{ $user->id }}', @js($user->name))"
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
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                <!-- Cover/Header -->
                <div class="h-32 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-t-lg"></div>
                <div class="px-6 pb-6">
                    <div class="relative flex justify-between items-end -mt-12 mb-6">
                        <img class="h-24 w-24 rounded-full ring-4 ring-white dark:ring-gray-800 object-cover bg-white"
                            src="{{ $form->user->profile_photo_url }}" alt="{{ $form->user->name }}">
                    </div>

                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $form->user->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $form->user->email }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Professional') }}</label>
                                <div class="mt-2 space-y-2">
                                    <div
                                        class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">NIP</span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->nip }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">Job Title</span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->jobTitle ? json_decode($form->user->jobTitle)->name : '-' }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">Division</span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->division ? json_decode($form->user->division)->name : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label
                                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Personal') }}</label>
                                <div class="mt-2 space-y-2">
                                    <div
                                        class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">Phone</span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->phone }}</span>
                                    </div>
                                    <div
                                        class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                        <span class="text-sm text-gray-600 dark:text-gray-300">Gender</span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ __($form->user->gender) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-overlays.modal>
</div>
