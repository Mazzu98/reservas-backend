<?php
 
namespace App\Http\Middlewares;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if($request->user()->role == 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}