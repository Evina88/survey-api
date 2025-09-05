<?php

namespace App\Http\Controllers;

use App\Models\Responder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email|unique:responders,email',
            'password' => 'required|string|min:8',
        ]);

        $responder = Responder::create([
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Issue JWT
        $token = auth('api')->login($responder);

        return response()->json([
            'status'  => 'success',
            'message' => 'Registered successfully.',
            'data'    => [
                'token'     => $token,
                'responder' => ['id' => $responder->id, 'email' => $responder->email],
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $creds = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!$token = auth('api')->attempt($creds)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $r = auth('api')->user();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged in.',
            'data'    => [
                'token'     => $token,
                'responder' => ['id' => $r->id, 'email' => $r->email],
            ],
        ]);
    }

    public function me()
    {
        $r = auth('api')->user();

        return response()->json([
            'status'  => 'success',
            'message' => 'Current responder.',
            'data'    => ['id' => $r->id, 'email' => $r->email],
        ]);
    }
}
