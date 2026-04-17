<?php

namespace App\Http\Controllers\Admin\ImportExport\Concerns;

trait HandlesServiceResponse
{
    protected function handleServiceResponse(mixed $response)
    {
        if ($response === null) {
            return back()
                ->with('flash.banner', 'Advanced Reporting is an Enterprise Feature 🔒. Please Upgrade.')
                ->with('flash.bannerStyle', 'danger');
        }

        return $response;
    }
}
