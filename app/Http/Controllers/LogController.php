<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index()
    {
        $logs = Log::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Logs', [
            'logs' => $logs,
        ]);
    }
}
