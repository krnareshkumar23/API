<?php

namespace App\Http\Controllers\API\V1;

use App\Models\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends ApiController
{
    use ThrottlesLogins;

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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email',
            'phone' => 'required|string|unique:users',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return parent::api_response([], $validator->errors()->first(), false, 400);
        }

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
        $credentials = $request->only('username', 'password');

        //Limit the amount of times users can login
        if ($this->hasTooManyLoginAttempts($request)) {
            return parent::api_response([], 'too many authentication attempts', false, 401);
        }

        $token = JWTAuth::attempt($credentials);

        //If the login attempt failed
        if (!$token) {
            $this->incrementLoginAttempts($request);
            return parent::api_response([], 'invalid credentials', 401);
        }

        $user = User::findOrFail(Auth::user()->id);

        return parent::api_response([
            'user' => $user,
            'token' => $token
        ]);
    }
}
