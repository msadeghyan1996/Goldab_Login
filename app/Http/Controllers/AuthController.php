<?php
namespace App\Http\Controllers;
use App\Services\impl\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Annotations as OA;
 * @OA\Info(
 *     title="OTP Authentication API",
 *     version="1.0.0",
 *     description="Mobile-based OTP authentication system with JWT",
 *     @OA\Contact(
 *         email="support@example.com",
 *         name="API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearer_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT Bearer token"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication and user management endpoints"
 * )
 */
class AuthController extends Controller
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
     * @OA\Post(
     *     path="/api/v1/auth/check-mobile",
     *     summary="Check if mobile number exists",
     *     description="Check whether a mobile number is registered in the system",
     *     operationId="checkMobile",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile_number"},
     *             @OA\Property(property="mobile_number", type="string", example="9145813194", description="Mobile number (10-15 digits)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mobile number check result",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Mobile number checked successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="exists", type="boolean", example=true),
     *                 @OA\Property(property="is_verified", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation failed")
     *         )
     *     )
     * )
     */
    public function checkMobile(Request $request): JsonResponse
    {
        try {
            $this->validateData($request->all(), [
                'mobile_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            ]);
            $result = $this->authService->checkMobile($request->input('mobile_number'));
            return $this->successResponse($result, 'Mobile number checked successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login with password",
     *     description="Authenticate user with mobile number and password",
     *     operationId="login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile_number", "password"},
     *             @OA\Property(property="mobile_number", type="string", example="9145813194", description="Mobile number"),
     *             @OA\Property(property="password", type="string", example="MyPassword123", description="Password (min 8 characters)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="mobile_number", type="string", example="9145813194"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe")
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGci...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $this->validateData($request->all(), [
                'mobile_number' => 'required|string|regex:/^[0-9]{10,15}$/',
                'password' => 'required|string|min:8',
            ]);
            $result = $this->authService->loginWithPassword(
                $request->input('mobile_number'),
                $request->input('password'),
                $request->ip(),
                $request->header('User-Agent')
            );
            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Login successful');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }
     * @OA\Post(
     *     path="/api/v1/auth/request-otp",
     *     summary="Request OTP code",
     *     description="Send OTP code via SMS for registration or verification",
     *     operationId="requestOtp",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile_number"},
     *             @OA\Property(property="mobile_number", type="string", example="9145813194", description="Mobile number to receive OTP")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="success", type="boolean", example=true),
     *                 @OA\Property(property="message", type="string", example="OTP sent successfully"),
     *                 @OA\Property(property="otp_code", type="string", example="123456", description="OTP code (only in debug mode)"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", example="2025-10-07T10:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Rate limit exceeded")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function requestOtp(Request $request): JsonResponse
    {
        try {
            $this->validateData($request->all(), [
                'mobile_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            ]);
            $result = $this->authService->initiateRegistration($request->input('mobile_number'));
            return $this->successResponse($result, 'OTP sent successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Complete registration with OTP",
     *     description="Verify OTP and complete user registration",
     *     operationId="register",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile_number", "otp_code", "first_name", "last_name", "national_id", "password", "password_confirmation"},
     *             @OA\Property(property="mobile_number", type="string", example="9145813194", description="Mobile number"),
     *             @OA\Property(property="otp_code", type="string", example="123456", description="6-digit OTP code"),
     *             @OA\Property(property="first_name", type="string", example="John", description="First name"),
     *             @OA\Property(property="last_name", type="string", example="Doe", description="Last name"),
     *             @OA\Property(property="national_id", type="string", example="1234567890", description="10-digit national ID"),
     *             @OA\Property(property="password", type="string", example="MyPassword123", description="Password (min 8 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", example="MyPassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration completed successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="mobile_number", type="string", example="9145813194"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="national_id", type="string", example="1234567890"),
     *                     @OA\Property(property="is_mobile_verified", type="boolean", example=true)
     *                 ),
     *                 @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGci...")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid or expired OTP code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $this->validateData($request->all(), [
                'mobile_number' => 'required|string|regex:/^[0-9]{10,15}$/',
                'otp_code' => 'required|string|size:6',
                'first_name' => 'required|string|max:100',
                'last_name' => 'required|string|max:100',
                'national_id' => 'required|string|regex:/^[0-9]{10}$/',
                'password' => 'required|string|min:8|confirmed',
            ]);
            $result = $this->authService->completeRegistration(
                $request->input('mobile_number'),
                $request->input('otp_code'),
                [
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'national_id' => $request->input('national_id'),
                    'password' => $request->input('password'),
                ],
                $request->ip(),
                $request->header('User-Agent')
            );
            return $this->successResponse([
                'user' => $result['user'],
                'token' => $result['token'],
            ], 'Registration completed successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get authenticated user",
     *     description="Retrieve current authenticated user information",
     *     operationId="me",
     *     tags={"Authentication"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="mobile_number", type="string", example="9145813194"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="national_id", type="string", example="1234567890"),
     *                 @OA\Property(property="is_mobile_verified", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }
            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}