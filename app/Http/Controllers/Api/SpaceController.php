<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spaces = Space::all();
        return response()->json($spaces);
    }

    /**
     * Display schedule availability of the resource.
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
            'image' => 'required|string',
            'capacity' => 'required|integer',
        ]);

        Space::create($data);

        return response()->json(['message' => 'Space created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $space = Space::findOrFail($id);
        return response()->json($space);
    }

    /**
     * query the specified resource.
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

        if ($startDate && $endDate) {
            $spaces->whereDoesntHave('reservations', function ($query) use ($startDate, $endDate) {
                $query->where('start_date', '<', $endDate)
                    ->where('end_date', '>', $startDate);
            });
        }

        $spaces = $spaces->get();

        return response()->json($spaces);
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
            'image' => 'required|string',
            'capacity' => 'required|integer',
        ]);

        $space = Space::findOrFail($id);
        $space->update($data);
        return response()->json(['message' => 'Space updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $space = Space::findOrFail($id);
        $space->delete();

        return response()->json(['message' => 'Space deleted successfully']);
    }
}
