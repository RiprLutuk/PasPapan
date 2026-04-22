<?php

namespace App\Policies;

use App\Models\Holiday;
use App\Models\User;

class HolidayPolicy
{
    public function create(User $user): bool
    {
        return $user->can('manageHolidays');
    }

    public function update(User $user, Holiday $holiday): bool
    {
        return $user->can('manageHolidays');
    }

    public function delete(User $user, Holiday $holiday): bool
    {
        return $user->can('manageHolidays');
    }
}
