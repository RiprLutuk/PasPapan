<x-form-section submit="updateProfileInformation">
    <x-slot name="icon">
        <x-heroicon-o-user class="h-6 w-6" />
    </x-slot>

    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

  <x-slot name="description">
    {{ __('Update your account\'s profile information and email address.') }}
  </x-slot>

  <x-slot name="form">
    <!-- Profile Photo -->
    @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
      <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6">
        <!-- Profile Photo File Input -->
        <input type="file" id="photo" class="hidden" wire:model.live="photo" x-ref="photo"
          x-on:change="
                                    photoName = $refs.photo.files[0].name;
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        photoPreview = e.target.result;
                                    };
                                    reader.readAsDataURL($refs.photo.files[0]);
                            " />

        <x-label for="photo" value="{{ __('Photo') }}" />

        <!-- Current Profile Photo -->
        <div class="mt-2" x-show="! photoPreview">
          <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}"
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

        @if ($this->user->profile_photo_path)
          <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
            {{ __('Remove Photo') }}
          </x-secondary-button>
        @else
          <x-secondary-button type="button" class="mt-2" x-show="photoPreview"
            x-on:click="photoName = null; photoPreview = null">
            {{ __('Remove Photo') }}
          </x-secondary-button>
        @endif

        <x-input-error for="photo" class="mt-2" />
      </div>
    @endif

    <!-- Name -->
    <div class="col-span-6">
      <x-label for="name" value="{{ __('Name') }}" />
      <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required
        autocomplete="name" />
      <x-input-error for="name" class="mt-2" />
    </div>

    <!-- NIP -->
    <div class="col-span-6">
      <x-label for="nip" value="{{ __('NIP') }}" />
      <x-input id="nip" type="text" class="mt-1 block w-full" wire:model="state.nip" required
        autocomplete="nip" />
      <x-input-error for="nip" class="mt-2" />
    </div>

    <!-- Email -->
    <div class="col-span-6">
      <x-label for="email" value="{{ __('Email') }}" />
      <x-input id="email" type="email" class="mt-1 block w-full" wire:model="state.email" required
        autocomplete="username" />
      <x-input-error for="email" class="mt-2" />

      @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) &&
              !$this->user->hasVerifiedEmail())
        <p class="mt-2 text-sm dark:text-white">
          {{ __('Your email address is unverified.') }}

          <button type="button"
            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
            wire:click.prevent="sendEmailVerification">
            {{ __('Click here to re-send the verification email.') }}
          </button>
        </p>

        @if ($this->verificationLinkSent)
          <p class="mt-2 text-sm font-medium text-green-600 dark:text-green-400">
            {{ __('A new verification link has been sent to your email address.') }}
          </p>
        @endif
      @endif
    </div>

    <div class="col-span-6 flex flex-row gap-3">
      <!-- Phone Number -->
      <div class="w-full">
        <x-label for="phone" value="{{ __('Phone Number') }}" />
        <x-input id="phone" type="text" class="mt-1 block w-full" wire:model="state.phone" required />
        <x-input-error for="phone" class="mt-2" />
      </div>

      <!-- Gender -->
      <div class="w-full">
        <x-label for="gender" value="{{ __('Gender') }}" />
        <x-tom-select id="gender" class="mt-1 block w-full" wire:model="state.gender"
            :options="[
                ['id' => 'male', 'name' => __('Male')],
                ['id' => 'female', 'name' => __('Female')]
            ]"
            placeholder="{{ __('Select Gender') }}" />
        <x-input-error for="gender" class="mt-2" />
      </div>
    </div>

    <!-- Address -->
    <div class="col-span-6">
      <x-label for="address" value="{{ __('Address') }}" />
      <x-textarea id="address" type="text" class="mt-1 block w-full" wire:model="state.address" required />
      <x-input-error for="address" class="mt-2" />
    </div>

    <!-- City -->
    <div class="col-span-6">
      <x-label for="city" value="{{ __('City') }}" />
      <x-input id="city" type="text" class="mt-1 block w-full" wire:model="state.city" required />
      <x-input-error for="city" class="mt-2" />
    </div>

    <div class="col-span-6 flex flex-row gap-3">
      <!-- Birth Date -->
      <div class="w-full">
        <x-label for="birth_date" value="{{ __('Birth Date') }}" />
        <x-input id="birth_date" type="date" class="mt-1 block w-full" value="{{ $state['birth_date'] }}"
          wire:model="state.birth_date" />
        <x-input-error for="birth_date" class="mt-2" />
      </div>

      <!-- Birth Place -->
      <div class="w-full">
        <x-label for="birth_place" value="{{ __('Birth Place') }}" />
        <x-input id="birth_place" type="text" class="mt-1 block w-full" wire:model="state.birth_place" />
        <x-input-error for="birth_place" class="mt-2" />
      </div>
    </div>

    <!-- Division -->
    <div class="col-span-6">
      <x-label for="division" value="{{ __('Division') }}" />
      <x-tom-select id="division" class="mt-1 block w-full" wire:model="state.division_id" :options="App\Models\Division::all()" placeholder="Select Division" />
      <x-input-error for="division" class="mt-2" />
    </div>

    <!-- Education -->
    <div class="col-span-6">
      <x-label for="education" value="{{ __('Last Education') }}" />
      <x-tom-select id="education" class="mt-1 block w-full" wire:model="state.education_id" :options="App\Models\Education::all()" placeholder="Select Education" />
      <x-input-error for="education" class="mt-2" />
    </div>

    <!-- Job title -->
    <div class="col-span-6">
      <x-label for="job_title" value="{{ __('Job Title') }}" />
      <x-tom-select id="job_title" class="mt-1 block w-full" wire:model="state.job_title_id" :options="App\Models\JobTitle::all()" placeholder="Select Job Title" />
      <x-input-error for="job_title" class="mt-2" />
    </div>

    <!-- Language -->
    <div class="col-span-6">
      <x-label for="language" value="{{ __('Language') }}" />
      <x-tom-select id="language" class="mt-1 block w-full" wire:model="state.language"
          :options="[
              ['id' => 'id', 'name' => 'Bahasa Indonesia'],
              ['id' => 'en', 'name' => 'English']
          ]"
          placeholder="{{ __('Select Language') }}" />
      <x-input-error for="language" class="mt-2" />
    </div>
  </x-slot>

  <x-slot name="actions">
    <x-action-message class="me-3" on="saved">
      {{ __('Saved.') }}
    </x-action-message>

    <x-button wire:loading.attr="disabled" wire:target="photo">
      {{ __('Save') }}
    </x-button>
  </x-slot>

  <script>
      document.addEventListener('livewire:initialized', () => {
          Livewire.on('saved', () => {
              setTimeout(() => {
                  window.location.reload();
              }, 1000);
          });
      });
  </script>
</x-form-section>
