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
        title = $event.detail.title || 'Enterprise Feature 🔒';
        message = $event.detail.message || 'This feature is available in the Enterprise Edition.';
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
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
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
                    <input x-model="nama" type="text" required placeholder="{{ __('Full name') }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input x-model="email" type="email" required placeholder="name@company.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Perusahaan --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Company Name') }} <span class="text-red-500">*</span></label>
                    <input x-model="perusahaan" type="text" required placeholder="{{ __('PT / CV / Organization') }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>

                {{-- WhatsApp --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('WhatsApp Number') }} <span class="text-red-500">*</span></label>
                    <input x-model="whatsapp" type="tel" required placeholder="08xxxxxxxxxx" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Domain (editable) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Domain') }}</label>
                    <input x-model="domain" type="text" placeholder="example.com" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>

                {{-- Jumlah Karyawan --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Number of Employees') }} <span class="text-red-500">*</span></label>
                    <input x-model="jumlahKaryawan" type="number" required placeholder="50" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5">
                </div>
            </div>

            {{-- HWID (readonly, full width) --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Server HWID') }}</label>
                <input x-model="hwid" type="text" readonly class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5 bg-gray-100 dark:bg-gray-800 cursor-not-allowed font-mono">
            </div>

            {{-- Catatan --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-0.5">{{ __('Notes (optional)') }}</label>
                <textarea x-model="catatan" rows="2" placeholder="{{ __('Additional requirements or questions...') }}" class="block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-xs dark:bg-gray-700 dark:text-white py-1.5 px-2.5"></textarea>
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
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                {{ __('Send via WhatsApp') }}
            </button>
        </div>
    </div>
</div>
