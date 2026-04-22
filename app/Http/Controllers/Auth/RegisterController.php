<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    public function checkUsername(Request $request)
    {
        $exists = \Illuminate\Support\Facades\DB::table('users')
                    ->where('username', strtolower(trim($request->username)))
                    ->exists();

        if ($exists) {
            return response()->json(['status' => 'error', 'message' => 'Username taken.'], 400);
        }

        return response()->json(['status' => 'success']);
    }

    public function sendVerificationCode(Request $request)
    {
        $request->validate(['email' => 'required|email|unique:users,email']);

        $code = Str::upper(Str::random(6)); // Simple 6 char code

        // In a real app, store this in a temporary table or cache. 
        // For simplicity, we are creating a "pending" user or using cache.
        // Let's use cache for the OTP flow.

        session(['register_email' => $request->email]);
        session(['register_otp' => $code]);
        session(['register_otp_expires' => now()->addMinutes(3)]);

        // Send actual email
        try {
            Mail::to($request->email)->send(new \App\Mail\VerificationCode($code));
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to send email. Please check your address.'], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Verification code sent to ' . $request->email
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate(['code' => 'required']);

        $sessionCode = session('register_otp');
        $expires = session('register_otp_expires');

        if (!$sessionCode) {
            return response()->json(['status' => 'error', 'message' => 'No active verification code found.'], 422);
        }

        if (now()->greaterThan($expires)) {
            return response()->json(['status' => 'error', 'message' => 'Code expired.'], 422);
        }

        if ($request->code !== $sessionCode) {
            return response()->json(['status' => 'error', 'message' => 'Invalid code.'], 422);
        }

        session(['register_email_verified' => true]);

        return response()->json(['status' => 'success']);
    }

    public function register(Request $request)
    {
        $passwordRules = ['required', 'min:8', 'confirmed'];
        if ($request->role === 'organizer') {
            $passwordRules = array_merge($passwordRules, [
                'regex:/[a-z]/',      // at least one lowercase letter
                'regex:/[A-Z]/',      // at least one uppercase letter
                'regex:/[0-9]/',      // at least one digit
                'regex:/[@$!%*#?&]/', // at least one special character
            ]);
        }

        $request->validate([
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => $passwordRules,
            'role' => 'required|in:user,organizer',
        ]);

        if (!session('register_email_verified') || session('register_email') !== $request->email) {
            return back()->withErrors(['email' => 'Email verification required.']);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(),
        ]);

        \Illuminate\Support\Facades\Auth::login($user);

        // Clear session
        session()->forget(['register_email', 'register_otp', 'register_otp_expires', 'register_email_verified']);

        return redirect()->route('dashboard');
    }
}
