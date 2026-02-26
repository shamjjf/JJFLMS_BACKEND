<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'color',
        'annual_limit',
        'carry_forward',
        'is_active',
    ];

    protected $casts = [
        'annual_limit'  => 'integer',
        'carry_forward' => 'integer',
        'is_active'     => 'boolean',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function toFrontendArray(): array
    {
        return [
            'id'           => $this->code,
            'name'         => $this->name,
            'color'        => $this->color,
            'annual'       => $this->annual_limit,
            'carryForward' => $this->carry_forward,
        ];
    }
}
