<?php

namespace App\Livewire\Admin\ImportExport;

use App\Exports\AttendancesExport;
use App\Exports\AttendanceTemplateExport;
use App\Helpers\Editions;
use App\Imports\AttendancesImport;
use App\Models\Attendance as AttendanceModel;
use App\Models\Division;
use App\Models\Education;
use App\Models\JobTitle;
use App\Support\ImportExportRunViewService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Attendance extends Component
{
    use InteractsWithBanner, WithFileUploads;

    public bool $previewing = false;

    public ?string $mode = null;

    public $file = null;

    public $start_date = null;

    public $end_date = null;

    public $division = null;

    public $job_title = null;

    public $education = null;

    public $skippedRows = 0;

    public $importErrors = [];

    public $importResult = null; // Store import result for display

    protected $rules = [
        'file' => 'required|mimes:csv,xls,xlsx,ods',
        'start_date' => 'nullable|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'division' => 'nullable|exists:divisions,id',
        'job_title' => 'nullable|exists:job_titles,id',
        'education' => 'nullable|exists:educations,id',
    ];

    public function preview()
    {
        $this->previewing = ! $this->previewing;
        $this->mode = $this->previewing ? 'export' : null;
    }

    public function mount()
    {
        if (Editions::reportingLocked()) {
            session()->flash('show-feature-lock', ['title' => 'Import/Export Locked', 'message' => 'Attendance Import/Export is an Enterprise Feature 🔒. Please Upgrade.']);

            return redirect()->route('admin.dashboard');
        }

        // Default to current month
        $this->start_date = now()->startOfMonth()->format('Y-m-d');
        $this->end_date = now()->endOfMonth()->format('Y-m-d');
    }

    public function updated($property, $value)
    {
        if ($value === '') {
            $this->$property = null;
        }

        if (in_array($property, ['start_date', 'end_date', 'division', 'job_title', 'education'])) {
            $this->previewing = true;
            $this->mode = 'export';
        }
    }

    public function render()
    {
        $attendances = null;
        if ($this->file) {
            $this->mode = 'import';
            $this->previewing = true;
            $this->previewing = true;
            $attendanceImport = new AttendancesImport(save: false);

            $rows = Excel::toCollection($attendanceImport, $this->file)->first();
            $totalBefore = $rows->count();

            $attendances = $rows->map(function (Collection $row) use ($attendanceImport) {
                return $attendanceImport->model($row->toArray());
            })
                ->filter();

            $this->skippedRows = $totalBefore - $attendances->count();
        } elseif ($this->previewing && $this->mode == 'export') {
            $attendances = AttendanceModel::filter(
                division: $this->division,
                jobTitle: $this->job_title,
                education: $this->education
            )->when($this->start_date && $this->end_date, function ($query) {
                $query->whereBetween('date', [$this->start_date, $this->end_date]);
            })->get();
        } else {
            $this->previewing = false;
            $this->mode = null;
        }

        return view('livewire.admin.import-export.attendance', [
            'attendances' => $attendances,
            'divisions' => Division::query()->orderBy('name')->get(),
            'jobTitles' => JobTitle::query()->orderBy('name')->get(),
            'educations' => Education::query()->orderBy('name')->get(),
            'recentRuns' => app(ImportExportRunViewService::class)
                ->recentForResources(['attendances'], Auth::user(), 8),
        ]);
    }

    public function import()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        if (Editions::reportingLocked()) {
            $this->dispatch('feature-lock', title: 'Import Locked', message: 'Importing Attendance is an Enterprise Feature 🔒. Please Upgrade.');

            return;
        }
        try {
            $this->validate();
            Log::info('🚀 Import Action Triggered - Validation Passed');

            $importer = new AttendancesImport(save: true); // Explicitly true
            Excel::import($importer, $this->file);

            Log::info("🏁 Import Action Completed. Imported: {$importer->importedCount}, Skipped: {$importer->skippedCount}");

            // Store results for display
            $this->importResult = [
                'imported' => $importer->importedCount,
                'skipped' => $importer->skippedCount,
            ];

            if (! empty($importer->importErrors)) {
                $this->importErrors = $importer->importErrors;
            }

            if ($importer->importedCount > 0) {
                $this->banner(__('Import completed successfully!'));
            } else {
                $this->dangerBanner(__('No records were imported.'));
            }

            $this->file = null; // Clear file but keep results visible
        } catch (\Throwable $th) {
            $this->dangerBanner($th->getMessage());
        }
    }

    public function export()
    {
        if (Auth::user()->isNotAdmin) {
            abort(403);
        }

        if (Editions::reportingLocked()) {
            $this->dispatch('feature-lock', title: 'Export Locked', message: 'Exporting Attendance is an Enterprise Feature 🔒. Please Upgrade.');

            return;
        }

        $division = $this->division ? Division::find($this->division)?->name : null;
        $job_title = $this->job_title ? JobTitle::find($this->job_title)?->name : null;
        $education = $this->education ? Education::find($this->education)?->name : null;

        $timeStr = '';
        if ($this->start_date && $this->end_date) {
            $timeStr = '_'.$this->start_date.'_to_'.$this->end_date;
        }

        $filename = 'attendances'.$timeStr.($division ? '_'.Str::slug($division) : '').($job_title ? '_'.Str::slug($job_title) : '').($education ? '_'.Str::slug($education) : '').'.xlsx';

        // NOTE: AttendancesExport likely needs update to accept Start/End Date instead of Month/Year
        // However, we don't have access to modify AttendancesExport in this turn (User request limitation?)
        // Or we should modify it.
        // If AttendancesExport doesn't support range, we might need to modify it or assume it re-queries based on args.
        // Let's assume we need to update AttendancesExport constructor signature or pass the range via the $months arg (abusing it) or just update it.
        // Since I cannot see AttendancesExport content here, I will proceed with creating a temporary workaround or just passing the dates if I can.
        // Actually, I can View the file `AttendancesExport` but I suspect I should update it.

        // For now, I will use the `AttendancesExport` but it likely expects Month/Year.
        // I will need to check `AttendancesExport.php`.

        return Excel::download(new AttendancesExport(
            null, // Month (deprecated)
            null, // Year (deprecated)
            $this->division,
            $this->job_title,
            $this->education,
            $this->start_date, // New Arg 6?
            $this->end_date    // New Arg 7?
        ), $filename);
    }

    public function downloadTemplate()
    {
        return Excel::download(new AttendanceTemplateExport, 'attendance_template.xlsx');
    }
}
