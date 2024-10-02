<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Space;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class SpaceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/space",
     *     tags={"Space"},
     *     summary="List all enabled spaces",
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Small saloon"),
     *                 @OA\Property(property="type", type="string", example="saloon"),
     *                 @OA\Property(property="description", type="string", example="A cozy and intimate space for small gatherings and events."),
     *                 @OA\Property(property="capacity", type="integer", example=10),
     *                 @OA\Property(property="enable", type="boolean", example=true),
     *                 @OA\Property(property="image_url", type="string", example="http://example.com/small_space.jpg"),
     *             ),
     *         ),
     *        description="Spaces"
     *    ),
     * )
     */
    public function get()
    {
        $spaces = Space::where('enable', true)
            ->get();
        return response()->json($spaces);
    }

    /**
     * @OA\Get(
     *     path="/api/space/{id}/daily-available-slots",
     *     tags={"Space"},
     *     summary="List Available Time Slots for a Space",
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="start", type="string", example="08:00"),
     *                 @OA\Property(property="end", type="string", example="08:30"),
     *         ),
     *        description="Available times"
     *    ),
     * )
     */
    public function getDailyAvailableTimeSlots(int $id, Request $request)
    {
        $reservationIgnore = $request->query('reservationIgnore');
        $dayStart = Carbon::parse($request->query('day'))->startOfDay();
        $dayEnd = Carbon::parse($request->query('day'))->endOfDay();
        $spaces = new Collection(Space::getAvailableTimeSlots($id, $dayStart, $dayEnd, $reservationIgnore));
        $spaces = $spaces->map(function ($space) {
            return [
                'start' => $space['start']->format('H:i'),
                'end' => $space['end']->format('H:i')
            ];
        });
        return response()->json($spaces);
    }

    /**
     * @OA\Post(
     *     path="/api/space",
     *     tags={"Space"},
     *     summary="Create a space",
     *     security={{ "bearerAuth": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"description","type","capacity","image"},
     *              @OA\Property(property="description", type="string", example="A cozy and intimate space for small gatherings and events."),
     *              @OA\Property(property="type", type="string", example="saloon"),
     *              @OA\Property(property="capacity", type="integer", example=10),
     *              @OA\Property(property="image", type="string", format="binary"),
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|string',
            'capacity' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        Space::create($data);


        return response()->json(['message' => 'Space created successfully'], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/space/{id}",
     *     tags={"Space"},
     *     summary="space",
     *     security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Small saloon"),
     *                 @OA\Property(property="type", type="string", example="saloon"),
     *                 @OA\Property(property="description", type="string", example="A cozy and intimate space for small gatherings and events."),
     *                 @OA\Property(property="capacity", type="integer", example=10),
     *                 @OA\Property(property="enable", type="boolean", example=true),
     *                 @OA\Property(property="image_url", type="string", example="http://example.com/small_space.jpg"),
     *         ),
     *        description="Space"
     *    ),
     * )
     */
    public function getById(string $id)
    {
        $space = Space::findOrFail($id);
        return response()->json($space);
    }

    /**
     * @OA\Get(
     *     path="/api/space/search?type=${type}&capacity=${capacity}&start_date=${startDate}&end_date=${endDate}",
     *     tags={"Space"},
     *     summary="Available spaces for a given filter",
     *    security={{ "bearerAuth": {} }},
     *     @OA\Response(
     *         response=200,
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=2),
     *                 @OA\Property(property="name", type="string", example="Small saloon"),
     *                 @OA\Property(property="type", type="string", example="saloon"),
     *                 @OA\Property(property="description", type="string", example="A cozy and intimate space for small gatherings and events."),
     *                 @OA\Property(property="capacity", type="integer", example=10),
     *                 @OA\Property(property="enable", type="boolean", example=true),
     *                 @OA\Property(property="image_url", type="string", example="http://example.com/small_space.jpg"),
     *             ),
     *         ),
     *        description="Available Spaces"
     *    ),
     * )
     */
    public function query(Request $request)
    {
        $type = $request->query('type');
        $capacity = $request->query('capacity');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $spaces = Space::query();

        if ($type) {
            $spaces->whereRaw('LOWER(type) LIKE ?', [strtolower($type) . '%']);
        }

        if ($capacity) {
            $spaces->where('capacity', '>=', $capacity);
        }

        $allSpaces = $spaces->where('enable', true)
            ->get();

        $availableSpaces = [];
        $timeSlots = Reservation::getTimeSlots(new Carbon($startDate), new Carbon($endDate));

        foreach ($allSpaces as $space) {
            $spaceHasAvailableTime = false;

            foreach ($timeSlots as $slot) {
                $overlappingReservations = $space->reservations()->where(function ($query) use ($slot) {
                    $query->where('start_date', '<', $slot['end'])
                        ->where('end_date', '>', $slot['start']);
                })->exists();

                if (!$overlappingReservations) {
                    $spaceHasAvailableTime = true;
                    break;
                }
            }

            if ($spaceHasAvailableTime) {
                $availableSpaces[] = $space;
            }
        }

        return response()->json($availableSpaces);
    }


    /**
     * @OA\Put(
     *     path="/api/space/{id}",
     *     tags={"Space"},
     *     summary="Update a space",
     *    security={{ "bearerAuth": {} }},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"description","type","capacity","image"},
     *              @OA\Property(property="description", type="string", example="A cozy and intimate space for small gatherings and events."),
     *              @OA\Property(property="type", type="string", example="saloon"),
     *              @OA\Property(property="capacity", type="integer", example=10),
     *              @OA\Property(property="image", type="string", format="binary"),
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
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'type' => 'required|string',
            'capacity' => 'required|integer',
            'image' => 'nullable|file|mimes:jpg,png,jpeg|max:2048',
        ]);

        $space = Space::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($space->image) {
                Storage::delete('public/' . $space->image);
            }

            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        $space->update($data);

        return response()->json(['message' => 'Space updated successfully']);
    }

    /**
     * @OA\Delete(
     *     path="/api/space/{id}",
     *     tags={"Space"},
     *     summary="Delete reservation",
     *   security={{ "bearerAuth": {} }},
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
        $space = Space::findOrFail($id);
        $space->enable = false;
        $space->save();

        return response()->json(['message' => 'Space deleted successfully']);
    }
}
