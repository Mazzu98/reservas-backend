<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @OA\Info(
 * title="Swagger with Laravel",
 * version="1.0.0",
 * )
 * @OA\SecurityScheme(
 * type="http",
 * securityScheme="bearerAuth",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Register a user",
     *    @OA\RequestBody(
     *        required=true,
     *       @OA\JsonContent(
     *          required={"name","email","password","password_confirmation"},
     *          @OA\Property(property="name", type="string", format="name", example="John Doe"),
     *          @OA\Property(property="email", type="string", format="email", example="some@email.com"),
     *          @OA\Property(property="password", type="string", format="password", example="12345678"),
     *          @OA\Property(property="password_confirmation", type="string", format="password", example="12345678")
     *      ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         ),
     *        description="Token of created user"
     *    ),
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login",
     *    @OA\RequestBody(
     *        required=true,
     *       @OA\JsonContent(
     *          required={"email","password"},
     *          @OA\Property(property="email", type="string", format="email", example="some@email.com"),
     *          @OA\Property(property="password", type="string", format="password", example="12345678"),
     *      ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer")
     *         ),
     *        description="Token of created user"
     *    ),
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        try {
            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Invalid login details'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not create token'], 500);
        }

        return response()->json(['access_token' => $token, 'token_type' => 'Bearer']);
    }

    /**
     * @OA\Get(
     *     path="/api/user",
     *     tags={"Auth"},
     *     summary="Get user info",
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Client"),
     *             @OA\Property(property="email", type="string", example="client@client.com"),
     *             @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-09-28T02:28:52.000000Z"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2024-09-28T02:28:52.000000Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-09-28T02:28:52.000000Z"),
     *             @OA\Property(property="role", type="string", example="client")
     *         ),
     *        description="User info"
     *    ),
     * )
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
