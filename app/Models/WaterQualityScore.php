<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterQualityScore extends Model
{
    protected $fillable = [
        'score',
        'ph_value',
        'tds_value',
        'turbidity',
        'salinity',
        'water_level',
        'ph_min',
        'ph_max',
        'tds_min',
        'tds_max',
        'turbidity_min',
        'turbidity_max',
        'recorded_at'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'ph_value' => 'decimal:2',
        'tds_value' => 'decimal:2',
        'turbidity' => 'decimal:2',
        'salinity' => 'decimal:2',
        'water_level' => 'decimal:2',
        'recorded_at' => 'datetime'
    ];
}
