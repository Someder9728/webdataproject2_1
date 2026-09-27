<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantApiController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));

        $perPage = min(
            max((int) $request->input('per_page', 20), 1),
            100
        );

        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('t_Fname', 'like', "%{$search}%")
                        ->orWhere('t_Lname', 'like', "%{$search}%")
                        ->orWhere('t_tel', 'like', "%{$search}%")
                        ->orWhere('t_mail', 'like', "%{$search}%");
                });
            })
            ->orderBy('t_id', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $tenants->items(),
            'pagination' => [
                'current_page' => $tenants->currentPage(),
                'last_page' => $tenants->lastPage(),
                'per_page' => $tenants->perPage(),
                'total' => $tenants->total(),
            ],
        ]);
    }

    public function show(Tenant $tenant)
    {
        return response()->json([
            'success' => true,
            'data' => $tenant,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            't_Fname' => ['required', 'string', 'max:255'],
            't_Lname' => ['required', 'string', 'max:255'],
            't_tel' => ['required', 'digits:10'],
            't_mail' => ['nullable', 'email', 'max:255'],
            't_address' => ['nullable', 'string'],
        ]);

        $tenant = Tenant::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'เพิ่มข้อมูลผู้เช่าเรียบร้อยแล้ว',
            'data' => $tenant,
        ], 201);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            't_Fname' => ['required', 'string', 'max:255'],
            't_Lname' => ['required', 'string', 'max:255'],
            't_tel' => ['required', 'digits:10'],
            't_mail' => ['nullable', 'email', 'max:255'],
            't_address' => ['nullable', 'string'],
        ]);

        $tenant->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'แก้ไขข้อมูลผู้เช่าเรียบร้อยแล้ว',
            'data' => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบข้อมูลผู้เช่าเรียบร้อยแล้ว',
        ]);
    }
}