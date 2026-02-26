<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * GET /api/reports/employee
     * Employee-wise leave report. Supports ?department= filter.
     */
    public function employeeReport(Request $request): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::query();
        if ($dept = $request->query('department')) {
            $query->where('department', $dept);
        }

        $employees  = $query->orderBy('name')->get();
        $leaveTypes = LeaveType::all()->keyBy('id');
        $year       = $request->query('year', date('Y'));

        $report = $employees->map(function ($emp) use ($leaveTypes, $year) {
            $leaves   = LeaveRequest::forEmployee($emp->id)->get();
            $balances = LeaveBalance::forEmployee($emp->id)->forYear($year)->get();

            $balMap = [];
            foreach ($balances as $b) {
                $lt = $leaveTypes->get($b->leave_type_id);
                if ($lt) {
                    $balMap[$lt->code] = $b->balance;
                }
            }

            return [
                'id'        => $emp->id,
                'name'      => $emp->name,
                'avatar'    => $emp->avatar,
                'dept'      => $emp->department,
                'total'     => $leaves->count(),
                'approved'  => $leaves->where('status', 'approved')->count(),
                'pending'   => $leaves->where('status', 'pending')->count(),
                'rejected'  => $leaves->where('status', 'rejected')->count(),
                'totalDays' => $leaves->where('status', 'approved')->sum('days'),
                'balanceCL' => $balMap['CL'] ?? 0,
                'balanceSL' => $balMap['SL'] ?? 0,
                'balanceEL' => $balMap['EL'] ?? 0,
            ];
        });

        return response()->json([
            'success' => true,
            'report'  => $report,
        ]);
    }

    /**
     * GET /api/reports/department
     * Department-wise summary.
     */
    public function departmentReport(Request $request): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $departments = User::distinct()->pluck('department');

        $report = $departments->map(function ($dept) {
            $empIds    = User::where('department', $dept)->pluck('id');
            $leaves    = LeaveRequest::whereIn('employee_id', $empIds)->get();
            $headcount = $empIds->count();

            return [
                'dept'      => $dept,
                'headcount' => $headcount,
                'total'     => $leaves->count(),
                'approved'  => $leaves->where('status', 'approved')->count(),
                'pending'   => $leaves->where('status', 'pending')->count(),
                'totalDays' => $leaves->where('status', 'approved')->sum('days'),
            ];
        });

        return response()->json([
            'success' => true,
            'report'  => $report,
        ]);
    }

    /**
     * GET /api/reports/monthly
     * Monthly leave trend + summary stats.
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $year = $request->query('year', date('Y'));

        $monthlyData = DB::table('leave_requests')
            ->selectRaw('MONTH(applied_on) as month, COUNT(*) as count')
            ->whereYear('applied_on', $year)
            ->groupBy(DB::raw('MONTH(applied_on)'))
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $trend = collect($months)->map(function ($name, $i) use ($monthlyData) {
            $monthNum = $i + 1;
            return [
                'month' => $name,
                'count' => (int) ($monthlyData->get($monthNum)->count ?? 0),
            ];
        });

        $totalLeaves   = LeaveRequest::whereYear('applied_on', $year)->count();
        $totalApproved = LeaveRequest::whereYear('applied_on', $year)->approved()->count();
        $totalDays     = LeaveRequest::whereYear('applied_on', $year)->sum('days');
        $approvalRate  = $totalLeaves > 0 ? round(($totalApproved / $totalLeaves) * 100) : 0;
        $avgDays       = $totalLeaves > 0 ? round($totalDays / $totalLeaves, 1) : 0;

        return response()->json([
            'success' => true,
            'trend'   => $trend,
            'summary' => [
                'totalRequests' => $totalLeaves,
                'approvalRate'  => $approvalRate,
                'avgDays'       => $avgDays,
            ],
        ]);
    }
}
