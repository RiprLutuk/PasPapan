<div>
  <div class="mb-4 flex-col items-center gap-5 sm:flex-row md:flex md:justify-between lg:mr-4">
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 md:mb-0">
      Data Admin
    </h3>
    @if (Auth::user()->isSuperadmin)
      <x-button wire:click="showCreating" class="w-full sm:w-auto justify-center">
        <x-heroicon-o-plus class="mr-2 h-4 w-4" /> Tambah Admin
      </x-button>
    @endif
  </div>

  <!-- Mobile Card View -->
  <div class="grid grid-cols-1 gap-4 sm:hidden mb-4">
    @foreach ($users as $user)
      @php
            $wireClick = "wire:click=show('$user->id')";
      @endphp
      <div class="bg-white dark:bg-gray-800 shadow rounded-xl p-4 border border-gray-100 dark:border-gray-700">
          <div class="flex items-start gap-4 mb-4">
             <div class="shrink-0" {{ $wireClick }}>
                 @if($user->profile_photo_url)
                    <img class="h-12 w-12 rounded-full object-cover" src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" />
                 @else
                    <div class="h-12 w-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-gray-500">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                 @endif
              </div>
              <div class="flex-1 min-w-0" {{ $wireClick }}>
                  <h4 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                      {{ $user->name }}
                  </h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                      {{ $user->email }}
                  </p>
                   <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 mt-1">
                      {{ $user->group }}
                  </span>
              </div>
          </div>
          
           <div class="text-sm text-gray-600 dark:text-gray-300 mb-4" {{ $wireClick }}>
              <div class="flex justify-between">
                  <span class="text-gray-500">Phone</span>
                  <span class="font-medium">{{ $user->phone ?? '-' }}</span>
              </div>
          </div>

          @if (Auth::user()->isSuperadmin || Auth::user()->id == $user->id)
            <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="edit('{{ $user->id }}')"
                    class="flex items-center justify-center gap-2 px-2 py-1 text-sm font-medium text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-900/30 rounded-lg transition-colors {{ (Auth::user()->isSuperadmin && $user->isUser) ? '' : 'col-span-2' }}">
                    <x-heroicon-o-pencil class="w-4 h-4" />
                </button>
                @if (Auth::user()->isSuperadmin && $user->isUser)
                    <button wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')"
                        class="flex items-center justify-center gap-2 px-2 py-1 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-300 dark:bg-red-900/30 rounded-lg transition-colors">
                        <x-heroicon-o-trash class="w-4 h-4" />
                    </button>
                @endif
            </div>
          @endif
      </div>
    @endforeach
  </div>

  <div class="hidden sm:block overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="relative px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300">
            No.
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
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @php
          $class = 'cursor-pointer group-hover:bg-gray-100 dark:group-hover:bg-gray-700';
        @endphp
        @foreach ($users as $user)
          @php
            $wireClick = "wire:click=show('$user->id')";
          @endphp
          <tr wire:key="{{ $user->id }}" class="group">
            <td class="{{ $class }} p-2 text-center text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $loop->iteration }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->name }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->email }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->group }}
            </td>
            <td class="{{ $class }} px-6 py-4 text-sm font-medium text-gray-900 dark:text-white"
              {{ $wireClick }}>
              {{ $user->phone }}
            </td>
            <td class="relative flex justify-center gap-2 px-6 py-4">
              @if (Auth::user()->isSuperadmin || Auth::user()->id == $user->id)
                <x-button wire:click="edit('{{ $user->id }}')" class="px-2 py-1">
                  <x-heroicon-o-pencil class="w-4 h-4" />
                </x-button>
                @if (Auth::user()->isSuperadmin && $user->isUser)
                  <x-danger-button wire:click="confirmDeletion('{{ $user->id }}', '{{ $user->name }}')" class="px-2 py-1">
                    <x-heroicon-o-trash class="w-4 h-4" />
                  </x-danger-button>
                @endif
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-3">
    {{ $users->links() }}
  </div>

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Admin
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus <b>{{ $deleteName }}</b>?
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
        {{ __('Confirm') }}
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>

  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Admin Baru
    </x-slot>

    <x-slot name="content">
      <form wire:submit="create">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <!-- Profile Photo File Input -->
            <input type="file" id="create_photo" class="hidden" wire:model.live="form.photo" x-ref="photo"
              x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

            <x-label for="create_photo" value="{{ __('Photo') }}" />

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

            <x-secondary-button class="me-2 mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
              {{ __('Select A New Photo') }}
            </x-secondary-button>

            @if ($this->user->profile_photo_path ?? false)
              <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                {{ __('Remove Photo') }}
              </x-secondary-button>
            @endif

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2" />
            @enderror
          </div>
        @endif
        <div class="mt-4">
          <x-label for="create_name">Nama Admin</x-label>
          <x-input id="create_name" class="mt-1 block w-full" type="text" wire:model="form.name" autocomplete="off" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="create_email">{{ __('Email') }}</x-label>
            <x-input id="create_email" class="mt-1 block w-full" type="email" wire:model="form.email"
              placeholder="example@example.com" required autocomplete="off" />
            @error('form.email')
              <x-input-error for="form.email" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="create_nip">NIP</x-label>
            <x-input id="create_nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
              placeholder="12345678" required autocomplete="off" />
            @error('form.nip')
              <x-input-error for="form.nip" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="create_password">{{ __('Password') }}</x-label>
            <x-input id="create_password" class="mt-1 block w-full" type="password" wire:model="form.password"
              placeholder="New Password" required autocomplete="new-password" />
            <p class="text-sm dark:text-gray-400">Default password: <b>admin</b></p>
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="form.group" value="{{ __('Group') }}" />
            <x-tom-select id="form.group" wire:model="form.group" placeholder="{{ __('Select Group') }}"
                :options="collect($groups)->filter(fn($g) => $g != 'user')->map(fn($g) => ['id' => $g, 'name' => $g])->values()->toArray()" />
            @error('form.group')
              <x-input-error for="form.group" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="create_phone">{{ __('Phone') }}</x-label>
            <x-input id="create_phone" class="mt-1 block w-full" type="number" wire:model="form.phone"
              placeholder="+628123456789" autocomplete="off" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="create_city">{{ __('City') }}</x-label>
            <x-input id="create_city" class="mt-1 block w-full" type="text" wire:model="form.city"
              placeholder="Domisili" autocomplete="off" />
            @error('form.city')
              <x-input-error for="form.city" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="create_address">{{ __('Address') }}</x-label>
            <x-input id="create_address" class="mt-1 block w-full" type="text" wire:model="form.address"
              placeholder="Jl. Jend. Sudirman" autocomplete="off" />
            @error('form.address')
              <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4">
          <x-label for="create_division" value="{{ __('Division') }}" />
          <x-tom-select id="create_division" wire:model="form.division_id" placeholder="{{ __('Select Division') }}"
              :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
          @error('form.division_id')
            <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4">
          <x-label for="create_jobTitle" value="{{ __('Job Title') }}" />
          <x-tom-select id="create_jobTitle" wire:model="form.job_title_id" placeholder="{{ __('Select Job Title') }}"
              :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
          @error('form.job_title_id')
            <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled" wire:target="form.photo">
        {{ __('Confirm') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Admin
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="update" id="user-edit">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
          <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <!-- Profile Photo File Input -->
            <input type="file" id="edit_photo" class="hidden" wire:model.live="form.photo" x-ref="photo"
              x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

            <x-label for="edit_photo" value="{{ __('Photo') }}" />

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

            <x-secondary-button class="me-2 mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
              {{ __('Select A New Photo') }}
            </x-secondary-button>

            @if ($form->user?->profile_photo_path)
              <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                {{ __('Remove Photo') }}
              </x-secondary-button>
            @endif

            @error('form.photo')
              <x-input-error for="form.photo" message="{{ $message }}" class="mt-2" />
            @enderror
          </div>
        @endif
        <div class="mt-4">
          <x-label for="edit_name">Nama Admin</x-label>
          <x-input id="edit_name" class="mt-1 block w-full" type="text" wire:model="form.name" autocomplete="off" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_email">{{ __('Email') }}</x-label>
            <x-input id="edit_email" class="mt-1 block w-full" type="email" wire:model="form.email"
              placeholder="example@example.com" required autocomplete="off" />
            @error('form.email')
              <x-input-error for="form.email" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="edit_nip">NIP</x-label>
            <x-input id="edit_nip" class="mt-1 block w-full" type="text" wire:model="form.nip"
              placeholder="12345678" required autocomplete="off" />
            @error('form.nip')
              <x-input-error for="form.nip" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_password">{{ __('Password') }}</x-label>
            <x-input id="edit_password" class="mt-1 block w-full" type="password" wire:model="form.password"
              placeholder="New Password" autocomplete="new-password" />
            @error('form.password')
              <x-input-error for="form.password" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_phone">{{ __('Phone') }}</x-label>
            <x-input id="edit_phone" class="mt-1 block w-full" type="text" wire:model="form.phone"
              placeholder="+628123456789" autocomplete="off" />
            @error('form.phone')
              <x-input-error for="form.phone" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_city">{{ __('City') }}</x-label>
            <x-input id="edit_city" class="mt-1 block w-full" type="text" wire:model="form.city"
              placeholder="Domisili" autocomplete="off" />
            @error('form.city')
              <x-input-error for="form.city" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="edit_address">{{ __('Address') }}</x-label>
            <x-input id="edit_address" class="mt-1 block w-full" type="text" wire:model="form.address"
              placeholder="Jl. Jend. Sudirman" autocomplete="off" />
            @error('form.address')
              <x-input-error for="form.address" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
        <div class="mt-4">
          <x-label for="edit_division" value="{{ __('Division') }}" />
          <x-tom-select id="edit_division" wire:model="form.division_id" placeholder="{{ __('Select Division') }}"
              :options="App\Models\Division::all()->map(fn($d) => ['id' => $d->id, 'name' => $d->name])" />
          @error('form.division_id')
            <x-input-error for="form.division_id" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4">
          <x-label for="edit_jobTitle" value="{{ __('Job Title') }}" />
          <x-tom-select id="edit_jobTitle" wire:model="form.job_title_id" placeholder="{{ __('Select Job Title') }}"
              :options="App\Models\JobTitle::all()->map(fn($j) => ['id' => $j->id, 'name' => $j->name])" />
          @error('form.job_title_id')
            <x-input-error for="form.job_title_id" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled" wire:target="form.photo">
        {{ __('Confirm') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <x-modal wire:model="showDetail">
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
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Email') }}</span>
            <p>{{ $form->user->email }}</p>
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Phone') }}</span>
            <p>{{ $form->user->phone }}</p>
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Group') }}</span>
            <p>{{ __($form->user->group) }}</p>
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Birth Date') }}</span>
            @if ($form->user->birth_date)
              <p>{{ \Illuminate\Support\Carbon::parse($form->user->birth_date)->format('D d M Y') }}</p>
            @else
              <p>-</p>
            @endif
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Birth Place') }}</span>
            <p>{{ $form->user->birth_place ?? '-' }}</p>
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Address') }}</span>
            @if (empty($form->user->address))
              <p>-</p>
            @else
              <p>{{ $form->user->address }}</p>
            @endif
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('City') }}</span>
            @if (empty($form->user->city))
              <p>-</p>
            @else
              <p>{{ $form->user->city }}</p>
            @endif
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Job Title') }}</span>
            <p>{{ $jobTitle }}</p>
          </div>
          <div class="mt-4">
            <span class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Division') }}</span>
            <p>{{ $division }}</p>
          </div>
          <div class="mt-4">
            <x-label for="education_id" value="{{ __('Last Education') }}" />
            <p>{{ $education }}</p>
          </div>
        </div>
      </div>
    @endif
  </x-modal>
</div>
