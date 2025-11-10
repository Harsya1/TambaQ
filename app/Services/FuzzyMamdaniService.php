<?php

namespace App\Services;

class FuzzyMamdaniService
{
    /**
     * Fungsi keanggotaan untuk pH
     */
    private function phMembership($value)
    {
        return [
            'rendah' => $this->trapezoid($value, 0, 0, 6.5, 7.0),
            'normal' => $this->triangle($value, 6.5, 7.5, 8.5),
            'tinggi' => $this->trapezoid($value, 8.0, 8.5, 14, 14),
        ];
    }

    /**
     * Fungsi keanggotaan untuk TDS (Total Dissolved Solids)
     */
    private function tdsMembership($value)
    {
        return [
            'rendah' => $this->trapezoid($value, 0, 0, 300, 350),
            'normal' => $this->triangle($value, 320, 380, 450),
            'tinggi' => $this->trapezoid($value, 420, 500, 1000, 1000),
        ];
    }

    /**
     * Fungsi keanggotaan untuk Turbidity (Kekeruhan)
     */
    private function turbidityMembership($value)
    {
        return [
            'rendah' => $this->trapezoid($value, 0, 0, 8, 12),
            'sedang' => $this->triangle($value, 10, 15, 20),
            'tinggi' => $this->trapezoid($value, 18, 25, 100, 100),
        ];
    }

    /**
     * Fungsi segitiga untuk membership
     */
    private function triangle($x, $a, $b, $c)
    {
        if ($x <= $a || $x >= $c) {
            return 0;
        } elseif ($x == $b) {
            return 1;
        } elseif ($x > $a && $x < $b) {
            return ($x - $a) / ($b - $a);
        } else {
            return ($c - $x) / ($c - $b);
        }
    }

    /**
     * Fungsi trapesium untuk membership
     */
    private function trapezoid($x, $a, $b, $c, $d)
    {
        if ($x <= $a || $x >= $d) {
            return 0;
        } elseif ($x >= $b && $x <= $c) {
            return 1;
        } elseif ($x > $a && $x < $b) {
            return ($x - $a) / ($b - $a);
        } else {
            return ($d - $x) / ($d - $c);
        }
    }

    /**
     * Rule-based fuzzy logic untuk menentukan kualitas air dan kontrol aerator
     * 
     * RULES:
     * 1. Jika pH Rendah DAN TDS Tinggi DAN Turbidity Tinggi → Kualitas Buruk → Aerator ON
     * 2. Jika pH Rendah DAN TDS Normal DAN Turbidity Sedang → Kualitas Sedang → Aerator ON
     * 3. Jika pH Normal DAN TDS Normal DAN Turbidity Rendah → Kualitas Baik → Aerator OFF
     * 4. Jika pH Normal DAN TDS Rendah DAN Turbidity Rendah → Kualitas Sangat Baik → Aerator OFF
     * 5. Jika pH Tinggi → Kualitas Buruk → Aerator ON
     * 6. Jika Turbidity Tinggi → Kualitas Buruk → Aerator ON
     * 7. Jika pH Normal DAN TDS Normal → Kualitas Baik → Aerator OFF
     * 8. Jika TDS Tinggi DAN Turbidity Tinggi → Kualitas Buruk → Aerator ON
     */
    public function evaluateWaterQuality($phValue, $tdsValue, $turbidityValue)
    {
        // Hitung membership degree untuk setiap parameter
        $phMem = $this->phMembership($phValue);
        $tdsMem = $this->tdsMembership($tdsValue);
        $turbidityMem = $this->turbidityMembership($turbidityValue);

        // Evaluasi rules menggunakan operator MIN untuk AND
        $rules = [
            // Rule 1: pH Rendah AND TDS Tinggi AND Turbidity Tinggi → Buruk → Aerator ON
            [
                'strength' => min($phMem['rendah'], $tdsMem['tinggi'], $turbidityMem['tinggi']),
                'output' => 'Buruk',
                'aerator' => 'on',
                'recommendation' => 'Kualitas air buruk. pH rendah, TDS dan kekeruhan tinggi. Aerator AKTIF untuk meningkatkan oksigen.',
            ],
            
            // Rule 2: pH Rendah AND TDS Normal AND Turbidity Sedang → Sedang → Aerator ON
            [
                'strength' => min($phMem['rendah'], $tdsMem['normal'], $turbidityMem['sedang']),
                'output' => 'Sedang',
                'aerator' => 'on',
                'recommendation' => 'Kualitas air sedang. pH rendah perlu perhatian. Aerator AKTIF untuk stabilisasi.',
            ],
            
            // Rule 3: pH Normal AND TDS Normal AND Turbidity Rendah → Baik → Aerator OFF
            [
                'strength' => min($phMem['normal'], $tdsMem['normal'], $turbidityMem['rendah']),
                'output' => 'Baik',
                'aerator' => 'off',
                'recommendation' => 'Kualitas air baik. Semua parameter dalam batas normal. Aerator NONAKTIF untuk efisiensi energi.',
            ],
            
            // Rule 4: pH Normal AND TDS Rendah AND Turbidity Rendah → Sangat Baik → Aerator OFF
            [
                'strength' => min($phMem['normal'], $tdsMem['rendah'], $turbidityMem['rendah']),
                'output' => 'Sangat Baik',
                'aerator' => 'off',
                'recommendation' => 'Kualitas air sangat baik. Kondisi optimal untuk budidaya. Aerator NONAKTIF.',
            ],
            
            // Rule 5: pH Tinggi → Buruk → Aerator ON
            [
                'strength' => $phMem['tinggi'],
                'output' => 'Buruk',
                'aerator' => 'on',
                'recommendation' => 'pH terlalu tinggi, berbahaya untuk udang. Aerator AKTIF untuk membantu stabilisasi.',
            ],
            
            // Rule 6: Turbidity Tinggi → Buruk → Aerator ON
            [
                'strength' => $turbidityMem['tinggi'],
                'output' => 'Buruk',
                'aerator' => 'on',
                'recommendation' => 'Kekeruhan air sangat tinggi. Aerator AKTIF untuk meningkatkan sirkulasi air.',
            ],
            
            // Rule 7: pH Normal AND TDS Normal → Baik → Aerator OFF
            [
                'strength' => min($phMem['normal'], $tdsMem['normal']),
                'output' => 'Baik',
                'aerator' => 'off',
                'recommendation' => 'Parameter utama normal. Aerator NONAKTIF, lakukan monitoring rutin.',
            ],
            
            // Rule 8: TDS Tinggi AND Turbidity Tinggi → Buruk → Aerator ON
            [
                'strength' => min($tdsMem['tinggi'], $turbidityMem['tinggi']),
                'output' => 'Buruk',
                'aerator' => 'on',
                'recommendation' => 'TDS dan kekeruhan tinggi. Pertimbangkan pergantian air. Aerator AKTIF.',
            ],
            
            // Rule 9: pH Rendah AND Turbidity Tinggi → Buruk → Aerator ON
            [
                'strength' => min($phMem['rendah'], $turbidityMem['tinggi']),
                'output' => 'Buruk',
                'aerator' => 'on',
                'recommendation' => 'Kombinasi pH rendah dan kekeruhan tinggi berbahaya. Aerator AKTIF segera.',
            ],
            
            // Rule 10: TDS Rendah AND Turbidity Rendah → Baik → Aerator OFF
            [
                'strength' => min($tdsMem['rendah'], $turbidityMem['rendah']),
                'output' => 'Baik',
                'aerator' => 'off',
                'recommendation' => 'Air jernih dengan TDS rendah. Kondisi baik. Aerator NONAKTIF.',
            ],
        ];

        // Filter rules dengan strength > 0
        $activeRules = array_filter($rules, function($rule) {
            return $rule['strength'] > 0;
        });

        if (empty($activeRules)) {
            return [
                'water_quality_status' => 'Tidak Diketahui',
                'aerator_status' => 'off',
                'recommendation' => 'Data sensor tidak valid atau di luar rentang normal.',
                'fuzzy_details' => 'Tidak ada rule yang terpenuhi.',
            ];
        }

        // Cari rule dengan strength tertinggi (defuzzifikasi dengan metode max)
        usort($activeRules, function($a, $b) {
            return $b['strength'] <=> $a['strength'];
        });

        $dominantRule = $activeRules[0];

        // Format detail fuzzy
        $fuzzyDetails = sprintf(
            "pH: %.2f (Rendah: %.2f, Normal: %.2f, Tinggi: %.2f) | " .
            "TDS: %.2f (Rendah: %.2f, Normal: %.2f, Tinggi: %.2f) | " .
            "Turbidity: %.2f (Rendah: %.2f, Sedang: %.2f, Tinggi: %.2f) | " .
            "Rule Strength: %.2f",
            $phValue, $phMem['rendah'], $phMem['normal'], $phMem['tinggi'],
            $tdsValue, $tdsMem['rendah'], $tdsMem['normal'], $tdsMem['tinggi'],
            $turbidityValue, $turbidityMem['rendah'], $turbidityMem['sedang'], $turbidityMem['tinggi'],
            $dominantRule['strength']
        );

        return [
            'water_quality_status' => $dominantRule['output'],
            'aerator_status' => $dominantRule['aerator'],
            'recommendation' => $dominantRule['recommendation'],
            'fuzzy_details' => $fuzzyDetails,
            'rule_strength' => $dominantRule['strength'],
        ];
    }
}
