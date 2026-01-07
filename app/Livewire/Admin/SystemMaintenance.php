<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SystemMaintenance extends Component
{
    public $cleanAttendances = false;
    public $cleanActivityLogs = false;
    public $cleanNotifications = false;
    public $cleanStorage = false;
    public $cleanNonAdminUsers = false;

    public function mount()
    {
        if (!Auth::user()->isSuperadmin) {
            return redirect()->route('admin.dashboard');
        }
    }

    public function cleanDatabase()
    {
        if (!Auth::user()->isSuperadmin) {
            $this->dispatch('error', message: 'Unauthorized action.');
            return;
        }

        if (!$this->cleanAttendances && !$this->cleanActivityLogs && !$this->cleanNonAdminUsers && !$this->cleanNotifications && !$this->cleanStorage) {
            $this->dispatch('warning', message: 'Please select at least one option to clean.');
            return;
        }

        try {
            \Illuminate\Support\Facades\Log::info('Cleaning database...', [
                'attendances' => $this->cleanAttendances,
                'activity_logs' => $this->cleanActivityLogs,
                'notifications' => $this->cleanNotifications,
                'storage' => $this->cleanStorage,
            ]);

            DB::transaction(function () {
                // Disable Foreign Key Checks to allow truncation
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');   
                
                if ($this->cleanAttendances) {
                    Attendance::truncate();
                }

                if ($this->cleanActivityLogs) {
                    DB::table('activity_logs')->truncate();
                }

                if ($this->cleanNotifications) {
                    DB::table('notifications')->truncate();
                }

                if ($this->cleanNonAdminUsers) {
                    // Delete users who are NOT admin or superadmin
                    User::where('group', 'user')->delete();
                }

                // Re-enable Foreign Key Checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            });

            if ($this->cleanStorage) {
                // Delete physical files
                Storage::disk('public')->deleteDirectory('attendance-photos');
                Storage::disk('public')->deleteDirectory('attachments');
                // Recreate empty directories to prevent listing errors if any
                Storage::disk('public')->makeDirectory('attendance-photos');
                Storage::disk('public')->makeDirectory('attachments');
            }

            $this->dispatch('success', message: 'Selected data and files cleaned successfully.');
            
            // Reset checkboxes
            $this->cleanAttendances = false;
            $this->cleanActivityLogs = false;
            $this->cleanNotifications = false;
            $this->cleanStorage = false;
            $this->cleanNonAdminUsers = false;

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Failed to clean database: ' . $e->getMessage());
        }
    }

    public function downloadBackup()
    {
        if (!Auth::user()->isSuperadmin) {
            $this->dispatch('error', message: 'Unauthorized action.');
            return;
        }

        try {
            $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
            $path = storage_path('app/' . $filename);

            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port');
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPassword = config('database.connections.mysql.password');

            // Construct mysqldump command
            // Note: Using --no-tablespaces to avoid privilege errors on some shared hosts
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s --no-tablespaces %s > %s',
                escapeshellarg($dbUser),
                escapeshellarg($dbPassword),
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbName),
                escapeshellarg($path)
            );

            // mask password in log/output if needed, but here we just run it
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return response()->download($path)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            $this->dispatch('error', message: 'Backup failed: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.system-maintenance')
            ->layout('layouts.app');
    }
}
