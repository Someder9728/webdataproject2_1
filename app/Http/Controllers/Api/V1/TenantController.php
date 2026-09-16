<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TenantController extends Controller
{
    private const COLUMNS = [
        't_id',
        't_Fname',
        't_Lname',
        't_tel',
        't_mail',
        't_address',
    ];

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Tenant::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');

        $query = Tenant::query();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                
                $query->whereRaw(
                    "instr(
                        coalesce(t_Fname, '') || ' ' || coalesce(t_Lname, ''),
                        ?
                    ) > 0",
                    [$search]
                )->orWhereRaw(
                    "instr(coalesce(t_tel, ''), ?) > 0",
                    [$search]
                );

                if (ctype_digit($search)) {
                    $query->orWhere('t_id', $search);
                }
            });
        }

        $tenants = $query
            ->orderByDesc('created_at')
            ->orderByDesc('t_id')
            ->paginate(
                (int) ($validated['per_page'] ?? 20),
                self::COLUMNS
            );

        return response()->json([
            'data' => $tenants->getCollection()
                ->map(fn (Tenant $tenant) =>
                    $tenant->only(self::COLUMNS)
                )
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $tenants->currentPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
                'last_page' => $tenants->lastPage(),
            ],
            'message' => 'อ่านรายการผู้เช่าสำเร็จ',
        ]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        Gate::authorize('view', $tenant);

        return response()->json([
            'data' => $tenant->only(self::COLUMNS),
            'message' => 'อ่านข้อมูลผู้เช่าสำเร็จ',
        ]);
    }
}