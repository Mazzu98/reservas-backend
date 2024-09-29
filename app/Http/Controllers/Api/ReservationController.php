<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Space;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * return listing of the resource by user.
     */
    public function get(Request $request)
    {
        $reservations = Reservation::where('user_id', $request->user()->id)
                                    ->get();
        return response()->json($reservations);
    }

    /**
     * return resource by user.
     */
    public function getById(int $id, Request $request)
    {
        $reservation = Reservation::where('id', $id)
                                    ->get()
                                    ->first();
                            
        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        if($reservation->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($reservation);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $userId =  $request->user()->id;  

        $request->validate([
            'space_id' => 'required|exists:spaces,id',
            'event_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startHour = env('SCHEDULE_START_HOUR', 8);
        $endHour = env('SCHEDULE_END_HOUR', 22);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if ($startDate->hour < $startHour) {
            return response()->json(['message' => "The limit start time is {$startHour}:00"], 422);
        }

        if ($endDate->hour > $endHour) {
            return response()->json(['message' => "The limit end time is {$endHour}:00."], 422);
        }

        $conflictingReservations = Reservation::where('space_id', $request->space_id)
                                                ->where('start_date', '<', $request->end_date)
                                                ->where('end_date', '>', $request->start_date)
                                                ->exists();

        if ($conflictingReservations) {
            return response()->json(['message' => 'There is already a reservation that conflicts with the selected time.'], 409);
        }

        Reservation::create([
            'space_id' => $request->space_id,
            'user_id' => $userId,
            'event_name' => $request->event_name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        return response()->json(['message' => 'Reservation created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reservation = Reservation::findOrFail($id);
        return response()->json($reservation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id, Request $request)
    {
        $request->validate([
            'space_id' => 'required',
            'user_id' => 'prohibited',
            'event_name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);


        $startHour = env('SCHEDULE_START_HOUR', 8);
        $endHour = env('SCHEDULE_END_HOUR', 22);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if ($startDate->hour < $startHour) {
            return response()->json(['message' => "The limit start time is {$startHour}:00"], 422);
        }

        if ($endDate->hour > $endHour) {
            return response()->json(['message' => "The limit end time is {$endHour}:00."], 422);
        }

        $conflictingReservations = Reservation::where('space_id', $request->space_id)
                                                ->where('id', '!=', $id)
                                                ->where('start_date', '<', $request->end_date)
                                                ->where('end_date', '>', $request->start_date)
                                                ->exists();

        if ($conflictingReservations) {
            return response()->json(['message' => 'There is already a reservation that conflicts with the selected time.'], 409);
        }

        $reservation = Reservation::findOrFail($id);
        $reservation->update($request->all());

        return response()->json(['message' => 'Reservation updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully']);
    }
}
