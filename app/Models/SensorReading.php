<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    protected $fillable = [
        'ph_value',
        'water_level',
        'tds_value',
        'salinity',
        'turbidity',
    ];

    protected $casts = [
        'ph_value' => 'decimal:2',
        'water_level' => 'decimal:2',
        'tds_value' => 'decimal:2',
        'salinity' => 'decimal:2',
        'turbidity' => 'decimal:2',
    ];

    public function fuzzyDecision()
    {
        return $this->hasOne(FuzzyDecision::class);
    }
}
