<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Show the forgot password form
    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password'); // Ensure this matches the path to your Blade file
    }

    // Handle sending the reset link email
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    // Send OTP to the user's email
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();

        // Generate OTP
        $otp = rand(100000, 999999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10), // OTP valid for 10 minutes
        ]);

        // Send OTP via email
        Mail::raw("Your OTP for password reset is: $otp", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset OTP');
        });

        return response()->json(['status' => 'success', 'message' => 'OTP sent successfully.']);
    }

    // Show OTP verification page
    public function showOtpVerificationPage(Request $request)
    {
        return view('auth.verify-otp', ['email' => $request->email]);
    }

    // Handle OTP verification and redirection to reset password page
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || (string) $user->otp !== (string) $request->otp || Carbon::now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['status' => 'error', 'message' => 'The OTP you entered is invalid or has expired. Please request a new one.']);
        }

        // Clear OTP after successful verification
        $user->update(['otp' => null, 'otp_expires_at' => null]);

        // Return JSON response with redirect URL
        return response()->json([
            'status' => 'success',
            'redirect_url' => route('password.reset.form', ['email' => $user->email])
        ]);
    }

    // Show password reset form
    public function showResetPasswordForm(Request $request)
    {
        return view('auth.reset-password', ['email' => $request->email]);
    }

    // Reset the password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('login')->with('status', 'Password reset successfully.');
    }
}