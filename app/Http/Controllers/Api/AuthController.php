<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'UniManagement API',
    version: '1.0.0',
    description: 'API për sistemin e menaxhimit të universitetit'
)]
#[OA\Server(url: 'http://127.0.0.1:8000/api', description: 'Server lokal')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Token i marrë nga /auth/login'
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/register',
        summary: 'Regjistro user të ri',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation', 'role'],
                properties: [
                    new OA\Property(property: 'name',                  type: 'string',  example: 'Arta Kelmendi'),
                    new OA\Property(property: 'email',                 type: 'string',  example: 'arta@uni.edu'),
                    new OA\Property(property: 'password',              type: 'string',  example: 'fjalkalim8'),
                    new OA\Property(property: 'password_confirmation', type: 'string',  example: 'fjalkalim8'),
                    new OA\Property(property: 'role',                  type: 'string',  enum: ['student', 'pedagog']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Regjistrimi u krye me sukses'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
            new OA\Response(response: 429, description: 'Rate limit i kaluar'),
        ]
    )]
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role'     => 'required|in:student,pedagog',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Regjistrimi u krye me sukses!',
            'token'   => $token,
            'user'    => $user,
        ], 201);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Hyr në sistem',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email',    type: 'string', example: 'arta@uni.edu'),
                    new OA\Property(property: 'password', type: 'string', example: 'fjalkalim8'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login u krye — kthen token'),
            new OA\Response(response: 401, description: 'Kredenciale të gabuara'),
            new OA\Response(response: 429, description: 'Rate limit i kaluar'),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Email ose fjalëkalimi është i gabuar.',
            ], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login u krye me sukses!',
            'token'   => $token,
            'user'    => $user,
        ]);
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Dil nga sistemi',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout u krye me sukses'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout u krye me sukses!',
        ]);
    }

    #[OA\Get(
        path: '/me',
        summary: 'Merr të dhënat e userit aktual',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Të dhënat e userit'),
            new OA\Response(response: 401, description: 'Nuk je i autentikuar'),
        ]
    )]
    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}
