<x-app-layout>
    <x-admin.page-shell
        :title="__('Import & Export Management')"
        :description="__('Manage bulk user data and attendance records from a single admin workspace.')"
    >
            
            <!-- SECTION 1: USER MANAGEMENT (Livewire Component) -->
            @livewire('admin.import-export.user')

            <!-- SECTION 2: ATTENDANCE MANAGEMENT -->
            <div x-data="{ activeTab: 'export' }" class="bg-white dark:bg-gray-800 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Header & Tabs -->
                <div class="border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/20 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ __('Attendance Data Management') }}
                    </h3>
                    
                    <div class="flex bg-gray-200 dark:bg-gray-700 rounded-lg p-1">
                        <button @click="activeTab = 'export'" 
                            :class="activeTab === 'export' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                            Export
                        </button>
                        <button @click="activeTab = 'import'" 
                            :class="activeTab === 'import' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                            class="px-4 py-1.5 text-sm font-medium rounded-md transition-all duration-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                            Import
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <!-- ATTENDANCE EXPORT -->
                    <div x-show="activeTab === 'export'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                         <div class="max-w-xl mx-auto">
                            <div class="text-center mb-6">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Export Attendance') }}</h4>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ __('Filter and download attendance records.') }}</p>
                            </div>
                            
                            <form action="{{ route('admin.attendances.export') }}" method="get" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <x-forms.label for="year" value="{{ __('Year') }}" class="mb-1 block" />
                                        <x-forms.input type="number" min="1970" max="2099" value="{{ date('Y') }}" name="year" id="year" class="w-full" />
                                    </div>
                                    <div>
                                        <x-forms.label for="month" value="{{ __('Month') }}" class="mb-1 block" />
                                        <x-forms.input type="month" name="month" id="month" class="w-full" />
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                     <div>
                                        <x-forms.label for="division" value="{{ __('Division') }}" class="mb-1 block" />
                                        <x-forms.select id="division" name="division" class="w-full">
                                            <option value="">{{ __('All Divisions') }}</option>
                                            @foreach (App\Models\Division::all() as $division)
                                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                                            @endforeach
                                        </x-forms.select>
                                    </div>
                                    <div>
                                        <x-forms.label for="jobTitle" value="{{ __('Job Title') }}" class="mb-1 block" />
                                        <x-forms.select id="jobTitle" name="job_title" class="w-full">
                                            <option value="">{{ __('All Job Titles') }}</option>
                                            @foreach (App\Models\JobTitle::all() as $jobTitle)
                                                <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                                            @endforeach
                                        </x-forms.select>
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <x-actions.button class="w-full justify-center py-3">
                                        {{ __('Export Attendance Records') }}
                                    </x-actions.button>
                                </div>
                            </form>
                         </div>
                    </div>

                    <!-- ATTENDANCE IMPORT -->
                    <div x-show="activeTab === 'import'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                         <div class="max-w-xl mx-auto">
                            <div class="text-center mb-6">
                                <h4 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Import Attendance') }}</h4>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ __('Bulk upload attendance from machine logs.') }}</p>
                            </div>
                            
                            <form x-data="{ file: null, dragging: false }" 
                                  action="{{ route('admin.attendances.import') }}" method="post" enctype="multipart/form-data"
                                  @drop.prevent="dragging = false; file = $event.dataTransfer.files[0]; $refs.file.files = $event.dataTransfer.files"
                                  @dragover.prevent="dragging = true"
                                  @dragleave.prevent="dragging = false"
                                  class="space-y-4">
                                @csrf
                                
                                <!-- Dropzone -->
                                <div class="relative group">
                                     <div :class="{'border-primary-500 bg-primary-50 dark:bg-primary-900/10': dragging, 'border-gray-300 dark:border-gray-600': !dragging}"
                                          class="border-2 border-dashed rounded-xl p-8 text-center transition-all duration-200 cursor-pointer hover:border-primary-400 dark:hover:border-primary-500"
                                          @click="$refs.file.click()">
                                          
                                        <input type="file" class="hidden" name="file" x-ref="file" 
                                               x-on:change="file = $refs.file.files[0]">
                                        
                                        <template x-if="!file">
                                            <div>
                                                <svg class="mx-auto h-12 w-12 text-gray-400 group-hover:text-primary-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Click or drag file here') }}</p>
                                            </div>
                                        </template>
                                        
                                        <template x-if="file">
                                            <div>
                                                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white" x-text="file.name"></p>
                                            </div>
                                        </template>
                                     </div>
                                </div>

                                <div x-show="file">
                                    <x-actions.danger-button class="w-full justify-center py-3">
                                        {{ __('Import Attendance File') }}
                                    </x-actions.danger-button>
                                </div>
                            </form>
                         </div>
                    </div>
                </div>
            </div>

    </x-admin.page-shell>
</x-app-layout>
