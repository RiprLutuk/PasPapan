<?php

namespace App\Support;

use App\Models\Reimbursement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class UserReimbursementService
{
    public function __construct(
        protected UserNotificationRecipientService $notificationRecipients,
    ) {}

    /**
     * @return array{claims: Collection<int, Reimbursement>, total: int}
     */
    public function claimListing(string|int $userId, string $search = '', string $statusFilter = 'all', string $typeFilter = 'all', int $limit = 5): array
    {
        $query = $this->queryForUser($userId, $search, $statusFilter, $typeFilter);
        $total = (clone $query)->count();

        return [
            'claims' => $query->take($limit)->get(),
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createClaim(User $user, array $data, ?UploadedFile $attachment = null): Reimbursement
    {
        $claim = Reimbursement::create([
            'user_id' => $user->id,
            'date' => $data['date'],
            'type' => $data['type'],
            'amount' => $this->normalizeAmount($data['amount'] ?? null),
            'description' => $data['description'],
            'attachment' => $attachment?->store('reimbursements', 'local'),
            'status' => 'pending',
        ]);

        $claim->loadMissing('user.jobTitle.jobLevel', 'user.division');
        $this->notificationRecipients->notifyReimbursementRequested($claim);

        return $claim;
    }

    protected function queryForUser(string|int $userId, string $search, string $statusFilter, string $typeFilter): Builder
    {
        return Reimbursement::query()
            ->where('user_id', $userId)
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $subQuery) use ($search) {
                    $term = '%'.trim($search).'%';

                    $subQuery->where('description', 'like', $term)
                        ->orWhere('type', 'like', $term);
                });
            })
            ->when($statusFilter !== 'all', fn (Builder $builder) => $builder->where('status', $statusFilter))
            ->when($typeFilter !== 'all', fn (Builder $builder) => $builder->where('type', $typeFilter))
            ->latest('date');
    }

    protected function normalizeAmount(mixed $amount): int
    {
        return (int) str_replace(['.', ','], '', (string) $amount);
    }
}
