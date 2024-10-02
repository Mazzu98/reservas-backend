<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Space;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reservation",
     *     tags={"Reservation"},
     *     summary="Get user reservations",
     *   security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=13),
     *                 @OA\Property(property="space_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_name", type="string", example="Some festival"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", example="2024-10-02 12:30:00"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", example="2024-10-02 15:00:00"),
     *                 @OA\Property(property="space_name", type="string", example="Big saloon")
     *             )
     *         ),
     *        description="User reservations"
     *    ),
     * )
     */
    public function get(Request $request)
    {
        $reservations = Reservation::where('user_id', $request->user()->id)
            ->with('space')
            ->get()
            ->map(function ($reservation) {
                $reservation->space_name = $reservation->space->name;
                return $reservation;
            });
        return response()->json($reservations);
    }

    /**
     * @OA\Get(
     *     path="/api/reservation/{id}",
     *     tags={"Reservation"},
     *     summary="Get particular user reservation",
     *  security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="id", type="integer", example=13),
     *                 @OA\Property(property="space_id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="event_name", type="string", example="Some festival"),
     *                 @OA\Property(property="start_date", type="string", format="date-time", example="2024-10-02 12:30:00"),
     *                 @OA\Property(property="end_date", type="string", format="date-time", example="2024-10-02 15:00:00"),
     *                 @OA\Property(property="space_name", type="string", example="Big saloon")
     *         ),
     *        description="User reservation"
     *    ),
     * )
     */
    public function getById(int $id, Request $request)
    {
        $reservation = Reservation::where('id', $id)
            ->get()
            ->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        if ($reservation->user_id != $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($reservation);
    }

    /**
     * @OA\Post(
     *     path="/api/reservation",
     *     tags={"Reservation"},
     *     summary="Create a reservation",
     *  security={{ "bearerAuth": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"space_id","event_name","start_date","end_date"},
     *              @OA\Property(property="space_id", type="integer", example=1),
     *              @OA\Property(property="event_name", type="string", example="Some festival"),
     *              @OA\Property(property="start_date", type="string", format="date-time", example="2024-10-02 12:30:00"),
     *              @OA\Property(property="end_date", type="string", format="date-time", example="2024-10-02 15:00:00"),
     *          ),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="message"),
     *         ),
     *        description="result message"
     *    ),
     * )
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

        $space = Space::findOrFail($request->space_id)->where('enable', true);
        if (!$space) {
            return response()->json(['message' => 'The selected space is not available.'], 409);
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
     * @OA\Put(
     *     path="/api/reservation/{id}",
     *     tags={"Reservation"},
     *     summary="Update a reservation",
     *     security={{ "bearerAuth": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"space_id","event_name","start_date","end_date"},
     *              @OA\Property(property="space_id", type="integer", example=1),
     *              @OA\Property(property="event_name", type="string", example="Some festival"),
     *              @OA\Property(property="start_date", type="string", format="date-time", example="2024-10-02 12:30:00"),
     *              @OA\Property(property="end_date", type="string", format="date-time", example="2024-10-02 15:00:00"),
     *          ),
     *      ),
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="message"),
     *         ),
     *        description="Result message"
     *    ),
     * )
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

        $space = Space::findOrFail($request->space_id)->where('enable', true);
        if (!$space) {
            return response()->json(['message' => 'The selected space is not available.'], 409);
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
     * @OA\Delete(
     *     path="/api/reservation/{id}",
     *     tags={"Reservation"},
     *     summary="Delete reservation",
     *    security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="message", type="string", example="message"),
     *         ),
     *        description="Result messge"
     *    ),
     * )
     */
    public function delete(string $id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully']);
    }
}
