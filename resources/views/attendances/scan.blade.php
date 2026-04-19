<x-app-layout>
    @pushOnce('scripts')
        <script>
            (() => {
                const syncScanAttendanceRouteClass = () => {
                    const isScanRoute = window.location.pathname === '/scan' || Boolean(document.querySelector('.scan-attendance-page'));

                    document.documentElement.classList.toggle('scan-attendance-route', isScanRoute);
                    document.body.classList.toggle('scan-attendance-route', isScanRoute);
                };

                syncScanAttendanceRouteClass();

                document.addEventListener('DOMContentLoaded', syncScanAttendanceRouteClass, {
                    once: true
                });
                document.addEventListener('livewire:navigated', syncScanAttendanceRouteClass);
                window.addEventListener('pageshow', syncScanAttendanceRouteClass);
                window.requestAnimationFrame(syncScanAttendanceRouteClass);
                window.setTimeout(syncScanAttendanceRouteClass, 150);
            })();
        </script>
    @endpushOnce

    <div class="user-page-shell scan-attendance-page">
        <div class="user-page-container user-page-container--wide">
            <x-user.page-header
                class="mb-4 native-scan-page-header"
                :back-href="route('home')"
                :title="__('Scan Attendance')"
                title-id="scan-attendance-title"
                plain>
                <x-slot name="icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7V5a1 1 0 011-1h2m12 3V5a1 1 0 00-1-1h-2M4 17v2a1 1 0 001 1h2m12-3v2a1 1 0 01-1 1h-2M9 12h6" />
                    </svg>
                </x-slot>
            </x-user.page-header>

            @livewire('user.scan-component')
        </div>
    </div>
</x-app-layout>
