<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Meters\CalculateMeterUsage;
use App\Actions\Meters\RecordMeterReading;
use App\Http\Controllers\Controller;
use App\Models\Meter;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeterController extends Controller
{
    public function usage(Request $request, Room $room, CalculateMeterUsage $action): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate([
            'start_meter_id' => ['required', 'integer'],
            'end_meter_id' => ['required', 'integer'],
        ]);
        $start = $room->meters()->whereKey($data['start_meter_id'])->firstOrFail();
        $end = $room->meters()->whereKey($data['end_meter_id'])->firstOrFail();

        return response()->json(['data' => [
            'start_meter_id' => $start->getKey(), 'end_meter_id' => $end->getKey(),
            ...$action->handle($start, $end),
        ]]);
    }

    public function index(Request $request, Room $room): JsonResponse
    {
        $this->authorizeAdmin($request);

        $input = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $meters = Meter::query()
            ->where('rooms_r_id', $room->getKey())
            ->orderByDesc('m_date')
            ->orderByDesc('m_id')
            ->paginate((int) ($input['per_page'] ?? 20));

        return response()->json([
            'data' => $meters->getCollection()
                ->map(fn (Meter $meter) => $this->present($meter))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $meters->currentPage(),
                'last_page' => $meters->lastPage(),
                'per_page' => $meters->perPage(),
                'total' => $meters->total(),
            ],
        ]);
    }

    public function store(
        Request $request,
        Room $room,
        RecordMeterReading $action
    ): JsonResponse {
        $this->authorizeAdmin($request);

        $meter = $action->handle(
            $request->user(),
            $room,
            $request->only(['m_date', 'm_water', 'm_elec'])
        );

        return response()->json([
            'data' => $this->present($meter),
            'message' => 'บันทึกมิเตอร์สำเร็จ',
        ], 201);
    }

    private function authorizeAdmin(Request $request): void
    {
        $actor = $request->user()?->fresh();

        abort_unless(
            $actor &&
            $actor->is_active &&
            ! $actor->must_change_password &&
            $actor->u_role === 'admin',
            403
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Meter $meter): array
    {
        return [
            'm_id' => $meter->getKey(),
            'rooms_r_id' => $meter->rooms_r_id,
            'm_date' => $meter->m_date->toDateString(),
            'm_water' => $meter->m_water,
            'm_elec' => $meter->m_elec,
        ];
    }
}
