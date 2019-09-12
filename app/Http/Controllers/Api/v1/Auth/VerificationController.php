<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified as VerifiedEvent;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param Request $request
     * @return Response
     */
    public function verify(Request $request)
    {
        // Ensure verification signature matches
        if (!hash_equals((string)$request->route('id'), (string)$request->user()->getKey()) ||
            !hash_equals((string)$request->route('hash'), sha1($request->user()->getEmailForVerification())))
            return response()->json([
                'message' => __('auth.verification.invalid'),
                'verified' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        // Don't attempt to re-verify an already verified user
        if ($request->user()->hasVerifiedEmail())
            return response()->json([
                'message' => __('auth.verification.conflict'),
                'verified' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        if ($request->user()->markEmailAsVerified())
            event(new VerifiedEvent($request->user()));

        return response()->json([
            'message' => __('auth.verification.verified'),
            'verified' => true
        ]);
    }

    /**
     * Resend the email verification notification.
     *
     * @param Request $request
     * @return Response
     */
    public function resend(Request $request)
    {
        // Don't re-send an email to an already verified user
        if (request()->user()->hasVerifiedEmail())
            return response()->json([
                'message' => __('auth.verification.conflict'),
                'resent' => false
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        request()->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => __('auth.verification.resent'),
            'resent' => true
        ]);
    }
}
