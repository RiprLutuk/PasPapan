@props(['id' => null, 'maxWidth' => null])

<x-overlays.modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <div class="sm:flex sm:items-start">
            <div class="mx-auto shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 dark:text-red-400" />
            </div>

            <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $title }}
                </h3>

                <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ $content }}
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end">
        {{ $footer }}
    </div>
</x-overlays.modal>
