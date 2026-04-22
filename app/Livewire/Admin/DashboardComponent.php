<?php

namespace App\Livewire\Admin;

use App\Livewire\Traits\AttendanceDetailTrait;
use App\Models\Attendance;
use App\Support\AdminDashboardActionService;
use App\Support\AdminDashboardPresenter;
use App\Support\AdminDashboardQueryService;
use App\Support\ImportExportRunViewService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class DashboardComponent extends Component
{
    use AttendanceDetailTrait, WithPagination;

    protected AdminDashboardQueryService $dashboardQueries;
    protected AdminDashboardPresenter $dashboardPresenter;
    protected AdminDashboardActionService $dashboardActions;
    protected ImportExportRunViewService $importExportRunViews;

    public $showStatModal = false;
    public $selectedStatType = '';
    public $detailList = [];

    // Pending Counts
    public $pendingLeavesCount = 0;
    public $pendingAttendanceCorrectionsCount = 0;
    public $pendingReimbursementsCount = 0;
    public $pendingOvertimesCount = 0;
    public $pendingKasbonCount = 0;

    // Overview Counts
    public $missingFaceDataCount = 0;
    public $activeHolidaysCount = 0;

    // Filter Properties
    public $search = '';
    public $chartFilter = 'week_1';
    public $selectedDate;

    protected string $paginationTheme = 'tailwind';

    public function boot(
        AdminDashboardQueryService $dashboardQueries,
        AdminDashboardPresenter $dashboardPresenter,
        AdminDashboardActionService $dashboardActions,
        ImportExportRunViewService $importExportRunViews,
    ): void {
        Gate::authorize('viewAdminDashboard');
        $this->dashboardQueries = $dashboardQueries;
        $this->dashboardPresenter = $dashboardPresenter;
        $this->dashboardActions = $dashboardActions;
        $this->importExportRunViews = $importExportRunViews;
    }

    public function mount()
    {
        $this->selectedDate = now()->toDateString();
    }

    public function showStatDetail($type)
    {
        $this->selectedStatType = $type;
        $this->showStatModal = true;

        $this->detailList = $this->dashboardQueries
            ->statDetail(auth()->user(), $this->resolvedSelectedDate(), (string) $type);
    }

    public function closeStatModal()
    {
        $this->showStatModal = false;
        $this->detailList = [];
    }



    public function updatedChartFilter()
    {
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    public function updatedSelectedDate()
    {
        $this->selectedDate = $this->resolvedSelectedDate()->toDateString();
        $this->resetPage(pageName: 'employeesPage');
        $this->resetPage(pageName: 'notLoggedInPage');
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    public function updatedSearch()
    {
        $this->resetPage(pageName: 'employeesPage');
    }

    public function resetSelectedDate()
    {
        $this->selectedDate = now()->toDateString();
        $this->resetPage(pageName: 'employeesPage');
        $this->resetPage(pageName: 'notLoggedInPage');
        $this->dispatch('chart-updated', $this->calculateChartData());
    }

    private function calculateChartData()
    {
        return $this->dashboardQueries
            ->chartData(auth()->user(), $this->resolvedSelectedDate(), $this->chartFilter);
    }

    public function render()
    {
        $selectedDate = $this->resolvedSelectedDate();
        $dashboard = $this->dashboardQueries->build(
            auth()->user(),
            $selectedDate,
            (string) $this->search,
        );

        $this->pendingLeavesCount = $dashboard['pendingLeavesCount'];
        $this->pendingAttendanceCorrectionsCount = $dashboard['pendingAttendanceCorrectionsCount'];
        $this->pendingReimbursementsCount = $dashboard['pendingReimbursementsCount'];
        $this->pendingOvertimesCount = $dashboard['pendingOvertimesCount'];
        $this->pendingKasbonCount = $dashboard['pendingKasbonCount'];
        $this->missingFaceDataCount = $dashboard['missingFaceDataCount'];
        $this->activeHolidaysCount = $dashboard['activeHolidaysCount'];

        $viewData = $this->dashboardPresenter->buildViewData(
            $dashboard,
            $selectedDate,
            $this->calculateChartData(),
        );

        return view('livewire.admin.dashboard', [
            ...$viewData,
            'recentReportRuns' => $this->importExportRunViews
                ->recentForResources(['monthly_report_pdf'], auth()->user(), 4),
        ]);
    }

    public function notifyUser($attendanceId)
    {
        $attendance = Attendance::find($attendanceId);

        if ($attendance) {
            $this->dashboardActions->sendCheckoutReminder($attendance);
        }
    }

    private function resolvedSelectedDate(): Carbon
    {
        try {
            return Carbon::parse($this->selectedDate ?: now()->toDateString())->startOfDay();
        } catch (\Throwable $e) {
            return now()->startOfDay();
        }
    }
}
