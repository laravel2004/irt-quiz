<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    use ResponseTrait;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string',
            'age' => 'nullable|integer',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        try {
            $user = $this->authService->register($request->all());
            return $this->successResponse($user, 'User registered successfully', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            $user = $this->authService->login($credentials);
            
            // For web session, we just redirect. For API, return token.
            if ($request->wantsJson()) {
                return $this->successResponse($user, 'Login successful');
            }

            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            
            return redirect()->route('login')->with('success', 'Login successful');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return $this->errorResponse($e->getMessage(), 401);
            }
            return back()->withErrors(['email' => $e->getMessage()]);
        }
    }

    public function logout()
    {
        $this->authService->logout();
        return redirect()->route('login');
    }
}
