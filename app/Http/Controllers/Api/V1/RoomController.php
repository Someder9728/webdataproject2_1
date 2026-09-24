<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Rooms\SaveRoom;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    private const COLUMNS = [
        'r_id', 'r_name', 'r_floor', 'r_type', 'r_rent', 'r_status',
    ];

    private const INPUTS = [
        'r_name', 'r_floor', 'r_type', 'r_rent',
    ];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Room::class);

        $input = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'r_floor' => [
                'sometimes', 'integer',
                'between:-2147483648,2147483647',
            ],
            'r_status' => [
                'sometimes', Rule::in(['VACANT', 'OCCUPIED']),
            ],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ]);

        $query = Room::query();
        $search = trim($input['search'] ?? '');

        if ($search !== '') {
            $query->whereRaw('instr(r_name, ?) > 0', [$search]);
        }

        foreach (['r_floor', 'r_status'] as $field) {
            if (array_key_exists($field, $input)) {
                $query->where($field, $input[$field]);
            }
        }

        $rooms = $query
            ->orderBy('r_floor')
            ->orderBy('r_name')
            ->orderBy('r_id')
            ->paginate((int) ($input['per_page'] ?? 20), self::COLUMNS);

        return response()->json([
            'data' => $rooms->getCollection()
                ->map(fn (Room $room) => $room->only(self::COLUMNS))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $rooms->currentPage(),
                'per_page' => $rooms->perPage(),
                'total' => $rooms->total(),
                'last_page' => $rooms->lastPage(),
            ],
            'message' => 'อ่านรายการห้องสำเร็จ',
        ]);
    }

    public function show(Room $room): JsonResponse
    {
        Gate::authorize('view', $room);

        return $this->roomResponse($room, 'อ่านข้อมูลห้องสำเร็จ');
    }

    public function store(Request $request, SaveRoom $action): JsonResponse
    {
        $room = $action->handle(
            $request->user(),
            $request->only(self::INPUTS)
        );

        return $this->roomResponse($room, 'สร้างห้องสำเร็จ', 201);
    }

    public function update(
        Request $request,
        Room $room,
        SaveRoom $action
    ): JsonResponse {
        $room = $action->handle(
            $request->user(),
            $request->only(self::INPUTS),
            $room
        );

        return $this->roomResponse($room, 'แก้ไขข้อมูลห้องสำเร็จ');
    }

    private function roomResponse(
        Room $room,
        string $message,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'data' => $room->only(self::COLUMNS),
            'message' => $message,
        ], $status);
    }

    public function destroy(
        Request $request,
        Room $room,
        \App\Actions\DeleteUnusedRecord $action
    ): JsonResponse {
        $action->handle($request->user(), $room);

        return response()->json([
            'data' => null,
            'message' => 'ลบข้อมูลห้องสำเร็จ',
        ]);
    }
}