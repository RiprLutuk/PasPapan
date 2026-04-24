<?php

namespace App\Livewire\Admin;

use App\Models\Appraisal;
use App\Models\User;
use App\Services\AppraisalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AppraisalManager extends Component
{
    use WithPagination;

    public $month;

    public $year;

    public $search = '';

    public $showModal = false;

    public ?User $evaluatingUser = null;

    public $activeAppraisalId = null;

    public $attendanceScore = 0;

    public $evaluations = [];

    public $managerScores = [];

    public $evalComments = [];

    public $evidenceDescriptions = [];

    public $appraisalStatus = 'draft';

    public $meetingDate = null;

    public $meetingLink = null;

    public $generalNotes = '';

    public $employeeNotes = '';

    public $developmentRecommendations = '';

    public function mount()
    {
        Gate::authorize('viewAdminAny', Appraisal::class);
        \App\Services\Enterprise\LicenseGuard::check();

        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function updating($property)
    {
        if (in_array($property, ['search', 'month', 'year'], true)) {
            $this->resetPage();
        }
    }

    public function initOrEvaluate($userId)
    {
        Gate::authorize('manage', Appraisal::class);

        $periodOpen = (bool) \App\Models\Setting::getValue('appraisal.period_open', false);
        $deadline = \App\Models\Setting::getValue('appraisal.period_deadline', '');

        if (! $periodOpen || ($deadline && now()->gt($deadline))) {
            session()->flash('error', __('The appraisal window is currently closed. Please open it from KPI Settings.'));

            return;
        }

        $this->evaluatingUser = User::findOrFail($userId);

        $service = app(AppraisalService::class);
        $appraisal = $service->initAppraisal($this->evaluatingUser, $this->month, $this->year);
        $appraisal->load('evaluations.kpiTemplate.kpiGroup');

        $this->activeAppraisalId = $appraisal->id;
        $this->appraisalStatus = $appraisal->status;
        $this->attendanceScore = $appraisal->attendance_score;
        $this->meetingDate = $appraisal->meeting_date ? Carbon::parse($appraisal->meeting_date)->format('Y-m-d') : null;
        $this->meetingLink = $appraisal->meeting_link;
        $this->generalNotes = $appraisal->notes;
        $this->evaluations = $appraisal->evaluations;

        foreach ($this->evaluations as $evaluation) {
            $this->managerScores[$evaluation->id] = $evaluation->manager_score ?? '';
            $this->evalComments[$evaluation->id] = $evaluation->comments ?? '';
            $this->evidenceDescriptions[$evaluation->id] = $evaluation->evidence_description ?? '';
        }

        $this->employeeNotes = $appraisal->employee_notes;
        $this->developmentRecommendations = $appraisal->development_recommendation;
        $this->showModal = true;
    }

    public function save()
    {
        Gate::authorize('manage', Appraisal::class);

        $this->validate([
            'managerScores.*' => 'nullable|numeric|min:1|max:5',
            'evalComments.*' => 'nullable|string',
            'evidenceDescriptions.*' => 'nullable|string',
            'generalNotes' => 'nullable|string',
            'employeeNotes' => 'nullable|string',
            'developmentRecommendations' => 'nullable|string',
            'appraisalStatus' => 'required|in:self_assessment,manager_review,1on1_scheduled,completed',
            'meetingDate' => 'nullable|date',
            'meetingLink' => 'nullable|url',
        ]);

        $service = app(AppraisalService::class);
        $appraisal = Appraisal::findOrFail($this->activeAppraisalId);
        $oldStatus = $appraisal->status;

        $service->finalizeAppraisal(
            $appraisal,
            $this->managerScores,
            $this->evalComments,
            $this->evidenceDescriptions,
            $this->generalNotes,
            $this->employeeNotes,
            $this->developmentRecommendations,
            $this->appraisalStatus,
            $this->meetingDate,
            $this->meetingLink
        );

        if ($this->appraisalStatus === 'completed' && $oldStatus !== 'completed') {
            $appraisal->update(['calibration_status' => 'pending']);
        }

        if ($oldStatus !== $this->appraisalStatus) {
            $message = '';

            if ($this->appraisalStatus === 'self_assessment') {
                $message = 'Your manager has initialized an appraisal. Please login to submit your self-assessment score.';
            } elseif ($this->appraisalStatus === '1on1_scheduled') {
                $message = 'Your manager has scheduled a 1-on-1 meeting to discuss your performance.';
            } elseif ($this->appraisalStatus === 'completed') {
                $message = 'Your final performance score has been released. Please login to acknowledge the results.';
            }

            if ($message !== '') {
                $this->evaluatingUser?->notify(new \App\Notifications\AppraisalActionNotification(
                    $appraisal,
                    $message,
                    route('my-performance')
                ));
            }
        }

        $this->showModal = false;
        $this->evaluatingUser = null;

        session()->flash('success', __('Appraisal saved and status updated successfully.'));
    }

    public function calibrate($appraisalId, $decision)
    {
        $appraisal = Appraisal::findOrFail($appraisalId);

        Gate::authorize('calibrate', $appraisal);

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            session()->flash('error', __('Unsupported calibration decision.'));

            return;
        }

        if ($appraisal->status !== 'completed' || $appraisal->calibration_status !== 'pending') {
            session()->flash('error', __('Only completed appraisals with pending calibration can be reviewed.'));

            return;
        }

        $appraisal->update([
            'calibrator_id' => auth()->id(),
            'calibration_status' => $decision,
        ]);

        if ($appraisal->evaluator) {
            $statusText = $decision === 'approved' ? 'approved' : 'rejected and requires revision';

            $appraisal->evaluator->notify(new \App\Notifications\AppraisalActionNotification(
                $appraisal,
                "The appraisal for {$appraisal->user->name} has been {$statusText} by HR.",
                route('admin.appraisals')
            ));
        }

        session()->flash('success', __('Calibration decision recorded: :status', ['status' => ucfirst($decision)]));
    }

    public function render()
    {
        $admin = auth()->user();
        $query = User::where('group', 'user')->managedBy($admin);

        if ($this->search) {
            $query->where(function ($builder) {
                $builder->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('nip', 'like', '%'.$this->search.'%');
            });
        }

        $users = $query->orderBy('name')->paginate(10);

        $service = app(AppraisalService::class);
        $appraisals = $service->getAppraisalsForUsers(
            $users->pluck('id')->toArray(),
            $this->month,
            $this->year
        );

        $months = collect(range(1, 12))
            ->map(fn ($value) => ['id' => (string) $value, 'name' => __(date('F', mktime(0, 0, 0, $value, 10)))])
            ->values()
            ->all();

        $years = collect(range(date('Y') - 2, date('Y') + 1))
            ->map(fn ($value) => ['id' => (string) $value, 'name' => (string) $value])
            ->values()
            ->all();

        $allScores = Appraisal::query()
            ->where('period_month', $this->month)
            ->where('period_year', $this->year)
            ->whereNotNull('final_score')
            ->pluck('final_score')
            ->toArray();

        $bellCurve = [
            'A' => count(array_filter($allScores, fn ($score) => $score >= 90)),
            'B' => count(array_filter($allScores, fn ($score) => $score >= 80 && $score < 90)),
            'C' => count(array_filter($allScores, fn ($score) => $score >= 70 && $score < 80)),
            'D' => count(array_filter($allScores, fn ($score) => $score >= 60 && $score < 70)),
            'E' => count(array_filter($allScores, fn ($score) => $score < 60)),
        ];

        $periodOpen = (bool) \App\Models\Setting::getValue('appraisal.period_open', false);
        $periodLabel = \App\Models\Setting::getValue('appraisal.period_label', '');

        return view('livewire.admin.appraisal-manager', compact(
            'users',
            'appraisals',
            'months',
            'years',
            'bellCurve',
            'periodOpen',
            'periodLabel',
        ));
    }
}
