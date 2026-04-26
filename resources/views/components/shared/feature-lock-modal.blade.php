@props(['id' => 'feature-lock-modal'])

<div x-data="{
        show: false,
        title: '',
        message: '',
        nama: '',
        email: '',
        perusahaan: '',
        whatsapp: '',
        jumlahKaryawan: '',
        catatan: '',
        hwid: '{{ \App\Console\Commands\EnterpriseHwId::generate() }}',
        domain: '{{ request()->getHost() }}',
        submitToWA() {
            const lines = [
                '*Enterprise License Request*',
                '',
                '--- Contact ---',
                'Name: ' + this.nama,
                'Email: ' + this.email,
                'Company: ' + this.perusahaan,
                'WhatsApp: ' + this.whatsapp,
                'Employees: ' + this.jumlahKaryawan,
                '',
                '--- Server Info ---',
                'Feature: ' + this.title,
                'Domain: ' + this.domain,
                'HWID: ' + this.hwid,
            ];
            if (this.catatan) {
                lines.push('');
                lines.push('Notes: ' + this.catatan);
            }
            lines.push('');
            lines.push('_Sent from admin panel_');
            
            const text = lines.join('\n');
            const url = 'https://wa.me/6282324774380?text=' + encodeURIComponent(text);
            const a = document.createElement('a');
            a.href = url;
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            this.show = false;
        }
     }"
     x-on:feature-lock.window="
        show = true;
        title = $event.detail.title || @js(__('Enterprise Feature'));
        message = $event.detail.message || @js(__('This feature is available in the Enterprise Edition. Please upgrade.'));
        nama = '';
        email = '';
        perusahaan = '';
        whatsapp = '';
        jumlahKaryawan = '';
        catatan = '';
     "
     x-on:close-modal.window="show = false"
     x-show="show"
     style="display: none;"
     class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div class="fixed inset-0 transform transition-all" x-on:click="show = false">
        <div class="absolute inset-0 bg-gray-500 opacity-75 dark:bg-gray-900"></div>
    </div>

    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl transform transition-all sm:w-full sm:max-w-md sm:mx-auto"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

        {{-- Header (compact) --}}
        <div class="px-5 py-3 bg-gradient-to-r from-red-600 to-orange-500 flex items-center gap-3">
            <div class="p-1.5 bg-white/20 rounded-full backdrop-blur-sm">
                <x-heroicon-o-lock-closed class="h-5 w-5 text-white" />
            </div>
            <div>
                <h3 class="text-sm font-bold text-white" x-text="title"></h3>
                <p class="text-xs text-white/80" x-text="message"></p>
            </div>
        </div>

        {{-- Form (compact) --}}
        <div class="px-5 py-4 space-y-3">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('Fill in your details below to request an Enterprise upgrade. We will contact you via WhatsApp.') }}
            </p>

            <div class="grid grid-cols-2 gap-3">
                {{-- Nama --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Your Name') }} <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="nama" type="text" required placeholder="{{ __('Full name') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="email" type="email" required placeholder="{{ __('name@company.com') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Perusahaan --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Company Name') }} <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="perusahaan" type="text" required placeholder="{{ __('PT / CV / Organization') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>

                {{-- WhatsApp --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('WhatsApp Number') }} <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="whatsapp" type="tel" required placeholder="{{ __('08xxxxxxxxxx') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Domain (editable) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Domain') }}</label>
                    <x-forms.input x-model="domain" type="text" placeholder="{{ __('example.com') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>

                {{-- Jumlah Karyawan --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Number of Employees') }} <span class="text-red-500">*</span></label>
                    <x-forms.input x-model="jumlahKaryawan" type="number" required placeholder="{{ __('50') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                </div>
            </div>

            {{-- HWID (readonly, full width) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Server HWID') }}</label>
                <x-forms.input x-model="hwid" type="text" readonly class="block w-full rounded-md border-gray-300 bg-gray-100 py-1.5 px-2.5 font-mono text-xs dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>

            {{-- Catatan --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Notes (optional)') }}</label>
                <x-forms.textarea x-model="catatan" rows="2" placeholder="{{ __('Additional requirements or questions...') }}" class="block w-full rounded-md border-gray-300 py-1.5 px-2.5 text-xs dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
            </div>

            {{-- Unlocks Info (compact) --}}
            <div class="p-2.5 bg-gray-50 dark:bg-gray-700/50 rounded-md border border-gray-100 dark:border-gray-700">
                <p class="font-semibold text-gray-800 dark:text-gray-200 text-xs mb-1">🚀 {{ __('Enterprise unlocks:') }}</p>
                <div class="grid grid-cols-2 gap-x-2 gap-y-0.5 text-[11px] text-gray-500 dark:text-gray-400 ml-1">
                    <span>• {{ __('Payroll Generation & Automation') }}</span>
                    <span>• {{ __('KPI & Performance Appraisals') }}</span>
                    <span>• {{ __('Company Asset Management') }}</span>
                    <span>• {{ __('Advanced Reporting (Excel/PDF)') }}</span>
                    <span>• {{ __('Audit Trails & Security Logs') }}</span>
                    <span>• {{ __('Face ID Biometric Enforcement') }}</span>
                </div>
            </div>
        </div>

        {{-- Footer (compact) --}}
        <div class="flex flex-row justify-end gap-2 px-5 py-3 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700">
            <button x-on:click="show = false" type="button" class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                {{ __('Close') }}
            </button>
            <button x-on:click="submitToWA()" type="button"
                    x-bind:disabled="!nama || !email || !perusahaan || !whatsapp || !jumlahKaryawan || !domain"
                    x-bind:class="(!nama || !email || !perusahaan || !whatsapp || !jumlahKaryawan || !domain) ? 'opacity-50 cursor-not-allowed' : 'hover:bg-green-600'"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest active:bg-green-700 transition">
                <x-heroicon-o-chat-bubble-left-ellipsis class="h-3.5 w-3.5" />
                {{ __('Send via WhatsApp') }}
            </button>
        </div>
    </div>
</div>
