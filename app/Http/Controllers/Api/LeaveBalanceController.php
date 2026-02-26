<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveBalanceController extends Controller
{
    /**
     * GET /api/balances
     * Returns balances in the frontend's expected format:
     * { "1": { "CL": 10, "SL": 9, ... }, "2": { ... } }
     *
     * Employees see only their own. HR/Admin see all.
     * Supports ?department= and ?search= filters.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->query('year', date('Y'));

        $query = LeaveBalance::with('leaveType')
            ->forYear($year);

        if ($user->role === 'employee') {
            $query->forEmployee($user->id);
        } elseif ($dept = $request->query('department')) {
            $empIds = User::where('department', $dept)->pluck('id');
            $query->whereIn('employee_id', $empIds);
        }

        $balances = $query->get();

        // Transform to frontend format: { empId: { leaveTypeCode: balance, ... } }
        $result = [];
        foreach ($balances as $bal) {
            $empId = $bal->employee_id;
            $code  = $bal->leaveType->code;
            if (! isset($result[$empId])) {
                $result[$empId] = [];
            }
            $result[$empId][$code] = $bal->balance;
        }

        return response()->json([
            'success'  => true,
            'balances' => $result,
        ]);
    }
}
