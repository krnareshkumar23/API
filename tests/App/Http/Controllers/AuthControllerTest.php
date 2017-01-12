<?php

/**
 * Created by PhpStorm.
 * User: Thomas
 * Date: 04/01/2017
 * Time: 11:04
 */
class AuthControllerTest extends TestCase
{
    public function testInvalidPasswordRegistration()
    {
        $this
            ->apiCall('POST', 'api/v1/auth/register', [
                'email' => 'test@email.com',
            ])
            ->seeError(400);
    }

    public function testInvalidEmailRegistration()
    {
        $this
            ->apiCall('POST', 'api/v1/auth/register', [
                'password' => 'password',
            ])
            ->seeError(400);
    }

    public function testValidRegistration()
    {
        $this
            ->apiCall('POST', 'api/v1/auth/register', [
                'email' => 'test@email.com',
                'password' => 'password',
            ])
            ->seeSuccess()
            ->seePayloadStructure([
                'token',
                'user'
            ]);
    }

    public function testInvalidLogin()
    {
        $this
            ->apiCall('POST', 'api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'Password123'
            ])
            ->seeError(401);
    }

    public function testInvalidPasswordLogin()
    {
        factory(\App\Models\User::class)->create([
            'email' => 'test@email.com',
            'password' => 'Password123'
        ]);

        $this
            ->apiCall('POST', 'api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'WrongPassword'
            ])
            ->seeError(401);
    }

    public function testValidLogin()
    {
        factory(\App\Models\User::class)->create([
            'email' => 'test@email.com',
            'password' => 'Password123',
        ]);

        $this
            ->apiCall('POST', 'api/v1/auth/login', [
                'email' => 'test@email.com',
                'password' => 'Password123'
            ])
            ->seeSuccess()
            ->seePayloadStructure([
                'token',
                'user'
            ]);
    }

    public function testInvalidLogout()
    {
        $this
            ->apiCall('POST', 'api/v1/auth/logout')
            ->seeError(400);
    }

    public function testValidLogout()
    {
        $this
            ->withNewToken()
            ->apiCall('POST', 'api/v1/auth/logout')
            ->seeSuccess();
    }
}