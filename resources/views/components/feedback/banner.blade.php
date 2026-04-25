@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

<div x-data="{{ json_encode(['show' => true, 'style' => $style, 'message' => $message]) }}"
    :class="{ 'bg-primary-500': style == 'success', 'bg-red-700': style == 'danger', 'bg-yellow-500': style == 'warning', 'bg-gray-500': style != 'success' && style != 'danger' && style != 'warning'}"
            style="display: none;"
            x-show="show && message"
            x-on:banner-message.window="
                style = event.detail.style;
                message = event.detail.message;
                show = true;
            ">
    <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between flex-wrap">
            <div class="w-0 flex-1 flex items-center min-w-0">
                <span class="flex p-2 rounded-lg" :class="{ 'bg-primary-600': style == 'success', 'bg-red-600': style == 'danger', 'bg-yellow-600': style == 'warning' }">
                    <x-heroicon-o-check-circle x-show="style == 'success'" class="h-5 w-5 text-white" />
                    <x-heroicon-o-exclamation-circle x-show="style == 'danger'" class="h-5 w-5 text-white" />
                    <x-heroicon-o-information-circle x-show="style != 'success' && style != 'danger' && style != 'warning'" class="h-5 w-5 text-white" />
                    <x-heroicon-o-exclamation-triangle x-show="style == 'warning'" class="h-5 w-5 text-white" />
                </span>

                <p class="ms-3 font-medium text-sm text-white truncate" x-text="message"></p>
            </div>

            <div class="shrink-0 sm:ms-3">
                <button
                    type="button"
                    class="-me-1 flex p-2 rounded-md focus:outline-none sm:-me-2 transition"
                    :class="{ 'hover:bg-primary-600 focus:bg-primary-600': style == 'success', 'hover:bg-red-600 focus:bg-red-600': style == 'danger', 'hover:bg-yellow-600 focus:bg-yellow-600': style == 'warning'}"
                    aria-label="{{ __('Dismiss') }}"
                    x-on:click="show = false">
                    <x-heroicon-o-x-mark class="h-5 w-5 text-white" />
                </button>
            </div>
        </div>
    </div>
</div>
