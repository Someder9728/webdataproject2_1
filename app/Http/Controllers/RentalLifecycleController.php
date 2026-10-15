<?php

namespace App\Http\Controllers;

use App\Actions\Rentals\ActivateRental;
use App\Actions\Rentals\CancelScheduledRental;
use App\Actions\Rentals\CreateRental;
use App\Actions\Rentals\ScheduleMoveOut;
use App\Models\Rental;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RentalLifecycleController extends Controller
{
    public function store(Request $request, CreateRental $action): RedirectResponse
    {
        $rental = $action->handle($request->user(), $request->only(['rt_movein', 'tenants_t_id', 'rooms_r_id', 'c_end', 'c_rent', 'c_deposit']));

        return redirect()->route('rentals.show', $rental)->with('success', 'บันทึกการเช่าสำเร็จ');
    }

    public function activate(Request $request, Rental $rental, ActivateRental $action): RedirectResponse
    {
        $action->handle($request->user(), $rental);

        return back()->with('success', 'ยืนยันเข้าอยู่จริงแล้ว');
    }

    public function schedule(Request $request, Rental $rental, ScheduleMoveOut $action): RedirectResponse
    {
        $action->handle($request->user(), $rental, $request->only(['scheduled_moveout_at', 'reason']));

        return back()->with('success', 'บันทึกกำหนดย้ายออกแล้ว');
    }

    public function cancel(Request $request, Rental $rental, CancelScheduledRental $action): RedirectResponse
    {
        $action->handle($request->user(), $rental, $request->only('reason'));

        return back()->with('success', 'ยกเลิกการจองแล้ว');
    }
}
