<x-admin.page-shell
    :title="__('System Maintenance')"
    :description="__('Manage cleanup, backup, and restore tasks for the application.')"
>
        <div class="space-y-6">
            
            <!-- Database Cleanup Section -->
            <x-admin.panel class="p-4 sm:p-8">
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

                        <fieldset class="mt-6 space-y-4">
                            <legend class="sr-only">{{ __('Database cleanup options') }}</legend>
                            <label class="flex items-center space-x-3">
                                <x-forms.checkbox wire:model="cleanAttendances" class="h-5 w-5 text-red-600 focus:ring-red-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Attendances') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <x-forms.checkbox wire:model="cleanActivityLogs" class="h-5 w-5 text-red-600 focus:ring-red-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Activity Logs') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <x-forms.checkbox wire:model="cleanNotifications" class="h-5 w-5 text-red-600 focus:ring-red-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean All Notifications') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <x-forms.checkbox wire:model="cleanStorage" class="h-5 w-5 text-red-600 focus:ring-red-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Clean Storage Files (Photos & Attachments)') }}</span>
                            </label>

                            <label class="flex items-center space-x-3">
                                <x-forms.checkbox wire:model="cleanNonAdminUsers" class="h-5 w-5 text-red-600 focus:ring-red-500" />
                                <span class="text-gray-700 dark:text-gray-300">{{ __('Delete Non-Admin Users (Employees)') }}</span>
                            </label>

                            <x-admin.alert tone="warning" class="mt-4">
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
                            </x-admin.alert>

                            <div class="mt-6">
                                <x-actions.danger-button type="button" wire:click="cleanDatabase" wire:confirm="{{ __('Are you sure you want to delete the selected data? This cannot be undone.') }}">
                                    {{ __('Clean Selected Data') }}
                                </x-actions.danger-button>
                            </div>
                        </fieldset>
                    </section>
                </div>
            </x-admin.panel>

            <!-- Database Backup & Restore Section -->
            <x-admin.panel class="p-4 sm:p-8">
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
                            <x-actions.button wire:click="downloadBackup">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('Download SQL Backup') }}
                            </x-actions.button>
                        </div>

                        <!-- Restore -->
                        <div class="mt-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Restore') }}</h3>
                            
                            <x-admin.alert tone="danger" class="mb-4">
                                <p class="text-sm text-red-700 dark:text-red-200">
                                    {{ __('CAUTION: Restoring a database will completely OVERWRITE existing data. Ensure you have a backup before proceeding.') }}
                                </p>
                            </x-admin.alert>

                            <form wire:submit.prevent="restoreDatabase" class="space-y-4">
                                <div>
                                    <label for="backupFile" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('SQL backup file') }}</label>
                                    <x-forms.file-input id="backupFile" wire:model="backupFile" accept=".sql" />
                                    @error('backupFile') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <x-actions.danger-button type="submit" wire:loading.attr="disabled">
                                    {{ __('Restore Database') }}
                                </x-actions.danger-button>
                                
                                <div wire:loading wire:target="restoreDatabase" role="status" aria-live="polite" class="text-sm text-gray-500 ml-2">
                                    {{ __('Restoring... do not close this window.') }}
                                </div>
                            </form>
                        </div>
                    </section>
                </div>
            </x-admin.panel>

        </div>
    </x-admin.page-shell>
