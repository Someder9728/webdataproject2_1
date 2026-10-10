<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Rental;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RentalHistoryController extends Controller
{
    public function index(Request $request, Rental $rental): JsonResponse
    {
        Gate::authorize('viewHistory', $rental);

        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        // Contract may have been soft deleted; its immutable audit still belongs to this rental.
        $contractIds = $rental->contract()->withTrashed()->pluck('c_id');

        $events = AuditEvent::query()
            ->with('actor')
            ->whereIn('entity_type', ['contracts', 'contract'])
            ->whereIn('entity_id', $contractIds)
            ->whereIn('action', ['contract_updated', 'CONTRACT_EXTENDED'])
            ->orderByDesc('created_at')
            ->orderByDesc('ae_id')
            ->paginate(
                (int) ($validated['per_page'] ?? 20),
                ['*'],
                'page',
                (int) ($validated['page'] ?? 1)
            );

        return response()->json([
            'data' => $events->getCollection()->map(fn (AuditEvent $event) => [
                'ae_id' => $event->getKey(),
                'entity_type' => $event->entity_type,
                'entity_id' => $event->entity_id,
                'action' => $event->action,
                'created_at' => $event->created_at->copy()->timezone('Asia/Bangkok')->toIso8601String(),
                'actor' => $event->actor ? [
                    'u_id' => $event->actor->getKey(),
                    'u_username' => $event->actor->u_username,
                ] : null,
                'reason' => $event->reason,
                // Only fields needed for the contract timeline; never return an arbitrary audit payload.
                'old_values' => $this->contractValues($event->old_values),
                'new_values' => $this->contractValues($event->new_values),
            ]),
            'meta' => [
                'current_page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
                'last_page' => $events->lastPage(),
            ],
            'message' => 'อ่านประวัติการแก้ไขสัญญาสำเร็จ',
        ]);
    }

    /** @return array<string, mixed> */
    private function contractValues(mixed $values): array
    {
        return is_array($values)
            ? array_intersect_key($values, array_flip(['c_end', 'c_status']))
            : [];
    }
}
