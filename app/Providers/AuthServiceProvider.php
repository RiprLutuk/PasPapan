<?php

namespace App\Providers;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\CompanyAsset;
use App\Models\Payroll;
use App\Models\Reimbursement;
use App\Models\SystemBackupRun;
use App\Policies\AppraisalPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\CompanyAssetPolicy;
use App\Policies\PayrollPolicy;
use App\Policies\ReimbursementPolicy;
use App\Policies\SystemBackupRunPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Attendance::class => AttendancePolicy::class,
        Appraisal::class => AppraisalPolicy::class,
        Reimbursement::class => ReimbursementPolicy::class,
        CompanyAsset::class => CompanyAssetPolicy::class,
        Payroll::class => PayrollPolicy::class,
        SystemBackupRun::class => SystemBackupRunPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
