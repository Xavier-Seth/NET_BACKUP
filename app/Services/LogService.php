<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

class LogService
{
    public static function record(string $activity): void
    {
        Log::create([
            'user_id' => Auth::id(),
            'activity' => $activity,
        ]);
    }
}
