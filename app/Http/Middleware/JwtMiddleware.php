<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use App\User;
use Firebase\JWT\JWT;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $token = $request->header('X-SECURE-TOKEN');
        
        if(!$token) {

            // Unauthorized response if token not there
            return response()->json([
                'status' => false,
                'message' => 'Token not provided.',
                'err_code' => 2,
                'errors' => ''
            ], 401);
        }

        try {
            
            $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);

        } catch(ExpiredException $e) {

            return response()->json([
                'status' => false,
                'message' => 'Session expired. Please log in again',
                'err_code' => 1,
                'errors' => ''
            ], 400);

        } catch(Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
                'err_code' => 2,
                'errors' => ''
            ], 401);
        }

        $user = User::find($credentials->sub);
        // Now let's put the user in the request class so that you can grab it from there
        $request->auth = $user;

        return $next($request);
    }
}