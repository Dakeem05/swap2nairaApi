<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterUserRequest;
use App\Http\Requests\Api\V1\RegistrationVerifyRequest;
use App\Http\Requests\Api\V1\VerifyForgotPassword;
use App\Models\User;
use App\Services\Api\V1\AuthenticationService;
use App\Traits\Api\V1\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Expr\Cast\Object_;

class AuthenticationController extends Controller
{
    use ApiResponseTrait;

    public function register (RegisterUserRequest $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->register($_data);
        
        $token = Auth::login($request);
        return $this->successResponse([
            // "user" => $request,
            "token" => $token
        ], "Signup successfull", 201);
    }

    public function resend(String $email, AuthenticationService $auth_service)
    {
        $_data = (Object) array(
            "email" => $email,
        );

        $request = $auth_service->resend($_data);
        // return $request;
        if ($request) {
            return $this->successResponse("The otp has been resent.", 201);
        } else{
            return $this->serverErrorResponse('An error occurred.');
        }
    }

    public function verify (RegistrationVerifyRequest $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->verify($_data);
        
        if ($request) {
            return $this->successResponse("Registration successful.");
        } else{
            return $this->serverErrorResponse('Wrong code? Resend.');
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->forgotPassword($_data);
        
        if ($request) {
            return $this->successResponse("Sent, check your mail");
        } else{
            return $this->serverErrorResponse('Something went wrong');
        }
       
    }

    public function resendForgotPassword(ForgotPasswordRequest $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->forgotPassword($_data);
        
        if ($request) {
            return $this->successResponse("The otp has been resent.");
        } else{
            return $this->serverErrorResponse('Something went wrong');
        }
    }

    public function verifyForgotPassword (RegistrationVerifyRequest $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->verifyForgot($_data);
        
        if ($request) {
            return $this->successResponse("Verification successful.");
        } else{
            return $this->serverErrorResponse('Wrong code? Resend.');
        }
    }

    public function changePassword (VerifyForgotPassword $request, AuthenticationService $auth_service)
    {
        $_data = (Object) $request->validated();

        $request = $auth_service->changePassword($_data);
        
        if ($request) {
            return $this->successResponse("Password changed.");
        } else{
            return $this->serverErrorResponse('An error occurred.');
        }

    }

    public function login (LoginRequest $request)
    {
        $credentials = $request->only(['email', 'password']);

        $token = Auth::attempt($credentials);

        if($token) {

            return $this->successResponse(['token' => $token], 'Login was successful.');
                    
        } else{
            return $this->unauthorizedResponse();
        }
    }

    public function getUser()
    {
        $user = User::where('id', auth()->user()->id)->with('wallet')->first();
        $data = [$user->wallet];

        foreach ($data as $value) {
            if ($value->pin == null) {
                $value->pin = null;
                $value->is_pin = false;
            } else {
                $value->pin = null;
                $value->is_pin = true;
            }
        }
        if ($user) {
            return $this->successResponse(['user' => $user], 'User data', 200);
        } else {
            return $this->unauthorizedResponse();
        }
    }

    public function logout(Request $request)
    {   
        Auth::logout(true);
        return $this->successResponse('Logged out');
    }
}
