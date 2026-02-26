<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'type',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function toFrontendArray(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'date' => $this->date->format('Y-m-d'),
            'type' => $this->type,
        ];
    }
}
