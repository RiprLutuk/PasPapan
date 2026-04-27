@php
    $selectedProvince = data_get($state, 'provinsi_kode');
    $selectedRegency = data_get($state, 'kabupaten_kode');
    $selectedDistrict = data_get($state, 'kecamatan_kode');

    $provinces = \App\Models\Wilayah::query()
        ->whereRaw('LENGTH(kode) = 2')
        ->orderBy('nama')
        ->get()
        ->map(fn($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama]);

    $regencies = $selectedProvince
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedProvince . '.%')
            ->whereRaw('LENGTH(kode) = 5')
            ->orderBy('nama')
            ->get()
            ->map(fn($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();

    $districts = $selectedRegency
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedRegency . '.%')
            ->whereRaw('LENGTH(kode) = 8')
            ->orderBy('nama')
            ->get()
            ->map(fn($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();

    $villages = $selectedDistrict
        ? \App\Models\Wilayah::query()
            ->where('kode', 'like', $selectedDistrict . '.%')
            ->whereRaw('LENGTH(kode) = 13')
            ->orderBy('nama')
            ->get()
            ->map(fn($wilayah) => ['id' => $wilayah->kode, 'name' => $wilayah->nama])
        : collect();
@endphp

<x-sections.form-section submit="updateProfileInformation" class="profile-card">
    <x-slot name="icon">
        <x-heroicon-o-user class="h-6 w-6" />
    </x-slot>

    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="form">
        <!-- Profile Photo -->
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="window.profilePhotoEditor({
                initialPhotoUrl: @js($this->user->profile_photo_url),
                defaultFileName: @js(\Illuminate\Support\Str::slug($this->user->name ?: 'profile-photo') . '.jpg'),
                messages: {
                    invalidFile: @js(__('Please choose a valid image file.')),
                    uploadFailed: @js(__('The photo could not be uploaded. Please try again.')),
                    processFailed: @js(__('The photo could not be processed. Please try another image.')),
                }
            })" class="col-span-6">
                <input type="file" id="profile-photo-input" class="sr-only" x-ref="photo"
                    accept="image/png,image/jpeg,image/jpg" x-on:change.stop="handleFileChange($event)" />

                <div class="profile-photo-panel">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <label for="profile-photo-input"
                                class="group relative inline-flex h-24 w-24 cursor-pointer items-center justify-center overflow-hidden rounded-full border-4 border-white bg-primary-50 text-primary-700 shadow-md outline-none ring-offset-2 transition hover:scale-[1.01] focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-gray-900 dark:bg-primary-950/40 dark:text-primary-300">
                                <template x-if="currentPhotoUrl">
                                    <img x-bind:src="currentPhotoUrl" alt="{{ __('Profile Photo') }}"
                                        class="h-full w-full object-cover" />
                                </template>
                                <template x-if="! currentPhotoUrl">
                                    <x-heroicon-s-user-circle class="h-16 w-16" />
                                </template>

                                <span
                                    class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-gray-950/70 px-2 py-1 text-[11px] font-semibold text-white transition group-hover:bg-gray-950/80">
                                    <x-heroicon-s-pencil-square class="h-3.5 w-3.5" />
                                    <span>{{ __('Change Photo') }}</span>
                                </span>
                            </label>

                            <div class="max-w-xl space-y-1">
                                <x-forms.label for="profile-photo-input" value="{{ __('Profile Photo') }}" />
                                <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                                    {{ __('Tap the profile photo to choose a new image. The photo can be cropped and will save immediately after you confirm it.') }}
                                </p>
                                <div class="flex flex-wrap items-center gap-3 pt-1 text-sm">
                                    @if ($this->user->profile_photo_path)
                                        <button type="button" wire:click="deleteProfilePhoto"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-2 font-medium text-gray-700 transition hover:border-red-200 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-red-900/60 dark:hover:text-red-300">
                                            <x-heroicon-o-trash class="h-4 w-4" />
                                            <span>{{ __('Remove Photo') }}</span>
                                        </button>
                                    @endif

                                    <span x-show="uploading" x-cloak
                                        class="inline-flex items-center gap-2 text-sm font-medium text-primary-700 dark:text-primary-300">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"
                                            aria-hidden="true">
                                            <circle class="opacity-30" cx="12" cy="12" r="9"
                                                stroke="currentColor" stroke-width="3"></circle>
                                            <path class="opacity-100" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor"
                                                stroke-width="3" stroke-linecap="round"></path>
                                        </svg>
                                        <span>{{ __('Uploading photo...') }}</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <x-forms.input-error for="photo" class="mt-2" />

                <p x-show="uploadError" x-cloak x-text="uploadError"
                    class="mt-2 text-sm font-medium text-red-600 dark:text-red-400"></p>

                <div x-show="cropModalOpen" x-cloak x-trap.inert.noscroll="cropModalOpen"
                    x-on:keydown.escape.window="closeCropModal()"
                    class="fixed inset-0 z-50 overflow-y-auto overscroll-contain px-3 py-3 sm:px-4 sm:py-6">
                    <div class="fixed inset-0 bg-gray-950/70" x-on:click="closeCropModal()"></div>

                    <div class="relative z-10 flex min-h-full items-start justify-center sm:items-center">
                        <div
                            class="w-full max-w-2xl overflow-hidden rounded-[2rem] border border-primary-100 bg-white shadow-2xl dark:border-primary-900/60 dark:bg-gray-900">
                            <div class="border-b border-primary-100 px-5 py-4 dark:border-primary-900/50 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ __('Adjust Photo') }}</h3>
                                        <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                            {{ __('Center your face inside the frame. You can drag the photo and zoom if needed before saving.') }}
                                        </p>
                                    </div>

                                    <button type="button" x-on:click="closeCropModal()"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:text-white">
                                        <x-heroicon-o-x-mark class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-4 px-4 py-4 sm:space-y-5 sm:px-6 sm:py-5">
                                <div class="flex justify-center">
                                    <div
                                        class="relative overflow-hidden rounded-[1.75rem] border border-primary-100 bg-gradient-to-br from-primary-50 via-white to-primary-100/60 p-3 dark:border-primary-900/50 dark:from-gray-900 dark:via-gray-900 dark:to-primary-950/20 sm:p-4">
                                        <canvas x-ref="cropCanvas" width="320" height="320"
                                            x-on:pointerdown.prevent="startDrag($event)"
                                            x-on:pointermove.prevent="onDrag($event)"
                                            x-on:pointerup.prevent="stopDrag()" x-on:pointercancel.prevent="stopDrag()"
                                            x-on:pointerleave="stopDrag()"
                                            x-bind:class="{ 'cursor-grabbing': dragging, 'cursor-grab': !dragging }"
                                            class="h-64 w-64 touch-none rounded-[1.4rem] bg-white shadow-inner dark:bg-gray-950 sm:h-80 sm:w-80"></canvas>

                                        <div
                                            class="pointer-events-none absolute inset-3 rounded-[1.4rem] ring-2 ring-primary-500/80 ring-offset-4 ring-offset-white dark:ring-primary-400 dark:ring-offset-gray-900 sm:inset-4">
                                        </div>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                                    <div>
                                        <label for="profile-photo-zoom"
                                            class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                            {{ __('Zoom') }}
                                        </label>
                                        <input id="profile-photo-zoom" type="range" min="1" max="3"
                                            step="0.05" x-model="zoom" x-on:input="renderCropCanvas()"
                                            class="mt-3 h-2 w-full cursor-pointer appearance-none rounded-full bg-primary-100 accent-primary-600 dark:bg-primary-950/60 dark:accent-primary-400">
                                    </div>

                                    <div
                                        class="rounded-2xl border border-primary-100 bg-primary-50/70 px-4 py-3 text-sm leading-6 text-primary-800 dark:border-primary-900/50 dark:bg-primary-950/30 dark:text-primary-200">
                                        {{ __('Drag inside the frame to reposition your photo.') }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="flex flex-col-reverse gap-3 border-t border-primary-100 bg-gray-50/90 px-5 py-4 dark:border-primary-900/50 dark:bg-gray-950/70 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                                <x-actions.secondary-button type="button" x-on:click="closeCropModal()">
                                    {{ __('Cancel') }}
                                </x-actions.secondary-button>

                                <button type="button" x-on:click="saveCroppedPhoto()" x-bind:disabled="uploading"
                                    class="inline-flex items-center justify-center gap-2 rounded-full bg-primary-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400">
                                    <svg x-show="uploading" x-cloak class="h-4 w-4 animate-spin" viewBox="0 0 24 24"
                                        fill="none" aria-hidden="true">
                                        <circle class="opacity-30" cx="12" cy="12" r="9"
                                            stroke="currentColor" stroke-width="3"></circle>
                                        <path class="opacity-100" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor"
                                            stroke-width="3" stroke-linecap="round"></path>
                                    </svg>
                                    <span>{{ __('Save Photo') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
                :options="[['id' => 'male', 'name' => __('Male')], ['id' => 'female', 'name' => __('Female')]]" placeholder="{{ __('Select Gender') }}" />
            <x-forms.input-error for="gender" class="mt-2" />
        </div>

        <!-- Birth Date -->
        <div class="col-span-6 md:col-span-3">
            <x-forms.label for="birth_date" value="{{ __('Birth Date') }}" />
            <x-forms.input id="birth_date" type="date" class="mt-1 block w-full"
                value="{{ $state['birth_date'] ?? '' }}" wire:model="state.birth_date" autocomplete="bday" />
            <x-forms.input-error for="birth_date" class="mt-2" />
        </div>

        <!-- Birth Place -->
        <div class="col-span-6 md:col-span-3">
            <x-forms.label for="birth_place" value="{{ __('Birth Place') }}" />
            <x-forms.input id="birth_place" type="text" class="mt-1 block w-full" wire:model="state.birth_place"
                autocomplete="bday-place" />
            <x-forms.input-error for="birth_place" class="mt-2" />
        </div>

        <div class="col-span-6">
            <div class="profile-field-group">
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
                            <x-forms.tom-select id="provinsi_kode" wire:model.live="state.provinsi_kode"
                                :options="$provinces" aria-describedby="profile-region-help"
                                placeholder="{{ __('Pilih Provinsi') }}" />
                        </div>
                        <x-forms.input-error for="provinsi_kode" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="kabupaten_kode" value="{{ __('Kabupaten / Kota') }}" />
                        <div class="mt-1" wire:key="profile-regency-{{ $selectedProvince ?? 'empty' }}">
                            <x-forms.tom-select id="kabupaten_kode" wire:model.live="state.kabupaten_kode"
                                :options="$regencies" aria-describedby="profile-region-help"
                                placeholder="{{ __('Pilih Kabupaten/Kota') }}" />
                        </div>
                        <x-forms.input-error for="kabupaten_kode" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="kecamatan_kode" value="{{ __('Kecamatan') }}" />
                        <div class="mt-1" wire:key="profile-district-{{ $selectedRegency ?? 'empty' }}">
                            <x-forms.tom-select id="kecamatan_kode" wire:model.live="state.kecamatan_kode"
                                :options="$districts" aria-describedby="profile-region-help"
                                placeholder="{{ __('Pilih Kecamatan') }}" />
                        </div>
                        <x-forms.input-error for="kecamatan_kode" class="mt-2" />
                    </div>

                    <div>
                        <x-forms.label for="kelurahan_kode" value="{{ __('Kelurahan / Desa') }}" />
                        <div class="mt-1" wire:key="profile-village-{{ $selectedDistrict ?? 'empty' }}">
                            <x-forms.tom-select id="kelurahan_kode" wire:model.live="state.kelurahan_kode"
                                :options="$villages" aria-describedby="profile-region-help"
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
            <x-forms.textarea id="address" type="text" rows="4" class="mt-2 block w-full"
                wire:model="state.address" required autocomplete="street-address"
                aria-describedby="profile-address-help" />
            <x-forms.input-error for="address" class="mt-2" />
        </div>

        <!-- Division -->
        <div class="col-span-6 xl:col-span-2">
            <x-forms.label for="division" value="{{ __('Division') }}" />
            <x-forms.tom-select id="division" class="mt-1 block w-full" wire:model.live="state.division_id"
                :options="App\Models\Division::all()" placeholder="{{ __('Select Division') }}" />
            <x-forms.input-error for="division" class="mt-2" />
        </div>

        <!-- Education -->
        <div class="col-span-6 xl:col-span-2">
            <x-forms.label for="education" value="{{ __('Last Education') }}" />
            <x-forms.tom-select id="education" class="mt-1 block w-full" wire:model.live="state.education_id"
                :options="App\Models\Education::all()" placeholder="{{ __('Select Education') }}" />
            <x-forms.input-error for="education" class="mt-2" />
        </div>

        <!-- Job title -->
        <div class="col-span-6 xl:col-span-2">
            <x-forms.label for="job_title" value="{{ __('Job Title') }}" />
            <x-forms.tom-select id="job_title" class="mt-1 block w-full" wire:model.live="state.job_title_id"
                :options="App\Models\JobTitle::all()" placeholder="{{ __('Select Job Title') }}" />
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
