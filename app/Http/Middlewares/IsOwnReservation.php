<?php
 
namespace App\Http\Middlewares;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Reservation;
 
class IsOwnReservation
{
    public function handle(Request $request, Closure $next): Response
    {
        $reservationId = $request->route('id');
        
        $reservation = Reservation::where('id', $reservationId)
                                    ->get()
                                    ->first();
                            
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        if($reservation->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}