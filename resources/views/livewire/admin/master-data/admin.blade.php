<x-admin.page-shell
    :title="__('Admin Data')"
    :description="__('Manage administrator accounts, roles, and contact information.')"
>
    <x-slot name="actions">
        @if (Auth::user()->isSuperadmin)
            <x-actions.button wire:click="showCreating" label="{{ __('Add Admin') }}">
                <x-heroicon-o-plus class="h-5 w-5" />
                <span>{{ __('Add Admin') }}</span>
            </x-actions.button>
        @endif
    </x-slot>

    <x-slot name="toolbar">
        <x-admin.page-tools grid-class="grid grid-cols-1 items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="sm:col-span-2 lg:col-span-2">
                <x-forms.label for="admin-search" value="{{ __('Search admins') }}" class="mb-1.5 block" />
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400 dark:text-gray-500">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.472 9.766l3.63 3.63a.75.75 0 1 0 1.06-1.06l-3.63-3.63A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0a4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <x-forms.input
                        id="admin-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by name, email, phone, or NIP...') }}"
                        class="w-full pl-11"
                    />
                </div>
            </div>

            <div>
                <x-forms.label for="admin-group-filter" value="{{ __('Group') }}" class="mb-1.5 block" />
                <x-forms.select id="admin-group-filter" wire:model.live="groupFilter" class="w-full">
                    <option value="all">{{ __('All groups') }}</option>
                    @foreach (collect($groups)->reject(fn ($group) => $group === 'user') as $group)
                        <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                    @endforeach
                </x-forms.select>
            </div>

            <div>
                <x-forms.label for="admin-per-page" value="{{ __('Rows per page') }}" class="mb-1.5 block" />
                <x-forms.select id="admin-per-page" wire:model.live="perPage" class="w-full">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </x-forms.select>
            </div>
        </x-admin.page-tools>
    </x-slot>

    <x-admin.panel>
        <div class="flex flex-col gap-2 border-b border-gray-200/70 px-6 py-5 dark:border-gray-700/70 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">{{ __('Admin Directory') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    @if ($users->count())
                        {{ __('Showing :from-:to of :total admins.', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}
                    @else
                        {{ __('No administrators found.') }}
                    @endif
                </p>
            </div>

            <x-admin.status-badge tone="primary">{{ __('Access management') }}</x-admin.status-badge>
        </div>

        @if ($users->count())
            <div class="grid grid-cols-1 gap-4 p-4 sm:hidden">
                @foreach ($users as $user)
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <button type="button" wire:click="show('{{ $user->id }}')"
                            class="w-full rounded-xl text-left transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2 dark:hover:bg-gray-700/60 dark:focus:ring-offset-gray-900"
                            aria-label="{{ __('View admin') }}: {{ $user->name }}">
                            <div class="mb-4 flex items-start gap-4">
                                <div class="shrink-0">
                                    @if ($user->profile_photo_url)
                                        <img class="h-12 w-12 rounded-full object-cover" src="{{ $user->profile_photo_url }}"
                                            alt="{{ $user->name }}" />
                                    @else
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-gray-500 dark:bg-gray-700">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h4 class="truncate text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $user->name }}
                                    </h4>
                                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $user->email }}
                                    </p>
                                    <span
                                        class="mt-1 inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $user->group }}
                                    </span>
                                </div>
                            </div>

                            <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">{{ __('Phone') }}</span>
                                    <span class="font-medium">{{ $user->phone ?? '-' }}</span>
                                </div>
                            </div>
                        </button>

                        <div class="flex flex-wrap justify-end gap-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                            <x-actions.button type="button" wire:click="show('{{ $user->id }}')" variant="soft-primary" size="sm" label="{{ __('View admin') }}: {{ $user->name }}">
                                <x-heroicon-o-eye class="h-4 w-4" />
                                <span>{{ __('View') }}</span>
                            </x-actions.button>
                            @if (Auth::user()->isSuperadmin || Auth::user()->id == $user->id)
                                <x-actions.button type="button" wire:click="edit('{{ $user->id }}')" variant="soft-primary" size="sm" label="{{ __('Edit admin') }}: {{ $user->name }}">
                                    <x-heroicon-o-pencil class="h-4 w-4" />
                                    <span>{{ __('Edit') }}</span>
                                </x-actions.button>
                                @if (Auth::user()->isSuperadmin && $user->isUser)
                                    <x-actions.button type="button" wire:click="confirmDeletion('{{ $user->id }}', @js($user->name))" variant="soft-danger" size="sm" label="{{ __('Delete admin') }}: {{ $user->name }}">
                                        <x-heroicon-o-trash class="h-4 w-4" />
                                        <span>{{ __('Delete') }}</span>
                                    </x-actions.button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col"
                                class="relative px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('No.') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('Name') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('Email') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('Group') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('Phone Number') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($users as $user)
                            <tr wire:key="{{ $user->id }}" class="group transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="p-2 text-center text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ($users->firstItem() ?? 1) + $loop->index }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->email }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->group }}
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $user->phone }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <x-actions.icon-button wire:click="show('{{ $user->id }}')" variant="primary" label="{{ __('View admin') }}: {{ $user->name }}">
                                            <x-heroicon-o-eye class="h-4 w-4" />
                                        </x-actions.icon-button>
                                        @if (Auth::user()->isSuperadmin || Auth::user()->id == $user->id)
                                            <x-actions.icon-button wire:click="edit('{{ $user->id }}')" variant="primary" label="{{ __('Edit admin') }}: {{ $user->name }}">
                                                <x-heroicon-o-pencil class="h-4 w-4" />
                                            </x-actions.icon-button>
                                            @if (Auth::user()->isSuperadmin && $user->isUser)
                                                <x-actions.icon-button
                                                    wire:click="confirmDeletion('{{ $user->id }}', @js($user->name))"
                                                    variant="danger"
                                                    label="{{ __('Delete admin') }}: {{ $user->name }}">
                                                    <x-heroicon-o-trash class="h-4 w-4" />
                                                </x-actions.icon-button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200/60 bg-gray-50/70 px-6 py-3 dark:border-gray-700/60 dark:bg-gray-900/40">
                    {{ $users->links() }}
                </div>
            @endif
        @else
            <x-admin.empty-state
                :title="filled($search) || $groupFilter !== 'all' ? __('No matching administrators found') : __('No administrators found')"
                :description="filled($search) || $groupFilter !== 'all'
                    ? __('Try changing the keyword or group filter to see more results.')
                    : __('Create admin accounts to manage access, monitoring, and operational settings.')"
                class="m-6 border-0 bg-transparent p-6 shadow-none dark:bg-transparent"
            >
                <x-slot name="icon">
                    <x-heroicon-o-users class="h-12 w-12 text-slate-300 dark:text-slate-600" />
                </x-slot>

                @if (Auth::user()->isSuperadmin)
                    <x-slot name="actions">
                        <x-actions.button type="button" wire:click="showCreating">
                            {{ __('Create Admin') }}
                        </x-actions.button>
                    </x-slot>
                @endif
            </x-admin.empty-state>
        @endif
    </x-admin.panel>

    <x-overlays.confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">
            {{ __('Delete Admin') }}
        </x-slot>

        <x-slot name="content">
            {{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>?
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
                {{ __('Confirm') }}
            </x-actions.danger-button>
        </x-slot>
    </x-overlays.confirmation-modal>

    <x-overlays.dialog-modal wire:model="creating">
        <x-slot name="title">
            {{ __('New Admin') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit="create">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
                        <!-- Profile Photo File Input -->
                        <input type="file" id="create_photo" class="hidden" wire:model.live="form.photo"
                            x-ref="photo"
                            x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

                        <x-forms.label for="create_photo" value="{{ __('Photo') }}" />

                        <!-- Current Profile Photo -->
                        <div class="mt-2" x-show="! photoPreview">
                            <img src="{{ $this->user->profile_photo_url ?? '' }}" alt="{{ $this->user->name ?? '' }}"
                                class="h-20 w-20 rounded-full object-cover">
                        </div>

                        <!-- New Profile Photo Preview -->
                        <div class="mt-2" x-show="photoPreview" style="display: none;">
                            <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                            </span>
                        </div>

                        <x-actions.button variant="secondary" size="sm" class="me-2 mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
                            {{ __('Select A New Photo') }}
                        </x-actions.button>

                        @if ($this->user->profile_photo_path ?? false)
                            <x-actions.button type="button" variant="soft-danger" size="sm" class="mt-2" wire:click="deleteProfilePhoto">
                                {{ __('Remove Photo') }}
                            </x-actions.button>
                        @endif

                        @error('form.photo')
                            <x-forms.input-error for="form.photo" message="{{ $message }}" class="mt-2" />
                        @enderror
                    </div>
                @endif
                <div class="mt-4">
                    <x-forms.label for="create_name">{{ __('Admin Name') }}</x-forms.label>
                    <x-forms.input id="create_name" class="mt-1 block w-full" type="text" wire:model="form.name"
                        autocomplete="off" />
                    @error('form.name')
                        <x-forms.input-error for="form.name" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="create_email">{{ __('Email') }}</x-forms.label>
                        <x-forms.input id="create_email" class="mt-1 block w-full" type="email" wire:model="form.email"
                            placeholder="example@example.com" required autocomplete="off" />
                        @error('form.email')
                            <x-forms.input-error for="form.email" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                    <div class="w-full">
                        <x-forms.label for="create_nip">NIP</x-forms.label>
                        <x-forms.input id="create_nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
                            placeholder="12345678" required autocomplete="off" />
                        @error('form.nip')
                            <x-forms.input-error for="form.nip" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="create_password">{{ __('Password') }}</x-forms.label>
                        <x-forms.input id="create_password" class="mt-1 block w-full" type="password"
                            wire:model="form.password" placeholder="New Password" required
                            autocomplete="new-password" />
                        <p class="text-sm dark:text-gray-400">{{ __('Default password admin') }}</p>
                        @error('form.password')
                            <x-forms.input-error for="form.password" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                    <div class="w-full">
                        <x-forms.label for="form.group" value="{{ __('Group') }}" />
                        <x-forms.tom-select id="form.group" wire:model="form.group" placeholder="{{ __('Select Group') }}"
                            :options="collect($groups)
                                ->filter(fn($g) => $g != 'user')
                                ->map(fn($g) => ['id' => $g, 'name' => $g])
                                ->values()
                                ->toArray()" />
                        @error('form.group')
                            <x-forms.input-error for="form.group" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="create_phone">{{ __('Phone') }}</x-forms.label>
                        <x-forms.input id="create_phone" class="mt-1 block w-full" type="number" wire:model="form.phone"
                            placeholder="+628123456789" autocomplete="off" />
                        @error('form.phone')
                            <x-forms.input-error for="form.phone" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="create_city">{{ __('City') }}</x-forms.label>
                        <x-forms.input id="create_city" class="mt-1 block w-full" type="text" wire:model="form.city"
                            placeholder="Domisili" autocomplete="off" />
                        @error('form.city')
                            <x-forms.input-error for="form.city" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                    <div class="w-full">
                        <x-forms.label for="create_address">{{ __('Address') }}</x-forms.label>
                        <x-forms.input id="create_address" class="mt-1 block w-full" type="text"
                            wire:model="form.address" placeholder="Jl. Jend. Sudirman" autocomplete="off" />
                        @error('form.address')
                            <x-forms.input-error for="form.address" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <x-forms.label for="create_division" value="{{ __('Division') }}" />
                    <x-forms.tom-select id="create_division" wire:model="form.division_id"
                        placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                    @error('form.division_id')
                        <x-forms.input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
                <div class="mt-4">
                    <x-forms.label for="create_jobTitle" value="{{ __('Job Title') }}" />
                    <x-forms.tom-select id="create_jobTitle" wire:model="form.job_title_id"
                        placeholder="{{ __('Select Job Title') }}" :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
                    @error('form.job_title_id')
                        <x-forms.input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" wire:click="create" wire:loading.attr="disabled" wire:target="form.photo">
                {{ __('Save') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model="editing">
        <x-slot name="title">
            {{ __('Edit Admin') }}
        </x-slot>

        <x-slot name="content">
            <form wire:submit.prevent="update" id="user-edit">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                    <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
                        <!-- Profile Photo File Input -->
                        <input type="file" id="edit_photo" class="hidden" wire:model.live="form.photo"
                            x-ref="photo"
                            x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

                        <x-forms.label for="edit_photo" value="{{ __('Photo') }}" />

                        <!-- Current Profile Photo -->
                        <div class="mt-2" x-show="! photoPreview">
                            <img src="{{ $form->user?->profile_photo_url }}" alt="{{ $form->user?->name }}"
                                class="h-20 w-20 rounded-full object-cover">
                        </div>

                        <!-- New Profile Photo Preview -->
                        <div class="mt-2" x-show="photoPreview" style="display: none;">
                            <span class="block h-20 w-20 rounded-full bg-cover bg-center bg-no-repeat"
                                x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                            </span>
                        </div>

                        <x-actions.button variant="secondary" size="sm" class="me-2 mt-2" type="button"
                            x-on:click.prevent="$refs.photo.click()">
                            {{ __('Select A New Photo') }}
                        </x-actions.button>

                        @if ($form->user?->profile_photo_path)
                            <x-actions.button type="button" variant="soft-danger" size="sm" class="mt-2" wire:click="deleteProfilePhoto">
                                {{ __('Remove Photo') }}
                            </x-actions.button>
                        @endif

                        @error('form.photo')
                            <x-forms.input-error for="form.photo" message="{{ $message }}" class="mt-2" />
                        @enderror
                    </div>
                @endif
                <div class="mt-4">
                    <x-forms.label for="edit_name">{{ __('Admin Name') }}</x-forms.label>
                    <x-forms.input id="edit_name" class="mt-1 block w-full" type="text" wire:model="form.name"
                        autocomplete="off" />
                    @error('form.name')
                        <x-forms.input-error for="form.name" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="edit_email">{{ __('Email') }}</x-forms.label>
                        <x-forms.input id="edit_email" class="mt-1 block w-full" type="email" wire:model="form.email"
                            placeholder="example@example.com" required autocomplete="off" />
                        @error('form.email')
                            <x-forms.input-error for="form.email" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                    <div class="w-full">
                        <x-forms.label for="edit_nip">NIP</x-forms.label>
                        <x-forms.input id="edit_nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
                            placeholder="12345678" required autocomplete="off" />
                        @error('form.nip')
                            <x-forms.input-error for="form.nip" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="edit_password">{{ __('Password') }}</x-forms.label>
                        <x-forms.input id="edit_password" class="mt-1 block w-full" type="password"
                            wire:model="form.password" placeholder="New Password" autocomplete="new-password" />
                        @error('form.password')
                            <x-forms.input-error for="form.password" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="edit_phone">{{ __('Phone') }}</x-forms.label>
                        <x-forms.input id="edit_phone" class="mt-1 block w-full" type="text" wire:model="form.phone"
                            placeholder="+628123456789" autocomplete="off" />
                        @error('form.phone')
                            <x-forms.input-error for="form.phone" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
                    <div class="w-full">
                        <x-forms.label for="edit_city">{{ __('City') }}</x-forms.label>
                        <x-forms.input id="edit_city" class="mt-1 block w-full" type="text" wire:model="form.city"
                            placeholder="Domisili" autocomplete="off" />
                        @error('form.city')
                            <x-forms.input-error for="form.city" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                    <div class="w-full">
                        <x-forms.label for="edit_address">{{ __('Address') }}</x-forms.label>
                        <x-forms.input id="edit_address" class="mt-1 block w-full" type="text"
                            wire:model="form.address" placeholder="Jl. Jend. Sudirman" autocomplete="off" />
                        @error('form.address')
                            <x-forms.input-error for="form.address" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <x-forms.label for="edit_division" value="{{ __('Division') }}" />
                    <x-forms.tom-select id="edit_division" wire:model="form.division_id"
                        placeholder="{{ __('Select Division') }}" :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                    @error('form.division_id')
                        <x-forms.input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
                <div class="mt-4">
                    <x-forms.label for="edit_jobTitle" value="{{ __('Job Title') }}" />
                    <x-forms.tom-select id="edit_jobTitle" wire:model="form.job_title_id"
                        placeholder="{{ __('Select Job Title') }}" :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
                    @error('form.job_title_id')
                        <x-forms.input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
                    @enderror
                </div>
            </form>
        </x-slot>

        <x-slot name="footer">
            <x-actions.secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-actions.secondary-button>

            <x-actions.button class="ml-2" wire:click="update" wire:loading.attr="disabled" wire:target="form.photo">
                {{ __('Update') }}
            </x-actions.button>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.modal wire:model="showDetail">
        @if ($form->user)
            @php
                $division = $form->user->division ? json_decode($form->user->division)->name : '-';
                $jobTitle = $form->user->jobTitle ? json_decode($form->user->jobTitle)->name : '-';
                $education = $form->user->education ? json_decode($form->user->education)->name : '-';
            @endphp
            <div class="px-6 py-4">
                <div class="my-4 flex items-center justify-center">
                    <img class="h-32 w-32 rounded-full object-cover" src="{{ $form->user->profile_photo_url }}"
                        alt="{{ $form->user->name }}" title="{{ $form->user->name }}" />
                </div>

                <div class="text-center text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $form->user->name }}
                </div>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    <div class="mt-4">
                        <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">NIP</span>
                        <p>{{ $form->user->nip }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Email') }}</span>
                        <p>{{ $form->user->email }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Phone') }}</span>
                        <p>{{ $form->user->phone }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Group') }}</span>
                        <p>{{ __($form->user->group) }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Birth Date') }}</span>
                        @if ($form->user->birth_date)
                            <p>{{ \Illuminate\Support\Carbon::parse($form->user->birth_date)->format('D d M Y') }}</p>
                        @else
                            <p>-</p>
                        @endif
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Birth Place') }}</span>
                        <p>{{ $form->user->birth_place ?? '-' }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Address') }}</span>
                        @if (empty($form->user->address))
                            <p>-</p>
                        @else
                            <p>{{ $form->user->address }}</p>
                        @endif
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('City') }}</span>
                        @if (empty($form->user->city))
                            <p>-</p>
                        @else
                            <p>{{ $form->user->city }}</p>
                        @endif
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Job Title') }}</span>
                        <p>{{ $jobTitle }}</p>
                    </div>
                    <div class="mt-4">
                        <span
                            class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Division') }}</span>
                        <p>{{ $division }}</p>
                    </div>
                    <div class="mt-4">
                        <x-forms.label for="education_id" value="{{ __('Last Education') }}" />
                        <p>{{ $education }}</p>
                    </div>
                </div>
            </div>
        @endif
    </x-overlays.modal>
</x-admin.page-shell>
