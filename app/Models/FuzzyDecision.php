<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuzzyDecision extends Model
{
    protected $fillable = [
        'sensor_reading_id',
        'water_quality_status',
        'recommendation',
        'fuzzy_details',
    ];

    public function sensorReading()
    {
        return $this->belongsTo(SensorReading::class);
    }
}
