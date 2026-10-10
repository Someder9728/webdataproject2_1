<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Repairs\CreateRepair;
use App\Actions\Repairs\UpdateRepairStatus;
use App\Http\Controllers\Controller;
use App\Models\Repair;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class RepairController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Repair::class);
        $data = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
            'rp_status' => ['sometimes', Rule::in(['REPORTED', 'IN_PROGRESS', 'COMPLETED'])],
        ]);
        $repairs = Repair::visibleTo($request->user())
            ->with('room:r_id,r_name')
            ->when(isset($data['rp_status']), fn ($q) => $q->where('rp_status', $data['rp_status']))
            ->orderByDesc('rp_id')->paginate($data['per_page'] ?? 20);

        return response()->json([
            'data' => $repairs->items(),
            'meta' => ['current_page' => $repairs->currentPage(), 'last_page' => $repairs->lastPage(), 'per_page' => $repairs->perPage(), 'total' => $repairs->total()],
        ]);
    }

    public function show(Repair $repair): JsonResponse
    {
        Gate::authorize('view', $repair);

        return response()->json(['data' => $repair->load(['histories' => fn ($q) => $q->orderBy('rph_id')])]);
    }

    public function store(Request $request, CreateRepair $action): JsonResponse
    {
        $input = $request->only(['rp_name', 'rp_description', 'rp_type', 'rooms_r_id']);
        if ($request->user()->u_role === 'admin') {
            $input['tenants_t_id'] = $request->input('tenants_t_id');
        }

        return response()->json(['data' => $action->handle($request->user(), $input)], 201);
    }

    public function update(Request $request, Repair $repair, UpdateRepairStatus $action): JsonResponse
    {
        return response()->json(['data' => $action->handle($request->user(), $repair, $request->only(['rp_status', 'expected_status']))]);
    }
}
