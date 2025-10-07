<?php
namespace App\Http\Middleware;
use App\Services\impl\AuthService;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
class Authenticate
{
    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function handle(Request $request, Closure $next, $guard = null)
    {
        $token = $this->getTokenFromRequest($request);
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token not provided.',
            ], 401);
        }
        try {
            $user = $this->authService->getUserFromToken($token);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Invalid token.',
                ], 401);
            }
            $request->merge(['user' => $user]);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. ' . $e->getMessage(),
            ], 401);
        }
    }
    protected function getTokenFromRequest(Request $request): ?string
    {
        $header = $request->header('Authorization');
        if (!$header) {
            return null;
        }
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
}