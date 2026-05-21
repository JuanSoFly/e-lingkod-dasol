<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ]);

                if ($request->has('setup')) {
                    $user->email_verified_at = now();
                }

                $user->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            if ($request->has('setup')) {
                $user = User::where('email', $request->email)->first();
                if ($user) {
                    Auth::login($user);
                    
                    // Determine direct route to prevent session flash loss on double-redirect
                    $hasDepartmentHeadAssignment = \App\Models\OfficeAssignment::where('user_id', $user->id)
                        ->where('role', \App\Models\OfficeAssignment::ROLE_DEPARTMENT_HEAD)
                        ->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('ended_date')
                                  ->orWhere('ended_date', '>=', now());
                        })
                        ->exists();

                    $canAccessOPCRDashboard = $user->hasAnyRole(['Assessor', 'Final Approver']);
                    $isAdminOrManager = $user->hasAnyRole(['HR Admin', 'Super Admin']) || 
                                       $hasDepartmentHeadAssignment || 
                                       $canAccessOPCRDashboard;
                    
                    if (!$isAdminOrManager && $user->employee_id) {
                        return redirect()->route('employee-portal.dashboard')->with('welcome', true);
                    }

                    return redirect()->route('dashboard')->with('welcome', true);
                }
            }

            $message = $request->has('setup')
                ? __('Your account has been set up successfully. Please log in to continue.')
                : __($status);

            return redirect()->route('login')->with('status', $message);
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
