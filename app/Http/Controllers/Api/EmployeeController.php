<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query();
        if ($dept = $request->query('department')) {
            $query->where('department', $dept);
        }
        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        return response()->json([
            'success'   => true,
            'employees' => $query->orderBy('name')->get()->map->toFrontendArray()->values(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'success'  => true,
            'employee' => User::findOrFail($id)->toFrontendArray(),
        ]);
    }

    /** POST /api/employees — Add new employee (Admin/HR) */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $v = $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'sometimes|string|min:6',
            'department' => 'required|string|max:50',
            'role'       => 'required|in:admin,hr,employee',
            'managerId'  => 'nullable|exists:users,id',
        ]);

        $nameParts = explode(' ', $v['name']);
        $avatar = strtoupper(substr($nameParts[0], 0, 1));
        if (count($nameParts) > 1) $avatar .= strtoupper(substr(end($nameParts), 0, 1));

        $user = User::create([
            'name'       => $v['name'],
            'email'      => $v['email'],
            'password'   => Hash::make($v['password'] ?? 'password'),
            'department' => $v['department'],
            'role'       => $v['role'],
            'avatar'     => $avatar,
            'manager_id' => $v['managerId'] ?? null,
        ]);

        foreach (LeaveType::where('is_active', true)->get() as $lt) {
            LeaveBalance::create([
                'employee_id'   => $user->id,
                'leave_type_id' => $lt->id,
                'balance'       => $lt->annual_limit,
                'year'          => date('Y'),
            ]);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Employee added successfully.',
            'employee' => $user->toFrontendArray(),
        ], 201);
    }

    /** PUT /api/employees/{id} — Update employee (Admin/HR) */
    public function update(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $employee = User::findOrFail($id);

        $v = $request->validate([
            'name'       => 'sometimes|string|max:100',
            'email'      => 'sometimes|email|unique:users,email,' . $id,
            'password'   => 'sometimes|string|min:6',
            'department' => 'sometimes|string|max:50',
            'role'       => 'sometimes|in:admin,hr,employee',
            'managerId'  => 'nullable|exists:users,id',
        ]);

        if (isset($v['name'])) {
            $employee->name = $v['name'];
            // Update avatar from name
            $parts = explode(' ', $v['name']);
            $avatar = strtoupper(substr($parts[0], 0, 1));
            if (count($parts) > 1) $avatar .= strtoupper(substr(end($parts), 0, 1));
            $employee->avatar = $avatar;
        }
        if (isset($v['email']))      $employee->email      = $v['email'];
        if (isset($v['department']))  $employee->department  = $v['department'];
        if (isset($v['role']))        $employee->role        = $v['role'];
        if (isset($v['password']))    $employee->password    = Hash::make($v['password']);
        if (array_key_exists('managerId', $v)) {
            $employee->manager_id = $v['managerId'] ?: null;
        }

        $employee->save();

        return response()->json([
            'success'  => true,
            'message'  => 'Employee updated successfully.',
            'employee' => $employee->toFrontendArray(),
        ]);
    }

    /** DELETE /api/employees/{id} — Delete employee (Admin only) */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Only admins can delete employees.'], 403);
        }

        $employee = User::findOrFail($id);

        if ($employee->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 400);
        }

        LeaveBalance::where('employee_id', $id)->delete();
        LeaveRequest::where('employee_id', $id)->delete();
        $employee->tokens()->delete();
        $employee->delete();

        return response()->json(['success' => true, 'message' => 'Employee deleted successfully.']);
    }

    public function departments(): JsonResponse
    {
        return response()->json([
            'success'     => true,
            'departments' => User::distinct()->pluck('department')->sort()->values(),
        ]);
    }
}
