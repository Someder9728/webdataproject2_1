<?php

namespace App\Http\Controllers;

use App\Actions\DeleteUnusedRecord;
use App\Actions\Tenants\CreateTenant;
use App\Actions\Tenants\UpdateTenant;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $tenants = Tenant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('t_Fname', 'like', '%'.$search.'%');
                    $filter->orWhere('t_Lname', 'like', '%'.$search.'%');
                    $filter->orWhere('t_tel', 'like', '%'.$search.'%');
                    $filter->orWhere('t_mail', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('t_id')
            ->paginate(20)->withQueryString();

        return view('tenants.index', compact('tenants', 'search'));
    }

    public function create(): View
    {
        return view('tenants.create');
    }

    public function edit(Tenant $tenant): View
    {
        return view('tenants.edit', compact('tenant'));
    }

    public function store(Request $request, CreateTenant $action): RedirectResponse
    {
        $action->handle($request->user(), $request->only(['t_Fname', 't_Lname', 't_tel', 't_mail', 't_address']));

        return redirect()->route('tenants.index')->with('success', 'บันทึกข้อมูลสำเร็จ');
    }

    public function update(Request $request, Tenant $tenant, UpdateTenant $action): RedirectResponse
    {
        $action->handle($request->user(), $tenant, $request->only(['t_Fname', 't_Lname', 't_tel', 't_mail', 't_address']));

        return redirect()->route('tenants.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(Request $request, Tenant $tenant, DeleteUnusedRecord $action): RedirectResponse
    {
        $action->handle($request->user(), $tenant);

        return redirect()->route('tenants.index')->with('success', 'ลบข้อมูลสำเร็จ');
    }
}
