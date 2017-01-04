<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    use AuthenticatesUsers;

    protected $maxLoginAttempts = 5;
    protected $lockoutTime = 600;

    /**
     * Create a new user.
     *
     * @param Request $request The HTTP request
     * @return json
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|between:2,255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|unique:users',
            'phone' => 'required|string|unique:users',
            'password' => 'required'
        ]);

        $user = new User();
        $user->name = $request->input('name');
        $user->username = $request->input('username');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->password = $request->input('password');
        $user->save();

        return $this->login($request);
    }

    /**
     * Log in a user.
     *
     * @param Request $request
     * @return json
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        //Limit the amount of times users can login
        if ($this->hasTooManyLoginAttempts($request)) {
            return parent::api_response([], 'too many authentication attempts, try again in '.$this->lockoutTime.' seconds', false, 401);
        }

        $token = JWTAuth::attempt($credentials);

        //If the login attempt failed
        if (!$token) {
            $this->incrementLoginAttempts($request);
            return parent::api_response([], 'invalid credentials', 401);
        }

        $user = $request->user()->fresh();

        return parent::api_response([
            'token' => $token,
            'user' => $user
        ]);
    }

    /**
     * Request a password reset email
     *
     * @param Request $request
     * @return json
     */
    public function requestPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->input('email'))->firstOrFail();
        $reset = $user->createPasswordReset($request->ip());
        $user->notify(new ResetPassword($reset));

        return parent::api_response([]);
    }
}
