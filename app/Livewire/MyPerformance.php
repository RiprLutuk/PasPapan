<?php

namespace App\Livewire;

use App\Models\Appraisal;
use App\Models\AppraisalEvaluation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyPerformance extends Component
{
    public $showSelfAssessmentModal = false;
    public $activeAppraisalId = null;
    
    // Arrays for binding
    public $selfScores = [];
    public $selfComments = [];
    public $evaluations = [];

    protected $rules = [
        'selfScores.*' => 'required|integer|min:1|max:100',
        'selfComments.*' => 'nullable|string',
    ];

    public function openSelfAssessment($appraisalId)
    {
        $appraisal = Appraisal::with('evaluations.kpiTemplate')->findOrFail($appraisalId);
        
        if ($appraisal->user_id !== auth()->id() || $appraisal->status !== 'self_assessment') {
            session()->flash('error', __('Unauthorized action.'));
            return;
        }

        $this->activeAppraisalId = $appraisal->id;
        $this->evaluations = $appraisal->evaluations;

        foreach ($this->evaluations as $evaluation) {
            $this->selfScores[$evaluation->id] = $evaluation->self_score ?? '';
            $this->selfComments[$evaluation->id] = $evaluation->comments ?? '';
        }

        $this->showSelfAssessmentModal = true;
    }

    public function submitSelfAssessment()
    {
        $this->validate();

        $appraisal = Appraisal::findOrFail($this->activeAppraisalId);

        foreach ($this->evaluations as $evaluation) {
            $evaluation->update([
                'self_score' => $this->selfScores[$evaluation->id],
                'comments' => $this->selfComments[$evaluation->id],
            ]);
        }

        $appraisal->update([
            'status' => 'manager_review'
        ]);

        $supervisor = auth()->user()->supervisor;
        if ($supervisor) {
            $supervisor->notify(new \App\Notifications\AppraisalActionNotification(
                $appraisal, 
                auth()->user()->name . ' has submitted their self-assessment and it is ready for your manager review.', 
                route('admin.appraisals')
            ));
        }

        $this->showSelfAssessmentModal = false;
        session()->flash('success', __('Self-assessment submitted successfully. Waiting for manager review.'));
    }

    public function acknowledge($appraisalId)
    {
        $appraisal = Appraisal::findOrFail($appraisalId);
        
        if ($appraisal->user_id !== auth()->id() || $appraisal->status !== 'completed') {
            session()->flash('error', __('Unauthorized action.'));
            return;
        }

        $appraisal->update([
            'employee_acknowledgement' => true
        ]);

        session()->flash('success', __('You have successfully acknowledged your final performance review.'));
    }

    public function render()
    {
        $appraisals = Appraisal::where('user_id', auth()->id())
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        return view('livewire.my-performance', compact('appraisals'));
    }
}
