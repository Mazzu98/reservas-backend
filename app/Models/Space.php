<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Reservation;

class Space extends Model
{
    use HasFactory;

    public $timestamps = false;

    static function getAvailableTimeSlots($space_id, $dayStart, $dayEnd)
    {
        $allSlots = Reservation::getTimeSlots($dayStart, $dayEnd);
        $reservations = Reservation::getReservationsInRange($space_id, $dayStart, $dayEnd);
    
        $availableSlots = [];
    
        foreach ($allSlots as $slot) {
            $slotAvailable = true;
    
            foreach ($reservations as $reservation) {
                if (!($slot['end'] <= $reservation->start_date || $slot['start'] >= $reservation->end_date)) {
                    $slotAvailable = false;
                    break;
                }
            }
    
            if ($slotAvailable) {
                $availableSlots[] = $slot;
            }
        }
    
        return $availableSlots;
    }
}
