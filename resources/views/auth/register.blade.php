<x-guest-layout>
    @php
        $selectedWilayahOptions = \App\Models\Wilayah::query()
            ->whereIn('kode', array_values(array_filter([
                old('provinsi_kode'),
                old('kabupaten_kode'),
                old('kecamatan_kode'),
                old('kelurahan_kode'),
            ])))
            ->get(['kode', 'nama'])
            ->mapWithKeys(fn ($wilayah) => [
                $wilayah->kode => [
                    'kode' => $wilayah->kode,
                    'nama' => $wilayah->nama,
                ],
            ])
            ->all();
    @endphp

    <div class="auth-shell">
        <div class="auth-shell__backdrop" aria-hidden="true"></div>

        <div class="auth-shell__container auth-shell__container--wide">
            <section class="auth-card lg:col-span-2" aria-labelledby="register-form-title">
                <div class="auth-card__header">
                    <p class="auth-card__eyebrow">{{ __('Register') }}</p>
                    <h2 id="register-form-title" class="auth-card__title">{{ __('Create an Account') }}</h2>
                    <p class="auth-card__copy">
                        {{ __('Complete your profile and account details below.') }}
                    </p>
                </div>

                <div class="auth-form" x-data="{ submitting: false }">
                    <form method="POST" action="{{ route('register') }}" class="space-y-6" novalidate @submit="submitting = true">
                        @csrf

                        <div class="auth-section-grid transition duration-200" :class="submitting ? 'opacity-85' : ''">
                            <fieldset class="auth-section">
                                <legend class="auth-section__title">{{ __('Account basics') }}</legend>
                                <div class="auth-section__header">
                                    <p class="auth-section__copy">
                                        {{ __('Start with the main identity and contact information for this account.') }}
                                    </p>
                                </div>

                                <div class="auth-grid">
                                    <div class="auth-field">
                                        <label for="name" class="auth-label">{{ __('Name') }}</label>
                                        <div class="auth-input-wrap">
                                            <div class="auth-input-icon" aria-hidden="true">
                                                <x-heroicon-o-user class="h-5 w-5" />
                                            </div>
                                            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name"
                                                aria-describedby="@error('name') name-error @enderror"
                                                aria-invalid="@error('name') true @else false @enderror"
                                                class="auth-input auth-input--icon @error('name') auth-input--error @enderror"
                                                placeholder="{{ __('Full Name') }}">
                                        </div>
                                        @error('name')
                                            <p id="name-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="nip" class="auth-label">{{ __('NIP') }}</label>
                                        <div class="auth-input-wrap">
                                            <div class="auth-input-icon" aria-hidden="true">
                                                <x-heroicon-o-identification class="h-5 w-5" />
                                            </div>
                                            <input id="nip" name="nip" type="text" value="{{ old('nip') }}" autocomplete="off"
                                                aria-describedby="@error('nip') nip-error @enderror"
                                                aria-invalid="@error('nip') true @else false @enderror"
                                                class="auth-input auth-input--icon @error('nip') auth-input--error @enderror"
                                                placeholder="{{ __('Employee ID') }}">
                                        </div>
                                        @error('nip')
                                            <p id="nip-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="email" class="auth-label">{{ __('Email') }}</label>
                                        <div class="auth-input-wrap">
                                            <div class="auth-input-icon" aria-hidden="true">
                                                <x-heroicon-o-at-symbol class="h-5 w-5" />
                                            </div>
                                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username"
                                                aria-describedby="@error('email') email-error @enderror"
                                                aria-invalid="@error('email') true @else false @enderror"
                                                class="auth-input auth-input--icon @error('email') auth-input--error @enderror"
                                                placeholder="{{ __('email@example.com') }}">
                                        </div>
                                        @error('email')
                                            <p id="email-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="phone" class="auth-label">{{ __('Phone Number') }}</label>
                                        <div class="auth-input-wrap">
                                            <div class="auth-input-icon" aria-hidden="true">
                                                <x-heroicon-o-phone class="h-5 w-5" />
                                            </div>
                                            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required autocomplete="tel" inputmode="numeric" pattern="[0-9]+"
                                                aria-describedby="@error('phone') phone-error @enderror"
                                                aria-invalid="@error('phone') true @else false @enderror"
                                                class="auth-input auth-input--icon @error('phone') auth-input--error @enderror"
                                                placeholder="{{ __('0812...') }}">
                                        </div>
                                        @error('phone')
                                            <p id="phone-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field auth-field--full">
                                        <label for="gender" class="auth-label">{{ __('Gender') }}</label>
                                        <select id="gender" name="gender" required
                                            aria-describedby="@error('gender') gender-error @enderror"
                                            aria-invalid="@error('gender') true @else false @enderror"
                                            class="auth-tom-select @error('gender') auth-input--error @enderror"
                                            data-placeholder="{{ __('Select Gender') }}">
                                            <option value="">{{ __('Select Gender') }}</option>
                                            <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                                            <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                                        </select>
                                        @error('gender')
                                            <p id="gender-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </fieldset>

                            <fieldset class="auth-section">
                                <legend class="auth-section__title">{{ __('Location details') }}</legend>
                                <div class="auth-section__header">
                                    <p class="auth-section__copy">
                                        {{ __('Complete your area and address so attendance settings can be configured correctly.') }}
                                    </p>
                                </div>

                                <div class="auth-grid">
                                    <div class="auth-field">
                                        <label for="provinsi_kode" class="auth-label">{{ __('Provinsi') }}</label>
                                        <select id="provinsi_kode" name="provinsi_kode" required
                                            aria-describedby="@error('provinsi_kode') provinsi-error @enderror"
                                            aria-invalid="@error('provinsi_kode') true @else false @enderror"
                                            class="auth-tom-select @error('provinsi_kode') auth-input--error @enderror"
                                            data-placeholder="{{ __('Select Province') }}"></select>
                                        @error('provinsi_kode')
                                            <p id="provinsi-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="kabupaten_kode" class="auth-label">{{ __('Kabupaten / Kota') }}</label>
                                        <select id="kabupaten_kode" name="kabupaten_kode" required
                                            aria-describedby="@error('kabupaten_kode') kabupaten-error @enderror"
                                            aria-invalid="@error('kabupaten_kode') true @else false @enderror"
                                            class="auth-tom-select @error('kabupaten_kode') auth-input--error @enderror"
                                            data-placeholder="{{ __('Select Regency or City') }}"></select>
                                        @error('kabupaten_kode')
                                            <p id="kabupaten-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="kecamatan_kode" class="auth-label">{{ __('Kecamatan') }}</label>
                                        <select id="kecamatan_kode" name="kecamatan_kode" required
                                            aria-describedby="@error('kecamatan_kode') kecamatan-error @enderror"
                                            aria-invalid="@error('kecamatan_kode') true @else false @enderror"
                                            class="auth-tom-select @error('kecamatan_kode') auth-input--error @enderror"
                                            data-placeholder="{{ __('Select District') }}"></select>
                                        @error('kecamatan_kode')
                                            <p id="kecamatan-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field">
                                        <label for="kelurahan_kode" class="auth-label">{{ __('Kelurahan / Desa') }}</label>
                                        <select id="kelurahan_kode" name="kelurahan_kode" required
                                            aria-describedby="@error('kelurahan_kode') kelurahan-error @enderror"
                                            aria-invalid="@error('kelurahan_kode') true @else false @enderror"
                                            class="auth-tom-select @error('kelurahan_kode') auth-input--error @enderror"
                                            data-placeholder="{{ __('Select Village or Subdistrict') }}"></select>
                                        @error('kelurahan_kode')
                                            <p id="kelurahan-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="auth-field auth-field--full">
                                        <label for="address" class="auth-label">{{ __('Address') }}</label>
                                        <textarea id="address" name="address" rows="3" required
                                            aria-describedby="@error('address') address-error @enderror"
                                            aria-invalid="@error('address') true @else false @enderror"
                                            class="auth-textarea @error('address') auth-input--error @enderror"
                                            placeholder="{{ __('Complete Address') }}">{{ old('address') }}</textarea>
                                        @error('address')
                                            <p id="address-error" class="auth-error" role="alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </fieldset>
                        </div>

                        <fieldset class="auth-section transition duration-200" :class="submitting ? 'opacity-85' : ''">
                            <legend class="auth-section__title">{{ __('Security') }}</legend>
                            <div class="auth-section__header">
                                <p class="auth-section__copy">
                                    {{ __('Create a password you can remember and confirm it once before submitting.') }}
                                </p>
                            </div>

                            <div class="auth-grid">
                                <div class="auth-field">
                                        <label for="password" class="auth-label">{{ __('Password') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <x-heroicon-o-lock-closed class="h-5 w-5" />
                                        </div>
                                        <input id="password" name="password" type="password" required autocomplete="new-password"
                                            aria-describedby="@error('password') password-error @enderror"
                                            aria-invalid="@error('password') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('password') auth-input--error @enderror"
                                            placeholder="{{ __('********') }}">
                                    </div>
                                    @error('password')
                                        <p id="password-error" class="auth-error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="auth-field">
                                        <label for="password_confirmation" class="auth-label">{{ __('Confirm Password') }}</label>
                                    <div class="auth-input-wrap">
                                        <div class="auth-input-icon" aria-hidden="true">
                                            <x-heroicon-o-check class="h-5 w-5" />
                                        </div>
                                        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                                            aria-describedby="@error('password_confirmation') password-confirmation-error @enderror"
                                            aria-invalid="@error('password_confirmation') true @else false @enderror"
                                            class="auth-input auth-input--icon @error('password_confirmation') auth-input--error @enderror"
                                            placeholder="{{ __('********') }}">
                                    </div>
                                    @error('password_confirmation')
                                        <p id="password-confirmation-error" class="auth-error" role="alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </fieldset>

                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="auth-section transition duration-200" :class="submitting ? 'opacity-85' : ''">
                                <label for="terms" class="auth-check">
                                    <x-forms.checkbox name="terms" id="terms" required class="auth-check__box" />
                                    <span class="auth-check__label">
                                        {!! __('I agree to the :terms_of_service and :privacy_policy', [
                                            'terms_of_service' => '<a target="_blank" href="' . route('terms.show') . '" class="auth-link">' . __('Terms of Service') . '</a>',
                                            'privacy_policy' => '<a target="_blank" href="' . route('policy.show') . '" class="auth-link">' . __('Privacy Policy') . '</a>',
                                        ]) !!}
                                    </span>
                                </label>
                                @error('terms')
                                    <p id="terms-error" class="auth-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="auth-actions">
                            <a class="auth-link" href="{{ route('login') }}">
                                {{ __('Already registered?') }}
                            </a>

                            <button type="submit" class="auth-button min-w-[10.5rem]" :disabled="submitting" :aria-busy="submitting.toString()">
                                <span x-show="!submitting" x-cloak>{{ __('Register') }}</span>
                                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                                    <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                                    <span>{{ __('Creating account...') }}</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bootRegisterSelects = (attempt = 0) => {
                if (!window.TomSelect) {
                    if (attempt < 40) {
                        window.setTimeout(() => bootRegisterSelects(attempt + 1), 50);
                    }

                    return;
                }

                let tsProvinsi, tsKabupaten, tsKecamatan, tsKelurahan;
                const wilayahApiBase = @js(url('/api/wilayah'));
                const selectedCodes = {
                    provinsi: @js(old('provinsi_kode')),
                    kabupaten: @js(old('kabupaten_kode')),
                    kecamatan: @js(old('kecamatan_kode')),
                    kelurahan: @js(old('kelurahan_kode')),
                };
                const selectedOptions = @json($selectedWilayahOptions);

                const resolveSelectedOption = (code) => code ? selectedOptions[code] ?? null : null;
                const makeInitialConfig = (code) => {
                    const option = resolveSelectedOption(code);

                    return {
                        options: option ? [option] : [],
                        items: code ? [code] : [],
                    };
                };
                const fetchOptions = (url) =>
                    fetch(url)
                        .then((response) => response.json())
                        .catch(() => []);
                const syncErrorState = (instance) => {
                    if (!instance?.input) {
                        return;
                    }

                    const hasError = instance.input.classList.contains('auth-input--error');
                    instance.wrapper.classList.toggle('auth-input--error', hasError);
                    instance.wrapper.classList.toggle('error', hasError);
                };
                const replaceOptions = async (instance, url, selectedCode = null) => {
                    const options = await fetchOptions(url);

                    instance.clearOptions();
                    instance.addOptions(options);
                    instance.refreshOptions(false);

                    if (selectedCode) {
                        instance.setValue(selectedCode, true);
                    }
                };
                const ensureLoadedOnFocus = (instance, resolveUrl) => () => {
                    const url = resolveUrl();

                    if (!url) {
                        return;
                    }

                    const hasLoadedOptions = Object.keys(instance.options).length > (instance.items.length ? 1 : 0);

                    if (!hasLoadedOptions) {
                        void replaceOptions(instance, url, instance.getValue() || null);
                    }
                };

                const commonConfig = {
                    create: false,
                    preload: true,
                    valueField: 'kode',
                    labelField: 'nama',
                    searchField: 'nama',
                    dropdownParent: 'body',
                    sortField: 'nama',
                    placeholder: '',
                };

                const tsGender = new window.TomSelect('#gender', {
                    create: false,
                    dropdownParent: 'body',
                    placeholder: document.querySelector('#gender')?.dataset.placeholder ?? '',
                    allowEmptyOption: true,
                    sortField: {
                        field: '$order',
                    },
                });
                syncErrorState(tsGender);

                tsProvinsi = new window.TomSelect('#provinsi_kode', {
                    ...commonConfig,
                    ...makeInitialConfig(selectedCodes.provinsi),
                    placeholder: document.querySelector('#provinsi_kode')?.dataset.placeholder ?? '',
                    load: function (query, callback) {
                        fetch(`${wilayahApiBase}/provinces?search=${encodeURIComponent(query)}`)
                            .then(r => r.json())
                            .then(j => callback(j))
                            .catch(() => callback());
                    },
                    onChange: function (value) {
                        if (!tsKabupaten || !tsKecamatan || !tsKelurahan) {
                            return;
                        }

                        tsKabupaten.clear();
                        tsKabupaten.clearOptions();
                        tsKecamatan.clear();
                        tsKecamatan.clearOptions();
                        tsKelurahan.clear();
                        tsKelurahan.clearOptions();

                        if (value) {
                            void replaceOptions(tsKabupaten, `${wilayahApiBase}/regencies/${value}`);
                        }
                    }
                });
                syncErrorState(tsProvinsi);

                tsKabupaten = new window.TomSelect('#kabupaten_kode', {
                    ...commonConfig,
                    preload: !!selectedCodes.provinsi,
                    ...makeInitialConfig(selectedCodes.kabupaten),
                    placeholder: document.querySelector('#kabupaten_kode')?.dataset.placeholder ?? '',
                    load: function (query, callback) {
                        if (!tsProvinsi.getValue()) {
                            callback();
                            return;
                        }

                        fetch(`${wilayahApiBase}/regencies/${tsProvinsi.getValue()}?search=${encodeURIComponent(query)}`)
                            .then(r => r.json())
                            .then(j => callback(j))
                            .catch(() => callback());
                    },
                    onChange: function (value) {
                        if (!tsKecamatan || !tsKelurahan) {
                            return;
                        }

                        tsKecamatan.clear();
                        tsKecamatan.clearOptions();
                        tsKelurahan.clear();
                        tsKelurahan.clearOptions();

                        if (value) {
                            void replaceOptions(tsKecamatan, `${wilayahApiBase}/districts/${value}`);
                        }
                    }
                });
                tsKabupaten.on('focus', ensureLoadedOnFocus(tsKabupaten, () =>
                    tsProvinsi.getValue() ? `${wilayahApiBase}/regencies/${tsProvinsi.getValue()}` : null
                ));
                syncErrorState(tsKabupaten);

                tsKecamatan = new window.TomSelect('#kecamatan_kode', {
                    ...commonConfig,
                    preload: !!selectedCodes.kabupaten,
                    ...makeInitialConfig(selectedCodes.kecamatan),
                    placeholder: document.querySelector('#kecamatan_kode')?.dataset.placeholder ?? '',
                    load: function (query, callback) {
                        if (!tsKabupaten.getValue()) {
                            callback();
                            return;
                        }

                        fetch(`${wilayahApiBase}/districts/${tsKabupaten.getValue()}?search=${encodeURIComponent(query)}`)
                            .then(r => r.json())
                            .then(j => callback(j))
                            .catch(() => callback());
                    },
                    onChange: function (value) {
                        if (!tsKelurahan) {
                            return;
                        }

                        tsKelurahan.clear();
                        tsKelurahan.clearOptions();

                        if (value) {
                            void replaceOptions(tsKelurahan, `${wilayahApiBase}/villages/${value}`);
                        }
                    }
                });
                tsKecamatan.on('focus', ensureLoadedOnFocus(tsKecamatan, () =>
                    tsKabupaten.getValue() ? `${wilayahApiBase}/districts/${tsKabupaten.getValue()}` : null
                ));
                syncErrorState(tsKecamatan);

                tsKelurahan = new window.TomSelect('#kelurahan_kode', {
                    ...commonConfig,
                    preload: !!selectedCodes.kecamatan,
                    ...makeInitialConfig(selectedCodes.kelurahan),
                    placeholder: document.querySelector('#kelurahan_kode')?.dataset.placeholder ?? '',
                    load: function (query, callback) {
                        if (!tsKecamatan.getValue()) {
                            callback();
                            return;
                        }

                        fetch(`${wilayahApiBase}/villages/${tsKecamatan.getValue()}?search=${encodeURIComponent(query)}`)
                            .then(r => r.json())
                            .then(j => callback(j))
                            .catch(() => callback());
                    },
                });
                tsKelurahan.on('focus', ensureLoadedOnFocus(tsKelurahan, () =>
                    tsKecamatan.getValue() ? `${wilayahApiBase}/villages/${tsKecamatan.getValue()}` : null
                ));
                syncErrorState(tsKelurahan);

                if (selectedCodes.provinsi) {
                    void replaceOptions(tsKabupaten, `${wilayahApiBase}/regencies/${selectedCodes.provinsi}`, selectedCodes.kabupaten);
                }

                if (selectedCodes.kabupaten) {
                    void replaceOptions(tsKecamatan, `${wilayahApiBase}/districts/${selectedCodes.kabupaten}`, selectedCodes.kecamatan);
                }

                if (selectedCodes.kecamatan) {
                    void replaceOptions(tsKelurahan, `${wilayahApiBase}/villages/${selectedCodes.kecamatan}`, selectedCodes.kelurahan);
                }
            };

            bootRegisterSelects();
        });
    </script>
</x-guest-layout>
