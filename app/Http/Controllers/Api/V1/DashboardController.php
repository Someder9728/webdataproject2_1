<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\DashboardSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request, DashboardSummary $summary): JsonResponse
    {
        $actor = $request->user();
        abort_unless($actor->is_active && ! $actor->must_change_password && $actor->u_role === 'admin', 403);
        $validated = $request->validate(['month' => ['sometimes', 'date_format:Y-m']]);

        return response()->json(['data' => $summary->read($validated['month'] ?? now('Asia/Bangkok')->format('Y-m')), 'message' => 'อ่านภาพรวมสำเร็จ']);
    }
}
