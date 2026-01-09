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

            <!-- Database Backup & Restore Section -->
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                {{ __('Backup & Restore Database') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ __('Download a full SQL backup or restore from a previous backup file.') }}
                            </p>
                        </header>

                        <!-- Backup -->
                        <div class="mt-6 border-b border-gray-200 dark:border-gray-700 pb-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Backup') }}</h3>
                            <x-button wire:click="downloadBackup">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('Download SQL Backup') }}
                            </x-button>
                        </div>

                        <!-- Restore -->
                        <div class="mt-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Restore') }}</h3>
                            
                            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 mb-4">
                                <p class="text-sm text-red-700 dark:text-red-200">
                                    {{ __('CAUTION: Restoring a database will completely OVERWRITE existing data. Ensure you have a backup before proceeding.') }}
                                </p>
                            </div>

                            <form wire:submit.prevent="restoreDatabase" class="space-y-4">
                                <div>
                                    <input type="file" wire:model="backupFile" accept=".sql" class="block w-full text-sm text-gray-500
                                        file:mr-4 file:py-2 file:px-4
                                        file:rounded-full file:border-0
                                        file:text-sm file:font-semibold
                                        file:bg-primary-50 file:text-primary-700
                                        hover:file:bg-primary-100
                                    "/>
                                    @error('backupFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <x-danger-button type="submit" wire:loading.attr="disabled">
                                    {{ __('Restore Database') }}
                                </x-danger-button>
                                
                                <div wire:loading wire:target="restoreDatabase" class="text-sm text-gray-500 ml-2">
                                    {{ __('Restoring... do not close this window.') }}
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </div>

        </div>
    </div>
</div>
