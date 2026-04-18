<x-app-layout>
    <x-admin.page-shell
        :title="__('Import & Export Management')"
        :description="__('Manage bulk user data and attendance records from a single admin workspace.')"
    >
        <div class="grid gap-6 lg:grid-cols-2">
            <div
                class="group rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:border-primary-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-primary-900/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-300">
                    <x-heroicon-o-users class="h-6 w-6" />
                </div>
                <h3 class="mt-5 text-xl font-semibold text-slate-950 dark:text-white">{{ __('Employee Data Management') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Open the unified workspace for exporting and importing employee records in bulk.') }}
                </p>
                <div class="mt-5">
                    <x-actions.button href="{{ route('admin.import-export.users') }}" variant="soft-primary" size="sm">
                        {{ __('Open Employee Import / Export') }}
                    </x-actions.button>
                </div>
            </div>

            <div
                class="group rounded-3xl border border-slate-200/70 bg-white/90 p-6 shadow-sm transition hover:border-primary-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-primary-900/40">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                    <x-heroicon-o-document-chart-bar class="h-6 w-6" />
                </div>
                <h3 class="mt-5 text-xl font-semibold text-slate-950 dark:text-white">{{ __('Attendance Data Management') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ __('Open the dedicated attendance import and export workflow with previews, filters, and validation feedback.') }}
                </p>
                <div class="mt-5">
                    <x-actions.button href="{{ route('admin.import-export.attendances') }}" variant="soft-success" size="sm">
                        {{ __('Open Attendance Import / Export') }}
                    </x-actions.button>
                </div>
            </div>
        </div>
    </x-admin.page-shell>
</x-app-layout>
