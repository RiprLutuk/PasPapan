<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasUlids;
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
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
        'profile_photo_path',
        'language',
        'basic_salary',
        'hourly_rate',
        'payslip_password',
        'payslip_password_set_at',
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
            'password' => 'hashed',
        ];
    }

    public static $groups = ['user', 'admin', 'superadmin'];

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
        return !$this->isAdmin;
    }

    final public function getIsDemoAttribute(): bool
    {
        return in_array($this->email, [
            'admin123@paspapan.com',
            'user123@paspapan.com',
        ]);
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

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    /**
     * Get the user's supervisor (Same Division, Higher Job Level).
     * Assumes lower rank number = higher seniority (1=Head, 4=Staff)
     */
    public function getSupervisorAttribute()
    {
        if (!$this->division_id || !$this->job_title_id || !$this->jobTitle || !$this->jobTitle->jobLevel) {
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
            ->sortByDesc(fn($u) => $u->jobTitle->jobLevel->rank)
            ->first();
    }

    /**
     * Get all subordinates for this user instance.
     */
    public function getSubordinatesAttribute()
    {
        if (!$this->division_id || !$this->jobTitle || !$this->jobTitle->jobLevel) {
            return collect();
        }

        $myRank = $this->jobTitle->jobLevel->rank;

        return User::where('division_id', $this->division_id)
            ->whereHas('jobTitle.jobLevel', function ($q) use ($myRank) {
                $q->where('rank', '>', $myRank);
            })
            ->get();
    }

    /**
     * Check if the user has a valid (non-expired) payslip password.
     * Expired if set > 3 months ago.
     */
    public function hasValidPayslipPassword(): bool
    {
        if (!$this->payslip_password || !$this->payslip_password_set_at) {
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
        if ($admin->isSuperadmin) {
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
