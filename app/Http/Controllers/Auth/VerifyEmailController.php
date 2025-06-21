<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * This is unused because email verification is not enabled.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        return redirect()->route('dashboard');
    }
}
