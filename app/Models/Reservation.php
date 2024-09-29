<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Reservation extends Model
{
    use HasFactory;

    public $timestamps = false;

    public $fillable = [
        'space_id',
        'user_id',
        'event_name',
        'start_date',
        'end_date',
    ];

    public static function getReservationsInRange($space_id, $start, $end)
    {
        return self::where('space_id', $space_id)
                    ->where(function ($query) use ($start, $end) {
                        $query->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($query) use ($start, $end) {
                                $query->where('start_date', '<=', $start)
                                        ->where('end_date', '>=', $end);
                            });
                    })
                    ->get();
    }

    static function getTimeSlots($dayStart, $dayEnd, $intervalMinutes = 30)
{
    $timeSlots = [];
    
    $currentDay = (new Carbon($dayStart))->startOfDay();
    $endDay = (new Carbon($dayEnd))->endOfDay();
    
    $startHour = env('SCHEDULE_START_HOUR', 8);
    $endHour = env('SCHEDULE_END_HOUR', 22);

    while ($currentDay <= $endDay) {
        $dayStart = $currentDay->copy()->hour($startHour)->minute(0);
        $dayEnd = $currentDay->copy()->hour($endHour)->minute(0);

        $currentSlot = $dayStart;
        while ($currentSlot < $dayEnd) {
            $nextSlot = $currentSlot->copy()->addMinutes($intervalMinutes);
            $timeSlots[] = [
                'start' => $currentSlot->copy(),
                'end' => $nextSlot
            ];
            $currentSlot = $nextSlot;
        }

        $currentDay->addDay();
    }

    return $timeSlots;
}
}
