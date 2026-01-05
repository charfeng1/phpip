<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles user authentication and login.
 *
 * Uses Laravel's AuthenticatesUsers trait to provide standard login functionality.
 * Configured to use the 'login' field instead of 'email' for authentication.
 */
class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Get the login username field.
     *
     * Uses the 'login' column instead of Laravel's default 'email' field.
     *
     * @return string
     */
    public function username()
    {
        return 'login';
    }

    /**
     * Attempt to log the user into the application.
     *
     * Overrides default behavior to handle PostgreSQL CHAR column padding.
     * The login column is CHAR(16) which gets padded with spaces, so we use
     * TRIM() in the query to find the user, then pass the raw (padded) login
     * value to Auth::attempt.
     *
     * Note: We use getAttributes()['login'] instead of $user->login because
     * the User model uses TrimsCharColumns trait which would return a trimmed
     * value, causing Auth::attempt to fail.
     *
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $login = $request->input($this->username());
        $password = $request->input('password');

        // Find user using TRIM() to handle CHAR column padding
        $user = User::whereRaw('TRIM(login) = ?', [$login])->first();

        // Use raw 'login' attribute if user found, otherwise use original input.
        // This ensures Auth::attempt is always called for correct event/throttling.
        $loginCredential = $user ? $user->getAttributes()['login'] : $login;

        return Auth::attempt(
            ['login' => $loginCredential, 'password' => $password],
            $request->boolean('remember')
        );
    }
}
