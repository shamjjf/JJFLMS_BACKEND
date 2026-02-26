<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = LeaveType::where('is_active', true)->get();
        return response()->json([
            'success'    => true,
            'leaveTypes' => $types->map->toFrontendArray()->values(),
        ]);
    }

    /**
     * POST /api/leave-types — Add new leave type (Admin only)
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'code'         => 'required|string|max:10|unique:leave_types,code',
            'name'         => 'required|string|max:100',
            'color'        => 'sometimes|string|max:10',
            'annualLimit'  => 'required|integer|min:1',
            'carryForward' => 'sometimes|integer|min:0',
        ]);

        $leaveType = LeaveType::create([
            'code'          => strtoupper($validated['code']),
            'name'          => $validated['name'],
            'color'         => $validated['color'] ?? '#6366f1',
            'annual_limit'  => $validated['annualLimit'],
            'carry_forward' => $validated['carryForward'] ?? 0,
            'is_active'     => true,
        ]);

        // Auto-create balances for all existing employees
        $employees = User::all();
        foreach ($employees as $emp) {
            LeaveBalance::create([
                'employee_id'   => $emp->id,
                'leave_type_id' => $leaveType->id,
                'balance'       => $leaveType->annual_limit,
                'year'          => date('Y'),
            ]);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Leave type added successfully.',
            'leaveType' => $leaveType->toFrontendArray(),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'color'         => 'sometimes|string|max:10',
            'annual_limit'  => 'sometimes|integer|min:0',
            'carry_forward' => 'sometimes|integer|min:0',
            'is_active'     => 'sometimes|boolean',
        ]);

        $leaveType = LeaveType::findOrFail($id);
        $leaveType->update($validated);

        return response()->json([
            'success'   => true,
            'leaveType' => $leaveType->toFrontendArray(),
        ]);
    }
}
