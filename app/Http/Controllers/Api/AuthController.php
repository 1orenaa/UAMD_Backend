<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    #[OA\Post(
        path: '/auth/forgot-password',
        summary: 'Kërko link rikuperimi për fjalëkalim',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'arta@uni.edu'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Nëse emaili ekziston, u dërgua një link rikuperimi'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
            new OA\Response(response: 429, description: 'Rate limit i kaluar'),
        ]
    )]
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:254',
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::where('email', $email)->first();

        // Përgjigje uniforme për arsye sigurie — mos i trego sulmuesit nëse
        // emaili ekziston apo jo.
        $genericResponse = response()->json([
            'message' => 'Nëse emaili ekziston në sistem, do të marrësh udhëzimet në kutinë postare.',
        ]);

        if (!$user) {
            return $genericResponse;
        }

        // Gjenero token të ri (i ruajmë vetëm hash-in në DB)
        $plainToken  = Str::random(64);
        $hashedToken = Hash::make($plainToken);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token'      => $hashedToken,
                'created_at' => now(),
            ]
        );

        // Në prodhim do të dërgohej email. Për nevoja akademike e shkruajmë në log.
        $resetLink = rtrim(config('app.frontend_url', 'http://localhost:5173'), '/')
            . '/reset-password?token=' . $plainToken . '&email=' . urlencode($email);

        Log::info('Password reset requested', [
            'email' => $email,
            'link'  => $resetLink,
        ]);

        // Në debug mode kthejmë edhe tokenin që testimi manual të jetë i lehtë.
        if (config('app.debug')) {
            return response()->json([
                'message'    => 'Linku për rikuperim u gjenerua.',
                'reset_link' => $resetLink,
                'token'      => $plainToken,
            ]);
        }

        return $genericResponse;
    }

    #[OA\Post(
        path: '/auth/reset-password',
        summary: 'Rivendos fjalëkalimin me token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'token', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email',                 type: 'string', example: 'arta@uni.edu'),
                    new OA\Property(property: 'token',                 type: 'string', example: '64-char-random-token'),
                    new OA\Property(property: 'password',              type: 'string', example: 'fjalkalim8'),
                    new OA\Property(property: 'password_confirmation', type: 'string', example: 'fjalkalim8'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Fjalëkalimi u rivendos'),
            new OA\Response(response: 400, description: 'Token i pavlefshëm ose ka skaduar'),
            new OA\Response(response: 422, description: 'Gabim validimi'),
        ]
    )]
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:254',
            'token'    => 'required|string',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/',
            ],
        ], [
            'password.regex' => 'Fjalëkalimi duhet të përmbajë të paktën një shkronjë dhe një numër.',
        ]);

        $email = strtolower(trim($request->email));

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$row || !Hash::check($request->token, $row->token)) {
            return response()->json([
                'message' => 'Token i pavlefshëm ose emaili nuk përputhet.',
            ], 400);
        }

        // Skadon pas 60 minutash
        $expireMinutes = 60;
        if (now()->diffInMinutes($row->created_at) > $expireMinutes) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return response()->json([
                'message' => 'Tokeni ka skaduar. Kërko një link të ri rikuperimi.',
            ], 400);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Përdoruesi nuk u gjet.',
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Një-herësh — fshije tokenin pas përdorimit
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Fshi gjithashtu tokenat e vjetra të Sanctum (logout nga sesionet e tjera)
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Fjalëkalimi u rivendos me sukses. Mund të hysh tani.',
        ]);
    }
}
