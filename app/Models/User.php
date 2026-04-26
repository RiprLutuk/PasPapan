<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Support\RbacRegistry;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasUlids;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nip',
        'name',
        'email',
        'password',
        'group',
        'phone',
        'gender',
        'birth_date',
        'birth_place',
        'address',
        'city',
        'provinsi_kode',
        'kabupaten_kode',
        'kecamatan_kode',
        'kelurahan_kode',
        'education_id',
        'division_id',
        'job_title_id',
        'manager_id',
        'profile_photo_path',
        'language',
        'basic_salary',
        'hourly_rate',
        'payslip_password',
        'payslip_password_set_at',
        'employment_status',
        'account_deletion_requested_at',
        'account_deletion_reason',
        'account_deletion_reviewed_at',
        'account_deletion_reviewed_by',
        'account_deletion_review_notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'payslip_password',
        'email_verification_code_hash',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_code_expires_at' => 'datetime',
            'birth_date' => 'datetime:Y-m-d',
            'account_deletion_requested_at' => 'datetime',
            'account_deletion_reviewed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public static $groups = ['user', 'admin', 'superadmin'];

    public const EMPLOYMENT_STATUS_ACTIVE = 'active';

    public const EMPLOYMENT_STATUS_INACTIVE = 'inactive';

    public const EMPLOYMENT_STATUS_RESIGNED = 'resigned';

    public const EMPLOYMENT_STATUS_DELETION_REQUESTED = 'deletion_requested';

    public const EMPLOYMENT_STATUS_DELETED = 'deleted';

    public function sendEmailVerificationNotification(): void
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        $code = (string) random_int(100000, 999999);

        $this->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ])->save();

        $this->notify(new QueuedVerifyEmail($code));
    }

    public function hasValidEmailVerificationCode(string $code): bool
    {
        $code = preg_replace('/\D+/', '', $code) ?? '';

        return strlen($code) === 6
            && filled($this->email_verification_code_hash)
            && $this->email_verification_code_expires_at?->isFuture()
            && Hash::check($code, $this->email_verification_code_hash);
    }

    public function clearEmailVerificationCode(): void
    {
        $this->forceFill([
            'email_verification_code_hash' => null,
            'email_verification_code_expires_at' => null,
        ])->save();
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    final public function getIsUserAttribute(): bool
    {
        return $this->group === 'user';
    }

    final public function getIsAdminAttribute(): bool
    {
        return $this->group === 'admin' || $this->isSuperadmin;
    }

    final public function getIsSuperadminAttribute(): bool
    {
        return $this->group === 'superadmin';
    }

    final public function getIsNotAdminAttribute(): bool
    {
        return ! $this->isAdmin;
    }

    final public function getIsDemoAttribute(): bool
    {
        return in_array($this->email, [
            'admin123@paspapan.com',
            'user123@paspapan.com',
        ]);
    }

    public static function employmentStatuses(): array
    {
        return [
            self::EMPLOYMENT_STATUS_ACTIVE => 'Active',
            self::EMPLOYMENT_STATUS_INACTIVE => 'Inactive',
            self::EMPLOYMENT_STATUS_RESIGNED => 'Resigned',
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED => 'Deletion Requested',
            self::EMPLOYMENT_STATUS_DELETED => 'Deleted',
        ];
    }

    public static function manuallyManagedEmploymentStatuses(): array
    {
        return [
            self::EMPLOYMENT_STATUS_ACTIVE,
            self::EMPLOYMENT_STATUS_INACTIVE,
            self::EMPLOYMENT_STATUS_RESIGNED,
        ];
    }

    public function reviewedAccountDeletionBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'account_deletion_reviewed_by');
    }

    public function employmentStatusLabel(): string
    {
        return __(self::employmentStatuses()[$this->employment_status] ?? Str::headline((string) $this->employment_status));
    }

    public function employmentStatusTone(): string
    {
        return match ($this->employment_status) {
            self::EMPLOYMENT_STATUS_ACTIVE => 'success',
            self::EMPLOYMENT_STATUS_INACTIVE => 'warning',
            self::EMPLOYMENT_STATUS_RESIGNED => 'accent',
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED => 'danger',
            self::EMPLOYMENT_STATUS_DELETED => 'neutral',
            default => 'neutral',
        };
    }

    public function canAuthenticate(): bool
    {
        return in_array($this->employment_status ?: self::EMPLOYMENT_STATUS_ACTIVE, [
            self::EMPLOYMENT_STATUS_ACTIVE,
            self::EMPLOYMENT_STATUS_DELETION_REQUESTED,
        ], true);
    }

    public function hasPendingAccountDeletionRequest(): bool
    {
        return $this->employment_status === self::EMPLOYMENT_STATUS_DELETION_REQUESTED
            && $this->account_deletion_requested_at !== null;
    }

    public function requestAccountDeletion(?string $reason = null): void
    {
        $this->forceFill([
            'employment_status' => self::EMPLOYMENT_STATUS_DELETION_REQUESTED,
            'account_deletion_requested_at' => now(),
            'account_deletion_reason' => filled($reason) ? trim((string) $reason) : null,
            'account_deletion_reviewed_at' => null,
            'account_deletion_reviewed_by' => null,
            'account_deletion_review_notes' => null,
        ])->save();
    }

    public function approveAccountDeletion(User $reviewer, ?string $notes = null): void
    {
        $this->forceFill([
            'employment_status' => self::EMPLOYMENT_STATUS_DELETED,
            'account_deletion_reviewed_at' => now(),
            'account_deletion_reviewed_by' => $reviewer->id,
            'account_deletion_review_notes' => filled($notes) ? trim((string) $notes) : null,
            'remember_token' => Str::random(60),
        ])->save();

        $this->tokens()->delete();
        DB::table('sessions')->where('user_id', $this->id)->delete();
    }

    public function rejectAccountDeletion(User $reviewer, ?string $notes = null): void
    {
        $this->forceFill([
            'employment_status' => self::EMPLOYMENT_STATUS_ACTIVE,
            'account_deletion_requested_at' => null,
            'account_deletion_reason' => null,
            'account_deletion_reviewed_at' => now(),
            'account_deletion_reviewed_by' => $reviewer->id,
            'account_deletion_review_notes' => filled($notes) ? trim((string) $notes) : null,
        ])->save();
    }

    public function canTransitionEmploymentStatusTo(string $status): bool
    {
        return in_array($status, self::manuallyManagedEmploymentStatuses(), true);
    }

    public function education()
    {
        return $this->belongsTo(Education::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function directManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function shiftSwapRequests()
    {
        return $this->hasMany(ShiftSwapRequest::class);
    }

    public function employeeDocumentRequests()
    {
        return $this->hasMany(EmployeeDocumentRequest::class);
    }

    public function hasAssignedRoles(): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->isNotEmpty();
        }

        return $this->roles()->exists();
    }

    public function rolePermissionKeys(): array
    {
        $this->loadMissing('roles');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions ?? [])
            ->filter(fn ($permission) => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function hasRole(string $slug): bool
    {
        $this->loadMissing('roles');

        return $this->roles->contains(fn (Role $role) => $role->slug === $slug);
    }

    public function hasPermission(string $permission): bool
    {
        $permissions = $this->rolePermissionKeys();

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        $segments = explode('.', $permission);

        while (count($segments) > 1) {
            array_pop($segments);

            if (in_array(implode('.', $segments).'.*', $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function allowsAdminPermission(string|array $permissions, bool $legacyFallback = false): bool
    {
        if (! $this->isAdmin) {
            return false;
        }

        $permissions = (array) $permissions;

        return $this->hasAssignedRoles() && $this->hasAnyPermission($permissions);
    }

    public function canAccessAdminPanel(): bool
    {
        if (! $this->isAdmin) {
            return false;
        }

        return $this->hasAssignedRoles()
            && $this->hasAnyPermission(RbacRegistry::adminAccessPermissions());
    }

    public function canManageRbac(): bool
    {
        return $this->allowsAdminPermission('admin.rbac.manage');
    }

    public function canAssignRoles(): bool
    {
        return $this->allowsAdminPermission('admin.rbac.assign');
    }

    public function canViewSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_view');
    }

    public function canManageSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_manage');
    }

    public function canDeleteSuperadminAccounts(): bool
    {
        return $this->allowsAdminPermission('admin.admin_accounts.superadmin_delete');
    }

    public function hasGlobalAdminScope(): bool
    {
        return $this->allowsAdminPermission('admin.scope.global');
    }

    public function preferredAdminRouteName(): ?string
    {
        if (! $this->canAccessAdminPanel()) {
            return null;
        }

        $routeAbilities = [
            'admin.dashboard' => ['viewAdminDashboard'],
            'admin.notifications' => ['manageAdminNotifications'],
            'admin.attendances' => ['viewAdminAny', Attendance::class],
            'admin.attendance-corrections' => ['viewAdminAny', AttendanceCorrection::class],
            'admin.document-requests' => ['viewAdminAny', EmployeeDocumentRequest::class],
            'admin.leaves' => ['manageLeaveApprovals'],
            'admin.shift-swaps' => ['manageShiftSwapApprovals'],
            'admin.overtime' => ['manageOvertime'],
            'admin.schedules' => ['manageSchedules'],
            'admin.analytics' => ['viewAnalyticsDashboard'],
            'admin.holidays' => ['manageHolidays'],
            'admin.announcements' => ['manageAnnouncements'],
            'admin.payrolls' => ['viewAdminAny', Payroll::class],
            'admin.reimbursements' => ['viewAdminAny', Reimbursement::class],
            'admin.manage-kasbon' => ['manageCashAdvances'],
            'admin.payroll.settings' => ['managePayrollSettings'],
            'admin.employees' => ['viewEmployees'],
            'admin.appraisals' => ['viewAdminAny', Appraisal::class],
            'admin.assets' => ['viewAdminAny', CompanyAsset::class],
            'admin.barcodes' => ['manageBarcodes'],
            'admin.masters.division' => ['manageDivisions'],
            'admin.masters.job-title' => ['manageJobTitles'],
            'admin.masters.education' => ['manageEducations'],
            'admin.masters.shift' => ['manageShifts'],
            'admin.masters.leave-types' => ['manageLeaveTypes'],
            'admin.masters.admin' => ['viewAdminAccounts'],
            'admin.settings' => ['viewAdminSettings'],
            'admin.settings.kpi' => ['manageKpiSettings'],
            'admin.import-export.users' => ['viewUserImportExport'],
            'admin.import-export.attendances' => ['viewAttendanceImportExport'],
            'admin.activity-logs' => ['viewActivityLogs'],
            'admin.system-maintenance' => ['viewAny', SystemBackupRun::class],
            'admin.roles.permissions' => ['manageRbac'],
        ];

        foreach ($routeAbilities as $routeName => $abilityDefinition) {
            $ability = $abilityDefinition[0] ?? null;
            $arguments = array_slice($abilityDefinition, 1);

            if ($ability === null) {
                continue;
            }

            if ($this->can($ability, $arguments)) {
                return $routeName;
            }
        }

        return null;
    }

    public function preferredHomeRouteName(): string
    {
        return $this->preferredAdminRouteName() ?? 'home';
    }

    public function preferredHomeUrl(): string
    {
        return route($this->preferredHomeRouteName());
    }

    public function getSupervisorAttribute()
    {
        if ($this->manager_id && $this->manager_id !== $this->id) {
            return $this->relationLoaded('directManager')
                ? $this->directManager
                : $this->directManager()->first();
        }

        if (! $this->division_id || ! $this->job_title_id || ! $this->jobTitle || ! $this->jobTitle->jobLevel) {
            return null;
        }

        $myRank = $this->jobTitle->jobLevel->rank;

        // Find someone in the same division with a higher rank (smaller rank number)
        // Check 1: User with a title that has a better rank
        return User::where('division_id', $this->division_id)
            ->where('id', '!=', $this->id)
            ->whereHas('jobTitle', function ($q) use ($myRank) {
                // Ensure JobTitle has a JobLevel with better rank
                $q->whereHas('jobLevel', function ($sq) use ($myRank) {
                    $sq->where('rank', '<', $myRank);
                });
            })
            ->with(['jobTitle.jobLevel'])
            ->get()
            // Sort by rank descending (e.g. 3 is closer to 4 than 1 is)
            // smaller rank = higher pos. We want the "closest" superior.
            // If I am 4, I want 3, then 2, then 1.
            // So sort by rank desc (3, 2, 1). First one is 3.
            ->sortByDesc(fn ($u) => $u->jobTitle->jobLevel->rank)
            ->first();
    }

    public function getSubordinatesAttribute()
    {
        $explicitReports = $this->relationLoaded('directReports')
            ? $this->directReports
            : $this->directReports()->get();

        if (! $this->division_id || ! $this->jobTitle || ! $this->jobTitle->jobLevel) {
            return $explicitReports->values();
        }

        $myRank = $this->jobTitle->jobLevel->rank;

        $inferredReports = User::where('division_id', $this->division_id)
            ->whereNull('manager_id')
            ->whereHas('jobTitle.jobLevel', function ($q) use ($myRank) {
                $q->where('rank', '>', $myRank);
            })
            ->get();

        return $explicitReports
            ->merge($inferredReports)
            ->where('id', '!=', $this->id)
            ->unique('id')
            ->values();
    }

    /**
     * Check if the user has a valid (non-expired) payslip password.
     * Expired if set > 3 months ago.
     */
    public function hasValidPayslipPassword(): bool
    {
        if (! $this->payslip_password || ! $this->payslip_password_set_at) {
            return false;
        }

        return \Illuminate\Support\Carbon::parse($this->payslip_password_set_at)->diffInMonths(now()) < 3;
    }

    /**
     * Get the user's face descriptor.
     */
    public function faceDescriptor()
    {
        return $this->hasOne(FaceDescriptor::class);
    }

    /**
     * Check if the user has a registered face.
     */
    public function hasFaceRegistered(): bool
    {
        return $this->faceDescriptor()->exists();
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return filled($this->two_factor_secret);
    }

    /**
     * Get the user's cash advances (kasbon).
     */
    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class);
    }

    public function provinsi()
    {
        return $this->belongsTo(Wilayah::class, 'provinsi_kode', 'kode');
    }

    public function kabupaten()
    {
        return $this->belongsTo(Wilayah::class, 'kabupaten_kode', 'kode');
    }

    public function kecamatan()
    {
        return $this->belongsTo(Wilayah::class, 'kecamatan_kode', 'kode');
    }

    public function kelurahan()
    {
        return $this->belongsTo(Wilayah::class, 'kelurahan_kode', 'kode');
    }

    /**
     * Get the assets assigned to the user.
     */
    public function companyAssets()
    {
        return $this->hasMany(CompanyAsset::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Scope a query to only include users managed by the given Admin.
     * Superadmins can see everyone. Regional admins are restricted to their Wilayah.
     */
    public function scopeManagedBy($query, $admin)
    {
        if ($admin->hasGlobalAdminScope()) {
            return $query;
        }

        // If the admin is assigned to a specific regency (kabupaten)
        if ($admin->kabupaten_kode) {
            return $query->where('kabupaten_kode', $admin->kabupaten_kode);
        }

        // If the admin is assigned to a whole province
        if ($admin->provinsi_kode) {
            return $query->where('provinsi_kode', $admin->provinsi_kode);
        }

        // Default: If an admin has no region set, they have national access
        return $query;
    }
}
