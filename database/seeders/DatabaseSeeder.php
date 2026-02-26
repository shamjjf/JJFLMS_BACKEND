<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\Holiday;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Leave Types (matching LEAVE_TYPES in mockData.js) ─────────
        $leaveTypes = [
            ['code' => 'CL', 'name' => 'Casual Leave',    'color' => '#6366f1', 'annual_limit' => 12,  'carry_forward' => 0],
            ['code' => 'SL', 'name' => 'Sick Leave',      'color' => '#f59e0b', 'annual_limit' => 10,  'carry_forward' => 5],
            ['code' => 'EL', 'name' => 'Earned Leave',    'color' => '#10b981', 'annual_limit' => 15,  'carry_forward' => 10],
            ['code' => 'ML', 'name' => 'Maternity Leave', 'color' => '#ec4899', 'annual_limit' => 180, 'carry_forward' => 0],
            ['code' => 'PL', 'name' => 'Paternity Leave', 'color' => '#8b5cf6', 'annual_limit' => 15,  'carry_forward' => 0],
        ];

        $ltModels = [];
        foreach ($leaveTypes as $lt) {
            $ltModels[$lt['code']] = LeaveType::create($lt);
        }

        // ─── Employees (matching EMPLOYEES_DATA) ──────────────────────
        // Note: All passwords are "password" for demo purposes
        $defaultPassword = Hash::make('password');

        $employees = [
            ['id' => 1, 'name' => 'Arjun Sharma',  'email' => 'arjun@company.com',  'department' => 'Engineering', 'role' => 'employee', 'avatar' => 'AS', 'manager_id' => 2],
            ['id' => 2, 'name' => 'Priya Mehta',   'email' => 'priya@company.com',  'department' => 'Engineering', 'role' => 'hr',       'avatar' => 'PM', 'manager_id' => null],
            ['id' => 3, 'name' => 'Ravi Kumar',    'email' => 'ravi@company.com',   'department' => 'HR',          'role' => 'employee', 'avatar' => 'RK', 'manager_id' => 2],
            ['id' => 4, 'name' => 'Sneha Patel',   'email' => 'sneha@company.com',  'department' => 'Finance',     'role' => 'employee', 'avatar' => 'SP', 'manager_id' => 2],
            ['id' => 5, 'name' => 'Admin User',    'email' => 'admin@company.com',  'department' => 'Operations',  'role' => 'admin',    'avatar' => 'AU', 'manager_id' => null],
            ['id' => 6, 'name' => 'Deepak Nair',   'email' => 'deepak@company.com', 'department' => 'Marketing',   'role' => 'employee', 'avatar' => 'DN', 'manager_id' => 2],
        ];

        // Create without manager_id first (to avoid FK issues), then update
        foreach ($employees as $emp) {
            $managerId = $emp['manager_id'];
            $emp['manager_id'] = null;
            $emp['password'] = $defaultPassword;
            User::create($emp);
        }
        // Now set manager IDs
        foreach ($employees as $emp) {
            if ($emp['manager_id']) {
                User::where('id', $emp['id'])->update(['manager_id' => $emp['manager_id']]);
            }
        }

        // ─── Holidays (matching HOLIDAYS_DATA) ────────────────────────
        $holidays = [
            ['name' => 'Republic Day',           'date' => '2025-01-26', 'type' => 'public'],
            ['name' => 'Holi',                   'date' => '2025-03-14', 'type' => 'public'],
            ['name' => 'Good Friday',            'date' => '2025-04-18', 'type' => 'public'],
            ['name' => 'Company Foundation Day', 'date' => '2025-04-22', 'type' => 'company'],
            ['name' => 'Independence Day',       'date' => '2025-08-15', 'type' => 'public'],
            ['name' => 'Gandhi Jayanti',         'date' => '2025-10-02', 'type' => 'public'],
            ['name' => 'Diwali',                 'date' => '2025-10-20', 'type' => 'public'],
            ['name' => 'Diwali Holiday',         'date' => '2025-10-21', 'type' => 'company'],
            ['name' => 'Christmas',              'date' => '2025-12-25', 'type' => 'public'],
            ['name' => 'New Year',               'date' => '2025-12-31', 'type' => 'company'],
        ];

        foreach ($holidays as $h) {
            Holiday::create($h);
        }

        // ─── Leave Requests (matching LEAVE_REQUESTS_DATA) ────────────
        $requests = [
            ['employee_id' => 1, 'leave_type_code' => 'CL', 'start_date' => '2025-03-10', 'end_date' => '2025-03-11', 'days' => 2, 'reason' => 'Family function attendance', 'status' => 'approved',  'applied_on' => '2025-03-05', 'approved_by' => 2, 'comments' => 'Approved. Enjoy!'],
            ['employee_id' => 3, 'leave_type_code' => 'SL', 'start_date' => '2025-03-18', 'end_date' => '2025-03-18', 'days' => 1, 'reason' => 'Medical appointment',         'status' => 'approved',  'applied_on' => '2025-03-17', 'approved_by' => 2, 'comments' => 'Approved. Get well soon.'],
            ['employee_id' => 4, 'leave_type_code' => 'EL', 'start_date' => '2025-04-01', 'end_date' => '2025-04-05', 'days' => 5, 'reason' => 'Family vacation',             'status' => 'pending',   'applied_on' => '2025-03-20', 'approved_by' => null, 'comments' => null],
            ['employee_id' => 6, 'leave_type_code' => 'CL', 'start_date' => '2025-03-25', 'end_date' => '2025-03-25', 'days' => 1, 'reason' => 'Personal work',               'status' => 'pending',   'applied_on' => '2025-03-22', 'approved_by' => null, 'comments' => null],
            ['employee_id' => 1, 'leave_type_code' => 'EL', 'start_date' => '2025-05-12', 'end_date' => '2025-05-16', 'days' => 5, 'reason' => 'Annual vacation',             'status' => 'pending',   'applied_on' => '2025-03-28', 'approved_by' => null, 'comments' => null],
            ['employee_id' => 3, 'leave_type_code' => 'CL', 'start_date' => '2025-02-14', 'end_date' => '2025-02-14', 'days' => 1, 'reason' => 'Personal',                    'status' => 'rejected',  'applied_on' => '2025-02-10', 'approved_by' => 2, 'comments' => 'Critical deadline period.'],
        ];

        foreach ($requests as $req) {
            $ltId = $ltModels[$req['leave_type_code']]->id;
            LeaveRequest::create([
                'employee_id'   => $req['employee_id'],
                'leave_type_id' => $ltId,
                'start_date'    => $req['start_date'],
                'end_date'      => $req['end_date'],
                'days'          => $req['days'],
                'reason'        => $req['reason'],
                'status'        => $req['status'],
                'applied_on'    => $req['applied_on'],
                'approved_by'   => $req['approved_by'],
                'comments'      => $req['comments'],
            ]);
        }

        // ─── Leave Balances (matching LEAVE_BALANCES_DATA) ────────────
        $balances = [
            1 => ['CL' => 10, 'SL' => 9,  'EL' => 13, 'ML' => 0,   'PL' => 15],
            2 => ['CL' => 12, 'SL' => 10, 'EL' => 15, 'ML' => 180, 'PL' => 0],
            3 => ['CL' => 11, 'SL' => 9,  'EL' => 15, 'ML' => 0,   'PL' => 15],
            4 => ['CL' => 12, 'SL' => 10, 'EL' => 10, 'ML' => 180, 'PL' => 0],
            5 => ['CL' => 12, 'SL' => 10, 'EL' => 15, 'ML' => 0,   'PL' => 15],
            6 => ['CL' => 11, 'SL' => 10, 'EL' => 15, 'ML' => 0,   'PL' => 15],
        ];

        $year = 2025;
        foreach ($balances as $empId => $types) {
            foreach ($types as $code => $balance) {
                LeaveBalance::create([
                    'employee_id'   => $empId,
                    'leave_type_id' => $ltModels[$code]->id,
                    'balance'       => $balance,
                    'year'          => $year,
                ]);
            }
        }
    }
}
