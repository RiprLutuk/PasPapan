<?php

namespace App\Livewire\Admin;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AppraisalManager extends Component
{
    use WithPagination;

    public $month;
    public $year;
    
    public $search = '';

    // Modal state
    public $showModal = false;
    public $evaluatingUser = null;
    public $attendanceScore = 0;
    public $subjectiveScore = 0;
    public $notes = '';

    public function mount()
    {
        \App\Services\Enterprise\LicenseGuard::check();
        
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function updating($property)
    {
        if (in_array($property, ['search', 'month', 'year'])) {
            $this->resetPage();
        }
    }

    public function evaluate($userId)
    {
        $this->evaluatingUser = User::findOrFail($userId);
        $this->calculateAttendanceScore();
        
        // Load existing appraisal if any
        $appraisal = Appraisal::where('user_id', $this->evaluatingUser->id)
            ->where('period_month', $this->month)
            ->where('period_year', $this->year)
            ->first();

        if ($appraisal) {
            $this->subjectiveScore = $appraisal->subjective_score;
            $this->notes = $appraisal->notes;
        } else {
            $this->subjectiveScore = 0;
            $this->notes = '';
        }
        
        $this->showModal = true;
    }

    public function calculateAttendanceScore()
    {
        // Simple algorithm: 100 base score. 
        // -5 per late
        // -10 per absent (alpha)
        // -2 per excused
        
        $lates = Attendance::where('user_id', $this->evaluatingUser->id)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->where('status', 'late')
            ->count();
            
        $absents = Attendance::where('user_id', $this->evaluatingUser->id)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->whereIn('status', ['absent', 'alpha'])
            ->count();

        $excused = Attendance::where('user_id', $this->evaluatingUser->id)
            ->whereMonth('date', $this->month)
            ->whereYear('date', $this->year)
            ->whereIn('status', ['excused', 'sick'])
            ->count();

        $score = 100 - ($lates * 5) - ($absents * 10) - ($excused * 2);
        $this->attendanceScore = max(0, min(100, $score));
    }

    public function save()
    {
        $this->validate([
            'subjectiveScore' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string'
        ]);

        $finalScore = ($this->attendanceScore * 0.4) + ($this->subjectiveScore * 0.6);

        Appraisal::updateOrCreate([
            'user_id' => $this->evaluatingUser->id,
            'period_month' => $this->month,
            'period_year' => $this->year,
        ], [
            'evaluator_id' => auth()->id(),
            'attendance_score' => $this->attendanceScore,
            'subjective_score' => $this->subjectiveScore,
            'final_score' => round($finalScore, 2),
            'notes' => $this->notes,
        ]);

        $this->showModal = false;
        $this->dispatch('notify', 'Appraisal saved successfully');
    }

    public function render()
    {
        $admin = auth()->user();
        $query = User::where('group', 'user')->managedBy($admin);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('nip', 'like', '%' . $this->search . '%');
        }

        $users = $query->orderBy('name')->paginate(10);

        // Map appraisals to users
        $userIds = $users->pluck('id')->toArray();
        $appraisals = Appraisal::whereIn('user_id', $userIds)
            ->where('period_month', $this->month)
            ->where('period_year', $this->year)
            ->get()
            ->keyBy('user_id');

        return view('livewire.admin.appraisal-manager', [
            'users' => $users,
            'appraisals' => $appraisals
        ]);
    }
}
