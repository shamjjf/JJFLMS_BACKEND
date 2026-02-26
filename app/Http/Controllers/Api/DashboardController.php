<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Holiday;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Returns dashboard statistics based on user role.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $today = Carbon::today()->toDateString();

        if ($user->role === 'employee') {
            return $this->employeeDashboard($user, $today);
        }

        return $this->adminDashboard($user, $today);
    }

    private function employeeDashboard(User $user, string $today): JsonResponse
    {
        $myLeaves   = LeaveRequest::forEmployee($user->id)->get();
        $pending    = $myLeaves->where('status', 'pending')->count();
        $approved   = $myLeaves->where('status', 'approved')->count();

        // Get CL balance
        $clType = LeaveType::where('code', 'CL')->first();
        $clBalance = 0;
        if ($clType) {
            $bal = LeaveBalance::where('employee_id', $user->id)
                ->where('leave_type_id', $clType->id)
                ->forYear(date('Y'))
                ->first();
            $clBalance = $bal ? $bal->balance : 0;
        }

        $upcomingHolidays = Holiday::where('date', '>=', $today)
            ->orderBy('date')
            ->limit(3)
            ->get()
            ->map->toFrontendArray();

        $recentLeaves = LeaveRequest::with('leaveType')
            ->forEmployee($user->id)
            ->orderByDesc('applied_on')
            ->limit(5)
            ->get()
            ->map->toFrontendArray();

        return response()->json([
            'success' => true,
            'stats' => [
                'totalApplied' => $myLeaves->count(),
                'pending'      => $pending,
                'approved'     => $approved,
                'clBalance'    => $clBalance,
            ],
            'recentLeaves'    => $recentLeaves,
            'upcomingHolidays' => $upcomingHolidays,
        ]);
    }

    private function adminDashboard(User $user, string $today): JsonResponse
    {
        $totalRequests  = LeaveRequest::count();
        $pendingCount   = LeaveRequest::pending()->count();
        $totalEmployees = User::count();

        $onLeaveToday = LeaveRequest::approved()
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->count();

        $upcomingHolidays = Holiday::where('date', '>=', $today)
            ->orderBy('date')
            ->limit(3)
            ->get()
            ->map->toFrontendArray();

        $recentLeaves = LeaveRequest::with('leaveType')
            ->orderByDesc('applied_on')
            ->limit(5)
            ->get()
            ->map->toFrontendArray();

        return response()->json([
            'success' => true,
            'stats' => [
                'totalRequests'  => $totalRequests,
                'pendingCount'   => $pendingCount,
                'onLeaveToday'   => $onLeaveToday,
                'totalEmployees' => $totalEmployees,
            ],
            'recentLeaves'     => $recentLeaves,
            'upcomingHolidays' => $upcomingHolidays,
        ]);
    }
}
