<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    /**
     * GET /api/leaves
     * List leave requests.
     *   - Employees see only their own leaves.
     *   - HR/Admin see all leaves.
     * Supports ?status= filter and ?employee_id= filter (admin/hr only).
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = LeaveRequest::with('leaveType');

        // Employees can only see their own leaves
        if ($user->role === 'employee') {
            $query->forEmployee($user->id);
        } elseif ($empId = $request->query('employee_id')) {
            $query->forEmployee($empId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $leaves = $query->orderByDesc('applied_on')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'leaves'  => $leaves->map->toFrontendArray()->values(),
        ]);
    }

    /**
     * POST /api/leaves
     * Submit a new leave application.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leaveType' => 'required|string|exists:leave_types,code',
            'startDate' => 'required|date|after_or_equal:today',
            'endDate'   => 'required|date|after_or_equal:startDate',
            'reason'    => 'required|string|max:1000',
        ]);

        $user      = $request->user();
        $leaveType = LeaveType::where('code', $validated['leaveType'])->firstOrFail();
        $startDate = Carbon::parse($validated['startDate']);
        $endDate   = Carbon::parse($validated['endDate']);
        $days      = $startDate->diffInDays($endDate) + 1;

        // ── Check balance ──────────────────────────────────────────────
        $balance = LeaveBalance::where('employee_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->forYear(date('Y'))
            ->first();

        $currentBalance = $balance ? $balance->balance : 0;

        if ($days > $currentBalance) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient balance. Available: {$currentBalance} days.",
                'errors'  => ['days' => ["Insufficient balance. Available: {$currentBalance} days."]],
            ], 422);
        }

        // ── Check overlap ──────────────────────────────────────────────
        $overlap = LeaveRequest::where('employee_id', $user->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->where(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                });
            })
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a leave request for this period.',
                'errors'  => ['overlap' => ['You already have a leave request for this period.']],
            ], 422);
        }

        // ── Create request ─────────────────────────────────────────────
        $leave = LeaveRequest::create([
            'employee_id'   => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'days'          => $days,
            'reason'        => $validated['reason'],
            'status'        => 'pending',
            'applied_on'    => now()->toDateString(),
        ]);

        $leave->load('leaveType');

        return response()->json([
            'success' => true,
            'message' => 'Leave application submitted successfully!',
            'leave'   => $leave->toFrontendArray(),
        ], 201);
    }

    /**
     * PUT /api/leaves/{id}/cancel
     * Cancel a pending leave request (by the employee who owns it).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $leave = LeaveRequest::findOrFail($id);

        // Only the owner can cancel
        if ($leave->employee_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled.',
            ], 422);
        }

        $leave->update(['status' => 'cancelled']);
        $leave->load('leaveType');

        return response()->json([
            'success' => true,
            'message' => 'Leave request cancelled.',
            'leave'   => $leave->toFrontendArray(),
        ]);
    }

    /**
     * PUT /api/leaves/{id}/review
     * Approve or reject a leave request (HR/Admin only).
     * Automatically deducts balance on approval.
     */
    public function review(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'action'  => 'required|in:approved,rejected',
            'comment' => 'nullable|string|max:500',
        ]);

        $leave = LeaveRequest::with('leaveType')->findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be reviewed.',
            ], 422);
        }

        $leave->update([
            'status'      => $validated['action'],
            'approved_by' => $user->id,
            'comments'    => $validated['comment'] ?? '',
        ]);

        // Deduct balance on approval
        if ($validated['action'] === 'approved') {
            $balance = LeaveBalance::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->forYear(date('Y'))
                ->first();

            if ($balance) {
                $balance->update([
                    'balance' => max(0, $balance->balance - $leave->days),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Leave request ' . $validated['action'] . '.',
            'leave'   => $leave->toFrontendArray(),
        ]);
    }
}
