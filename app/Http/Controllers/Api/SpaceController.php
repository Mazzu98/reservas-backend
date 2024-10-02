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
     * get a listing of the resource.
     */
    public function get()
    {
        $spaces = Space::all();
        return response()->json($spaces);
    }

    /**
     * Get schedule availability of the resource.
     */
    public function getDailyAvailableTimeSlots(int $id, Request $request)
    {
        $dayStart = Carbon::parse($request->query('day'))->startOfDay();
        $dayEnd = Carbon::parse($request->query('day'))->endOfDay();
        $spaces = new Collection(Space::getAvailableTimeSlots($id, $dayStart, $dayEnd));
        $spaces = $spaces->map(function ($space) {
            return [
                'start' => $space['start']->format('H:i'),
                'end' => $space['end']->format('H:i')
            ];
        });
        return response()->json($spaces);
    }

    /**
     * get weekly available time slots.
     */
    public function getWeeklyAvailableTimeSlots(int $id, Request $request)
    {
        $weekFromThis = (int) $request->query('weekFromThis');
        $dayStart = Carbon::now()->addWeeks($weekFromThis)->startOfWeek();
        $dayEnd = Carbon::now()->addWeeks($weekFromThis)->endOfWeek();
        $spaces = new Collection(Space::getAvailableTimeSlots($id, $dayStart, $dayEnd));
        $spaces = $spaces
            ->groupBy(function ($space) {
                return $space['start']->format('Y-m-d');
            })
            ->map(function ($spaces) {
                foreach ($spaces as $space) {
                    $formattedSpaces[] = [
                        'start' => $space['start']->format('H:i'),
                        'end' => $space['end']->format('H:i')
                    ];
                }
                return $formattedSpaces;
            });
        return response()->json($spaces);
    }

    /**
     * Store a newly created resource in storage.
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
     * Get the specified resource.
     */
    public function getById(string $id)
    {
        $space = Space::findOrFail($id);
        return response()->json($space);
    }

    /**
     * Query the specified resource with available time slots.
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

        $allSpaces = $spaces->get();
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
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        $space = Space::findOrFail($id);
        Storage::delete('public/' . $space->image);
        $space->delete();

        return response()->json(['message' => 'Space deleted successfully']);
    }
}
