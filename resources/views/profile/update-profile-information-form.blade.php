@php
    $selectedProvince = data_get($state, 'provinsi_kode');
    $selectedRegency = data_get($state, 'kabupaten_kode');
    $selectedDistrict = data_get($state, 'kecamatan_kode');

    $provinces = \App\Models\Wilayah::query()
        ->whereRaw('LENGTH(kode) = 2')
        ->orderBy('nama')
        ->get()
        ->map(fn ($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama]);

    $regencies = $selectedProvince
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedProvince . '.%')
            ->whereRaw('LENGTH(kode) = 5')
            ->orderBy('nama')
            ->get()
            ->map(fn ($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();

    $districts = $selectedRegency
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedRegency . '.%')
            ->whereRaw('LENGTH(kode) = 8')
            ->orderBy('nama')
            ->get()
            ->map(fn ($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();

    $villages = $selectedDistrict
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedDistrict . '.%')
            ->whereRaw('LENGTH(kode) = 13')
            ->orderBy('nama')
            ->get()
            ->map(fn ($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();
@endphp

<x-sections.form-section submit="updateProfileInformation">
    <x-slot name="icon">
        <x-heroicon-o-user class="h-6 w-6" />
    </x-slot>

    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

  <x-slot name="description">
    {{ __('Update your profile details, contact information, and domicile area.') }}
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

        <x-forms.label for="photo" value="{{ __('Photo') }}" />

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

        <x-actions.secondary-button class="me-2 mt-2" type="button" x-on:click.prevent="$refs.photo.click()">
          {{ __('Select A New Photo') }}
        </x-actions.secondary-button>

        @if ($this->user->profile_photo_path)
          <x-actions.secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
            {{ __('Remove Photo') }}
          </x-actions.secondary-button>
        @else
          <x-actions.secondary-button type="button" class="mt-2" x-show="photoPreview"
            x-on:click="photoName = null; photoPreview = null">
            {{ __('Remove Photo') }}
          </x-actions.secondary-button>
        @endif

        <x-forms.input-error for="photo" class="mt-2" />
      </div>
    @endif

    <!-- Name -->
    <div class="col-span-6 md:col-span-3">
      <x-forms.label for="name" value="{{ __('Name') }}" />
      <x-forms.input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required
        autocomplete="name" />
      <x-forms.input-error for="name" class="mt-2" />
    </div>

    <!-- NIP -->
    <div class="col-span-6 md:col-span-3">
      <x-forms.label for="nip" value="{{ __('NIP') }}" />
      <x-forms.input id="nip" type="text" class="mt-1 block w-full" wire:model="state.nip" required
        autocomplete="nip" />
      <x-forms.input-error for="nip" class="mt-2" />
    </div>

    <!-- Email -->
    <div class="col-span-6 md:col-span-3">
      <x-forms.label for="email" value="{{ __('Email') }}" />
      <x-forms.input id="email" type="email" class="mt-1 block w-full" wire:model="state.email" required
        autocomplete="username" />
      <x-forms.input-error for="email" class="mt-2" />

      @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) &&
              !$this->user->hasVerifiedEmail())
        <p class="mt-2 text-sm dark:text-white">
          {{ __('Your email address is unverified.') }}

          <button type="button"
            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
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

    <!-- Phone Number -->
    <div class="col-span-6 md:col-span-3">
      <x-forms.label for="phone" value="{{ __('Phone Number') }}" />
      <x-forms.input id="phone" type="text" class="mt-1 block w-full" wire:model="state.phone" required
        autocomplete="tel" inputmode="tel" />
      <x-forms.input-error for="phone" class="mt-2" />
    </div>

    <!-- Gender -->
    <div class="col-span-6 md:col-span-3">
      <x-forms.label for="gender" value="{{ __('Gender') }}" />
      <x-forms.tom-select id="gender" class="mt-1 block w-full" wire:model.live="state.gender"
          :options="[
              ['id' => 'male', 'name' => __('Male')],
              ['id' => 'female', 'name' => __('Female')]
          ]"
          placeholder="{{ __('Select Gender') }}" />
      <x-forms.input-error for="gender" class="mt-2" />
    </div>

    <!-- Birth Date -->
    <div class="col-span-6 md:col-span-3 xl:col-span-2">
      <x-forms.label for="birth_date" value="{{ __('Birth Date') }}" />
      <x-forms.input id="birth_date" type="date" class="mt-1 block w-full" value="{{ $state['birth_date'] ?? '' }}"
        wire:model="state.birth_date" autocomplete="bday" />
      <x-forms.input-error for="birth_date" class="mt-2" />
    </div>

    <!-- Birth Place -->
    <div class="col-span-6 md:col-span-3 xl:col-span-4">
      <x-forms.label for="birth_place" value="{{ __('Birth Place') }}" />
      <x-forms.input id="birth_place" type="text" class="mt-1 block w-full" wire:model="state.birth_place"
        autocomplete="bday-place" />
      <x-forms.input-error for="birth_place" class="mt-2" />
    </div>

    <div class="col-span-6">
      <div class="rounded-xl border border-primary-100 bg-primary-50/60 p-4 dark:border-primary-900/50 dark:bg-primary-950/20">
        <div class="flex flex-col gap-1">
          <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Domicile Area') }}</h4>
          <p id="profile-region-help" class="text-sm leading-6 text-gray-700 dark:text-gray-300">
            {{ __('Complete your domicile area so attendance and administrative settings remain accurate.') }}
          </p>
        </div>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <x-forms.label for="provinsi_kode" value="{{ __('Provinsi') }}" />
            <div class="mt-1" wire:key="profile-province">
              <x-forms.tom-select
                id="provinsi_kode"
                wire:model.live="state.provinsi_kode"
                :options="$provinces"
                aria-describedby="profile-region-help"
                placeholder="{{ __('Pilih Provinsi') }}" />
            </div>
            <x-forms.input-error for="provinsi_kode" class="mt-2" />
          </div>

          <div>
            <x-forms.label for="kabupaten_kode" value="{{ __('Kabupaten / Kota') }}" />
            <div class="mt-1" wire:key="profile-regency-{{ $selectedProvince ?? 'empty' }}">
              <x-forms.tom-select
                id="kabupaten_kode"
                wire:model.live="state.kabupaten_kode"
                :options="$regencies"
                aria-describedby="profile-region-help"
                placeholder="{{ __('Pilih Kabupaten/Kota') }}" />
            </div>
            <x-forms.input-error for="kabupaten_kode" class="mt-2" />
          </div>

          <div>
            <x-forms.label for="kecamatan_kode" value="{{ __('Kecamatan') }}" />
            <div class="mt-1" wire:key="profile-district-{{ $selectedRegency ?? 'empty' }}">
              <x-forms.tom-select
                id="kecamatan_kode"
                wire:model.live="state.kecamatan_kode"
                :options="$districts"
                aria-describedby="profile-region-help"
                placeholder="{{ __('Pilih Kecamatan') }}" />
            </div>
            <x-forms.input-error for="kecamatan_kode" class="mt-2" />
          </div>

          <div>
            <x-forms.label for="kelurahan_kode" value="{{ __('Kelurahan / Desa') }}" />
            <div class="mt-1" wire:key="profile-village-{{ $selectedDistrict ?? 'empty' }}">
              <x-forms.tom-select
                id="kelurahan_kode"
                wire:model.live="state.kelurahan_kode"
                :options="$villages"
                aria-describedby="profile-region-help"
                placeholder="{{ __('Pilih Kelurahan/Desa') }}" />
            </div>
            <x-forms.input-error for="kelurahan_kode" class="mt-2" />
          </div>
        </div>
      </div>
    </div>

    <!-- Address -->
    <div class="col-span-6">
      <x-forms.label for="address" value="{{ __('Address') }}" />
      <p id="profile-address-help" class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
        {{ __('Use a complete street address so location-based features and internal records stay aligned.') }}
      </p>
      <x-forms.textarea
        id="address"
        type="text"
        rows="4"
        class="mt-2 block w-full"
        wire:model="state.address"
        required
        autocomplete="street-address"
        aria-describedby="profile-address-help" />
      <x-forms.input-error for="address" class="mt-2" />
    </div>

    <!-- Division -->
    <div class="col-span-6 xl:col-span-2">
      <x-forms.label for="division" value="{{ __('Division') }}" />
      <x-forms.tom-select id="division" class="mt-1 block w-full" wire:model.live="state.division_id" :options="App\Models\Division::all()" placeholder="{{ __('Select Division') }}" />
      <x-forms.input-error for="division" class="mt-2" />
    </div>

    <!-- Education -->
    <div class="col-span-6 xl:col-span-2">
      <x-forms.label for="education" value="{{ __('Last Education') }}" />
      <x-forms.tom-select id="education" class="mt-1 block w-full" wire:model.live="state.education_id" :options="App\Models\Education::all()" placeholder="{{ __('Select Education') }}" />
      <x-forms.input-error for="education" class="mt-2" />
    </div>

    <!-- Job title -->
    <div class="col-span-6 xl:col-span-2">
      <x-forms.label for="job_title" value="{{ __('Job Title') }}" />
      <x-forms.tom-select id="job_title" class="mt-1 block w-full" wire:model.live="state.job_title_id" :options="App\Models\JobTitle::all()" placeholder="{{ __('Select Job Title') }}" />
      <x-forms.input-error for="job_title" class="mt-2" />
    </div>


  </x-slot>

  <x-slot name="actions">
    <x-actions.action-message class="me-3" on="saved">
      {{ __('Saved.') }}
    </x-actions.action-message>

    <x-actions.button wire:loading.attr="disabled" wire:target="photo">
      {{ __('Save') }}
    </x-actions.button>
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
</x-sections.form-section>
