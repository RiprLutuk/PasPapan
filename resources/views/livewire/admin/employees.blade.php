<div>
    <div class="mx-auto max-w-7xl px-2 sm:px-0 lg:px-0">
        <!-- Header -->
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                    {{ __('Employee Management') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Manage your organization\'s workforce, roles, and access.') }}
                </p>
            </div>
            @unless(auth()->user()->is_demo)
            <x-button wire:click="showCreating" class="!bg-primary-600 hover:!bg-primary-700">
                <x-heroicon-m-plus class="mr-2 h-4 w-4" />
                {{ __('Add Employee') }}
            </x-button>
            @endunless
        </div>

        <!-- Filters -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Search -->
            <div class="relative col-span-1 sm:col-span-2 lg:col-span-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <x-heroicon-m-magnifying-glass class="h-5 w-5 text-gray-400" />
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" 
                    placeholder="{{ __('Search name, NIP...') }}" 
                    class="block w-full rounded-lg border-0 py-2 pl-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-gray-700 sm:text-sm sm:leading-6">
            </div>

            <!-- Division Filter -->
            <div class="col-span-1">
                 <x-tom-select id="filter_division" wire:model.live="division" placeholder="{{ __('All Divisions') }}"
                    :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
            </div>

            <!-- Job Title Filter -->
            <div class="col-span-1">
                <x-tom-select id="filter_jobTitle" wire:model.live="jobTitle" placeholder="{{ __('All Job Titles') }}"
                    :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
            </div>
            
            <!-- Education Filter -->
             <div class="col-span-1">
                 <x-tom-select id="filter_education" wire:model.live="education" placeholder="{{ __('All Education') }}"
                    :options="App\Models\Education::all()->map(fn($e) => ['id' => $e->id, 'name' => $e->name])" />
            </div>
        </div>

        <!-- Content -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full whitespace-nowrap text-left text-sm">
                    <thead class="bg-gray-50 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Employee') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Details') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Contact') }}</th>
                            <th scope="col" class="px-6 py-4 font-medium">{{ __('Location') }}</th>
                            <th scope="col" class="px-6 py-4 text-right font-medium">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($users as $user)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-6 py-4">
                                     <div class="flex items-center gap-4">
                                        <div class="h-10 w-10 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700 ring-2 ring-white dark:ring-gray-800">
                                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-400/30 w-fit">
                                            {{ $user->jobTitle ? json_decode($user->jobTitle)->name : '-' }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $user->division ? json_decode($user->division)->name : '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-900 dark:text-white font-medium">{{ $user->phone }}</div>
                                    <div class="text-xs text-gray-500">NIP: {{ $user->nip }}</div>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $user->city ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="show('{{ $user->id }}')" class="text-gray-400 hover:text-primary-600 transition-colors" title="{{ __('View') }}">
                                            <x-heroicon-m-eye class="h-5 w-5" />
                                        </button>
                                        @unless(auth()->user()->is_demo)
                                        <button wire:click="edit('{{ $user->id }}')" class="text-gray-400 hover:text-blue-600 transition-colors" title="{{ __('Edit') }}">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')" class="text-gray-400 hover:text-red-600 transition-colors" title="{{ __('Delete') }}">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <x-heroicon-o-users class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" />
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
            <div class="grid grid-cols-1 sm:hidden divide-y divide-gray-200 dark:divide-gray-700">
                 @foreach ($users as $user)
                    <div class="p-4 space-y-3">
                        <div class="flex items-start gap-3">
                             <img class="h-10 w-10 rounded-full object-cover" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" />
                             <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate pr-2">{{ $user->name }}</h4>
                                    <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/20 dark:text-blue-400">
                                        {{ $user->jobTitle ? json_decode($user->jobTitle)->name : '-' }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
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
                             @unless(auth()->user()->is_demo)
                             <button wire:click="edit('{{ $user->id }}')" class="text-blue-600 text-xs font-medium uppercase tracking-wide">Edit</button>
                             <button wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')" class="text-red-600 text-xs font-medium uppercase tracking-wide">Delete</button>
                             @endunless
                        </div>
                    </div>
                 @endforeach
            </div>

             @if($users->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modals (Confirmation & Edit/Create) -->
    <!-- Retaining original modal logic but ensuring styles are compatible -->
    <x-confirmation-modal wire:model="confirmingDeletion">
        <x-slot name="title">{{ __('Delete Employee') }}</x-slot>
        <x-slot name="content">{{ __('Are you sure you want to delete') }} <b>{{ $deleteName }}</b>? {{ __('This action cannot be undone.') }}</x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-secondary-button>
            <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">{{ __('Confirm Delete') }}</x-danger-button>
        </x-slot>
    </x-confirmation-modal>

    <!-- Create/Edit Modal -->
    <x-dialog-modal wire:model="creating">
         <x-slot name="title">{{ __('New Employee') }}</x-slot>
         <x-slot name="content">
            <form wire:submit="create">
                 @csrf
                 <!-- Form Fields (Same as original but cleaned up if needed) -->
                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                     <!-- Name -->
                     <div class="sm:col-span-2">
                        <x-label for="create_name" value="{{ __('Full Name') }}" />
                        <x-input id="create_name" type="text" class="mt-1 block w-full" wire:model="form.name" />
                        <x-input-error for="form.name" class="mt-2" />
                     </div>
                     
                     <!-- Email -->
                     <div>
                        <x-label for="create_email" value="{{ __('Email') }}" />
                        <x-input id="create_email" type="email" class="mt-1 block w-full" wire:model="form.email" />
                        <x-input-error for="form.email" class="mt-2" />
                     </div>

                     <!-- NIP -->
                     <div>
                        <x-label for="create_nip" value="{{ __('NIP') }}" />
                        <x-input id="create_nip" type="text" class="mt-1 block w-full" wire:model="form.nip" />
                        <x-input-error for="form.nip" class="mt-2" />
                     </div>

                    <!-- Password -->
                     <div class="sm:col-span-2">
                        <x-label for="create_password" value="{{ __('Password') }}" />
                        <x-input id="create_password" type="password" class="mt-1 block w-full" wire:model="form.password" placeholder="{{ __('Leave blank for default: password') }}" />
                        <x-input-error for="form.password" class="mt-2" />
                     </div>

                     <!-- Phone -->
                     <div>
                        <x-label for="create_phone" value="{{ __('Phone') }}" />
                        <x-input id="create_phone" type="text" class="mt-1 block w-full" wire:model="form.phone" />
                        <x-input-error for="form.phone" class="mt-2" />
                     </div>

                     <!-- Gender -->
                     <div>
                        <x-label value="{{ __('Gender') }}" />
                        <div class="mt-3 flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="male" wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="female" wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Female') }}</span>
                            </label>
                        </div>
                        <x-input-error for="form.gender" class="mt-2" />
                    </div>

                    <!-- City -->
                    <div>
                        <x-label for="create_city" value="{{ __('City') }}" />
                        <x-input id="create_city" type="text" class="mt-1 block w-full" wire:model="form.city" />
                        <x-input-error for="form.city" class="mt-2" />
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <x-label for="create_address" value="{{ __('Address') }}" />
                        <x-textarea id="create_address" class="mt-1 block w-full" wire:model="form.address" rows="2" />
                        <x-input-error for="form.address" class="mt-2" />
                    </div>

                     <!-- Division & Job Title (Full Width) -->
                     <div class="sm:col-span-2 space-y-4">
                        <div>
                             <x-label for="create_division" value="{{ __('Division') }}" />
                             <div class="mt-1">
                                <x-tom-select id="create_division" wire:model.live="form.division_id" placeholder="{{ __('Select Division') }}"
                                    :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                             </div>
                             <x-input-error for="form.division_id" class="mt-2" />
                        </div>
                         <div>
                             <x-label for="create_jobTitle" value="{{ __('Job Title') }}" />
                             <div class="mt-1" wire:key="create-job-title-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-tom-select id="create_jobTitle" wire:model.live="form.job_title_id" placeholder="{{ __('Select Job Title') }}"
                                    :options="$availableJobTitles->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
                             </div>
                             <x-input-error for="form.job_title_id" class="mt-2" />
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
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-label for="create_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-input id="create_basic_salary" type="text" class="mt-1 block w-full" x-model="displayValue" @input="update" placeholder="e.g. 5.000.000" />
                            <x-input-error for="form.basic_salary" class="mt-2" />
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
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-label for="create_hourly_rate" value="{{ __('Hourly Rate (Rp)') }}" />
                            <x-input id="create_hourly_rate" type="text" class="mt-1 block w-full" x-model="displayValue" @input="update" placeholder="e.g. 25.000" />
                            <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to auto-calc (Salary / 173)') }}</p>
                            <x-input-error for="form.hourly_rate" class="mt-2" />
                        </div>
                     </div>
                 </div>
            </form>
         </x-slot>
         <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-secondary-button>
            <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">{{ __('Create Employee') }}</x-button>
         </x-slot>
    </x-dialog-modal>

    <!-- Edit Modal (Reusing similar structure) -->
    <x-dialog-modal wire:model="editing">
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
                        <x-label for="edit_name" value="{{ __('Full Name') }}" />
                        <x-input id="edit_name" type="text" class="mt-1 block w-full" wire:model="form.name" />
                        <x-input-error for="form.name" class="mt-2" />
                     </div>
                     
                     <!-- Email -->
                     <div>
                        <x-label for="edit_email" value="{{ __('Email') }}" />
                        <x-input id="edit_email" type="email" class="mt-1 block w-full" wire:model="form.email" />
                        <x-input-error for="form.email" class="mt-2" />
                     </div>

                     <!-- NIP -->
                     <div>
                        <x-label for="edit_nip" value="{{ __('NIP') }}" />
                        <x-input id="edit_nip" type="text" class="mt-1 block w-full" wire:model="form.nip" />
                        <x-input-error for="form.nip" class="mt-2" />
                     </div>

                     <!-- Password (Optional for Edit) -->
                     <div class="sm:col-span-2">
                        <x-label for="edit_password" value="{{ __('Password') }}" />
                        <x-input id="edit_password" type="password" class="mt-1 block w-full" wire:model="form.password" placeholder="{{ __('Leave blank to keep current password') }}" />
                        <x-input-error for="form.password" class="mt-2" />
                     </div>

                     <!-- Phone -->
                     <div class="sm:col-span-2">
                        <x-label for="edit_phone" value="{{ __('Phone') }}" />
                        <x-input id="edit_phone" type="text" class="mt-1 block w-full" wire:model="form.phone" />
                        <x-input-error for="form.phone" class="mt-2" />
                     </div>

                     <!-- Gender -->
                     <div class="sm:col-span-2">
                        <x-label value="{{ __('Gender') }}" />
                        <div class="mt-3 flex gap-4">
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="male" wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Male') }}</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" class="form-radio" name="gender" value="female" wire:model="form.gender">
                                <span class="ml-2 text-sm">{{ __('Female') }}</span>
                            </label>
                        </div>
                        <x-input-error for="form.gender" class="mt-2" />
                     </div>

                    <!-- City -->
                    <div>
                        <x-label for="edit_city" value="{{ __('City') }}" />
                        <x-input id="edit_city" type="text" class="mt-1 block w-full" wire:model="form.city" />
                        <x-input-error for="form.city" class="mt-2" />
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <x-label for="edit_address" value="{{ __('Address') }}" />
                        <x-textarea id="edit_address" class="mt-1 block w-full" wire:model="form.address" rows="2" />
                        <x-input-error for="form.address" class="mt-2" />
                    </div>
                     
                     <!-- Division & Job Title -->
                     <div class="sm:col-span-2 space-y-4">
                        <div>
                             <x-label for="edit_division" value="{{ __('Division') }}" />
                             <div class="mt-1">
                                <x-tom-select id="edit_division" wire:model.live="form.division_id" placeholder="{{ __('Select Division') }}"
                                    :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
                             </div>
                        </div>
                         <div>
                             <x-label for="edit_jobTitle" value="{{ __('Job Title') }}" />
                             <div class="mt-1" wire:key="edit-job-title-wrapper-{{ $form->division_id ?? 'all' }}">
                                <x-tom-select id="edit_jobTitle" wire:model.live="form.job_title_id" placeholder="{{ __('Select Job Title') }}"
                                    :options="$availableJobTitles->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
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
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-label for="edit_basic_salary" value="{{ __('Basic Salary (Rp)') }}" />
                            <x-input id="edit_basic_salary" type="text" class="mt-1 block w-full" x-model="displayValue" @input="update" placeholder="e.g. 5.000.000" />
                            <x-input-error for="form.basic_salary" class="mt-2" />
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
                        }" x-init="displayValue = format(model); $watch('model', value => displayValue = format(value))">
                            <x-label for="edit_hourly_rate" value="{{ __('Hourly Rate (Rp)') }}" />
                            <x-input id="edit_hourly_rate" type="text" class="mt-1 block w-full" x-model="displayValue" @input="update" placeholder="e.g. 25.000" />
                            <p class="text-xs text-gray-500 mt-1">{{ __('Leave blank to auto-calc (Salary / 173)') }}</p>
                            <x-input-error for="form.hourly_rate" class="mt-2" />
                        </div>
                     </div>
                 </div>
            </form>
         </x-slot>
         <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">{{ __('Cancel') }}</x-secondary-button>
            <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">{{ __('Save Changes') }}</x-button>
         </x-slot>
    </x-dialog-modal>
    
    <!-- Detail Modal -->
    <x-modal wire:model="showDetail">
        @if ($form->user)
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl">
             <!-- Cover/Header -->
             <div class="h-32 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-t-lg"></div>
             <div class="px-6 pb-6">
                 <div class="relative flex justify-between items-end -mt-12 mb-6">
                     <img class="h-24 w-24 rounded-full ring-4 ring-white dark:ring-gray-800 object-cover bg-white" src="{{ $form->user->profile_photo_url }}" alt="{{ $form->user->name }}">
                 </div>
                 
                 <div class="mb-6">
                     <h3 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $form->user->name }}</h3>
                     <p class="text-sm text-gray-500 dark:text-gray-400">{{ $form->user->email }}</p>
                 </div>

                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                     <div class="space-y-4">
                         <div>
                             <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Professional') }}</label>
                             <div class="mt-2 space-y-2">
                                 <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">NIP</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->nip }}</span>
                                 </div>
                                 <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">Job Title</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->jobTitle ? json_decode($form->user->jobTitle)->name : '-' }}</span>
                                 </div>
                                  <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">Division</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->division ? json_decode($form->user->division)->name : '-' }}</span>
                                 </div>
                             </div>
                         </div>
                     </div>
                     
                     <div class="space-y-4">
                          <div>
                             <label class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('Personal') }}</label>
                             <div class="mt-2 space-y-2">
                                 <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">Phone</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->phone }}</span>
                                 </div>
                                 <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">Gender</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ __($form->user->gender) }}</span>
                                 </div>
                                  <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                     <span class="text-sm text-gray-600 dark:text-gray-300">City</span>
                                     <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $form->user->city ?? '-' }}</span>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
        </div>
        @endif
    </x-modal>

</div>
