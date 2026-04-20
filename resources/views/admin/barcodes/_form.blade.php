@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $barcode = $barcode ?? null;
    $dynamicEnabled = old('dynamic_enabled', $barcode?->dynamic_enabled ?? false);
    $ttlValue = old('dynamic_ttl_seconds', $barcode?->dynamic_ttl_seconds ?: 60);
    $radiusValue = old('radius', $barcode?->radius);
    $latitudeValue = old('lat', $barcode?->latLng['lat'] ?? null);
    $longitudeValue = old('lng', $barcode?->latLng['lng'] ?? null);
@endphp

<div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
    <form action="{{ $action }}" method="post" class="p-5 lg:p-7">
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        <div class="flex flex-col gap-4 border-b border-gray-100 pb-5 dark:border-gray-700 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-600 dark:text-primary-300">
                    {{ $isEdit ? __('Barcode Setup') : __('New Checkpoint') }}
                </p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                    {{ $heading }}
                </h2>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ $subheading }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-actions.secondary-button href="{{ route('admin.barcodes') }}">
                    {{ __('Back') }}
                </x-actions.secondary-button>

                @if ($isEdit && $barcode?->dynamic_enabled)
                    <x-actions.button href="{{ route('admin.barcodes.dynamic-display', $barcode) }}" variant="warning">
                        {{ __('Live Display') }}
                    </x-actions.button>
                @endif

                @if ($isEdit)
                    <x-actions.button
                        type="button"
                        variant="secondary"
                        data-confirm-message="{{ __('Regenerate the dynamic secret now? Any currently displayed QR will stop working immediately.') }}"
                        onclick="if (confirm(this.dataset.confirmMessage)) { document.getElementById('regenerate-secret-form').submit(); }"
                    >
                        {{ __('Regenerate Secret') }}
                    </x-actions.button>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-900/70">
                    <div class="mb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 1') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Checkpoint Info') }}</h3>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <x-forms.label for="name" value="{{ __('Checkpoint Name') }}" />
                            <x-forms.input
                                name="name"
                                id="name"
                                class="mt-1 block w-full"
                                type="text"
                                :value="old('name', $barcode?->name)"
                                placeholder="{{ __('Main Gate') }}"
                            />
                            @error('name')
                                <x-forms.input-error for="name" class="mt-2" message="{{ $message }}" />
                            @enderror
                        </div>

                        <div>
                            <x-forms.label for="radius" value="{{ __('Attendance Radius (m)') }}" />
                            <x-forms.input
                                name="radius"
                                id="radius"
                                class="mt-1 block w-full"
                                type="number"
                                :value="$radiusValue"
                                placeholder="50"
                            />
                            @error('radius')
                                <x-forms.input-error for="radius" class="mt-2" message="{{ $message }}" />
                            @enderror
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-900/70">
                    <div class="mb-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 2') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('QR Mode') }}</h3>
                    </div>

                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white/80 p-4 dark:border-slate-700 dark:bg-slate-800/80">
                        <input
                            id="dynamic_enabled"
                            name="dynamic_enabled"
                            type="checkbox"
                            value="1"
                            @checked($dynamicEnabled)
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        >
                        <span class="block">
                            <span class="block text-sm font-semibold text-slate-950 dark:text-white">{{ __('Use Dynamic QR') }}</span>
                            <span class="mt-1 block text-sm text-slate-500 dark:text-slate-400">
                                {{ __('Enable this for rotating QR tokens. Leave it off for a fixed static code.') }}
                            </span>
                        </span>
                    </label>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1fr)_260px]">
                        <div id="barcode-value-field">
                            <x-forms.label for="value" value="{{ __('Static Barcode Value') }}" />
                            @if ($isEdit)
                                @livewire('admin.barcode-value-input-component', ['value' => old('value', $barcode?->value)])
                            @else
                                @livewire('admin.barcode-value-input-component')
                            @endif
                            <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Used only when dynamic QR is off.') }}
                            </p>
                        </div>

                        <div id="dynamic-barcode-config" class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200">
                            <x-forms.label for="dynamic_ttl_seconds" value="{{ __('Refresh Every') }}" />
                            <div class="mt-2 flex items-center gap-3">
                                <x-forms.input
                                    name="dynamic_ttl_seconds"
                                    id="dynamic_ttl_seconds"
                                    class="block w-full"
                                    type="number"
                                    min="30"
                                    max="300"
                                    :value="$ttlValue"
                                />
                                <span class="text-xs font-semibold uppercase tracking-[0.14em]">{{ __('sec') }}</span>
                            </div>
                            <p class="mt-2 text-xs text-emerald-700/90 dark:text-emerald-300/90">
                                {{ __('30 to 60 seconds is usually enough.') }}
                            </p>
                            @error('dynamic_ttl_seconds')
                                <x-forms.input-error for="dynamic_ttl_seconds" class="mt-2" message="{{ $message }}" />
                            @enderror
                        </div>
                    </div>
                </section>
            </div>

            <section class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5 dark:border-slate-700 dark:bg-slate-900/70">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">{{ __('Step 3') }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950 dark:text-white">{{ __('Checkpoint Location') }}</h3>
                    </div>

                    <x-actions.button type="button" id="detect-current-location" variant="secondary">
                        <x-heroicon-o-map-pin class="mr-2 h-5 w-5" />
                        {{ __('Use Current Location') }}
                    </x-actions.button>
                </div>

                <p id="location-helper-status" class="mb-4 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Set the pin from your current device position or adjust the coordinates manually.') }}
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="lat" value="{{ __('Latitude') }}" />
                        <x-forms.input
                            name="lat"
                            id="lat"
                            class="mt-1 block w-full"
                            type="text"
                            :value="$latitudeValue"
                            placeholder="-6.12345"
                        />
                        @error('lat')
                            <x-forms.input-error for="lat" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>

                    <div>
                        <x-forms.label for="lng" value="{{ __('Longitude') }}" />
                        <x-forms.input
                            name="lng"
                            id="lng"
                            class="mt-1 block w-full"
                            type="text"
                            :value="$longitudeValue"
                            placeholder="106.12345"
                        />
                        @error('lng')
                            <x-forms.input-error for="lng" class="mt-2" message="{{ $message }}" />
                        @enderror
                    </div>
                </div>

                <div id="map" class="mt-4 h-80 w-full overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700"></div>
            </section>
        </div>

        <div class="mt-6 flex flex-col gap-3 border-t border-gray-100 pt-5 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ $isEdit ? __('Save changes after adjusting the QR mode, radius, or location.') : __('Create the checkpoint once the radius and location are correct.') }}
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <x-actions.secondary-button href="{{ route('admin.barcodes') }}">
                    {{ __('Cancel') }}
                </x-actions.secondary-button>
                <x-actions.button>
                    {{ $submitLabel }}
                </x-actions.button>
            </div>
        </div>
    </form>
</div>
