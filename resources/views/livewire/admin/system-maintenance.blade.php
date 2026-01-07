<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('System Maintenance') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Database Cleanup Section -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Clean Database') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Select data to permanently delete from the database. This action cannot be undone.') }}
                            </p>
                        </header>

                        <div class="mt-6 space-y-4">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="cleanAttendances" class="form-checkbox h-5 w-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Attendances') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="cleanActivityLogs" class="form-checkbox h-5 w-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Activity Logs') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="cleanNotifications" class="form-checkbox h-5 w-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Notifications') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="cleanStorage" class="form-checkbox h-5 w-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean Storage Files (Photos & Attachments)') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <input type="checkbox" wire:model="cleanNonAdminUsers" class="form-checkbox h-5 w-5 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Delete Non-Admin Users (Employees)') }}</span>
                            </label>

                            <div class="bg-yellow-50 dark:bg-yellow-900/50 border-l-4 border-yellow-400 p-4 mt-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                                            {{ __('Warning: Deleted data cannot be recovered. Admin and Superadmin accounts will NOT be deleted.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <x-danger-button wire:click="cleanDatabase" wire:confirm="Are you sure you want to delete the selected data? This cannot be undone.">
                                    {{ __('Clean Selected Data') }}
                                </x-danger-button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <!-- Database Backup Section -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Backup Database') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Download a full SQL backup of the database.') }}
                            </p>
                        </header>

                        <div class="mt-6">
                            <x-button wire:click="downloadBackup">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('Download SQL Backup') }}
                            </x-button>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </div>
</div>
