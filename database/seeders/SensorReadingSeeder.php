<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SensorReading;
use Carbon\Carbon;

class SensorReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat 10 data sensor dengan timestamp berbeda dalam 1 hari terakhir
        $now = Carbon::now();
        
        $sensorData = [
            ['ph' => 7.2, 'level' => 45.5, 'tds' => 350.0, 'salinity' => 0.5, 'turbidity' => 12.3],
            ['ph' => 6.8, 'level' => 52.3, 'tds' => 420.0, 'salinity' => 0.6, 'turbidity' => 15.7],
            ['ph' => 7.5, 'level' => 48.0, 'tds' => 380.0, 'salinity' => 0.55, 'turbidity' => 10.2],
            ['ph' => 7.0, 'level' => 50.2, 'tds' => 400.0, 'salinity' => 0.58, 'turbidity' => 13.5],
            ['ph' => 6.9, 'level' => 47.8, 'tds' => 365.0, 'salinity' => 0.52, 'turbidity' => 11.8],
            ['ph' => 7.3, 'level' => 49.5, 'tds' => 395.0, 'salinity' => 0.57, 'turbidity' => 14.2],
            ['ph' => 7.1, 'level' => 46.3, 'tds' => 375.0, 'salinity' => 0.54, 'turbidity' => 12.9],
            ['ph' => 6.7, 'level' => 51.0, 'tds' => 410.0, 'salinity' => 0.59, 'turbidity' => 16.1],
            ['ph' => 7.4, 'level' => 48.7, 'tds' => 390.0, 'salinity' => 0.56, 'turbidity' => 11.5],
            ['ph' => 7.2, 'level' => 49.0, 'tds' => 385.0, 'salinity' => 0.55, 'turbidity' => 13.0],
        ];

        foreach ($sensorData as $index => $data) {
            // Buat data dengan interval 2-3 jam mundur dari sekarang
            $timestamp = $now->copy()->subHours(($index * 2.5));
            
            SensorReading::create([
                'ph_value' => $data['ph'],
                'water_level' => $data['level'],
                'tds_value' => $data['tds'],
                'salinity' => $data['salinity'],
                'turbidity' => $data['turbidity'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }
    }
}
