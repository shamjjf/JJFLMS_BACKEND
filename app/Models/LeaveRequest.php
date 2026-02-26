<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'applied_on',
        'approved_by',
        'comments',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'applied_on' => 'date:Y-m-d',
        'days'       => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function toFrontendArray(): array
    {
        return [
            'id'         => $this->id,
            'empId'      => $this->employee_id,
            'leaveType'  => $this->leaveType->code ?? '',
            'startDate'  => $this->start_date->format('Y-m-d'),
            'endDate'    => $this->end_date->format('Y-m-d'),
            'days'       => $this->days,
            'reason'     => $this->reason,
            'status'     => $this->status,
            'appliedOn'  => $this->applied_on->format('Y-m-d'),
            'approvedBy' => $this->approved_by,
            'comments'   => $this->comments ?? '',
        ];
    }
}
