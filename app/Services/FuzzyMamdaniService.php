<?php

namespace App\Services;

/**
 * Fuzzy Mamdani Service untuk Monitoring Kualitas Air Tambak Udang Vaname
 * 
 * Input: pH, Turbidity (NTU), TDS (PPM)
 * Output: Water Quality Score (0-100), Aerator Decision
 * Membership Functions: Trapezoidal (3 MF per input)
 * Rules: 27 Rule Base
 */
class FuzzyMamdaniService
{
    // Konstanta untuk konversi TDS ke Salinity
    const K_FACTOR = 0.57; // Faktor konversi untuk air payau tambak
    const TEMP_COEFFICIENT = 0.020; // Temperature coefficient (°C⁻¹)
    const REFERENCE_TEMP = 25; // Reference temperature (°C)

    /**
     * Calculate Salinity (PPT) from TDS (PPM)
     * Formula Simplified: Salinity (PPT) = TDS (PPM) / (K × 1000)
     */
    public function calculateSalinity($tdsValue, $temperature = 25)
    {
        // Simplified formula untuk monitoring tambak
        $salinity_ppt = $tdsValue / (self::K_FACTOR * 1000);
        
        return round($salinity_ppt, 2);
    }

    /**
     * Membership Function untuk pH (3 trapezoids)
     * Low: [0, 0, 6.5, 7.2] - Asam berbahaya
     * Normal: [7.0, 7.5, 8.0, 8.5] - Optimal untuk udang
     * High: [8.2, 9.0, 14, 14] - Basa berbahaya
     */
    private function phMembership($value)
    {
        return [
            'low' => $this->trapezoid($value, 0, 0, 6.5, 7.2),
            'normal' => $this->trapezoid($value, 7.0, 7.5, 8.0, 8.5),
            'high' => $this->trapezoid($value, 8.2, 9.0, 14, 14),
        ];
    }

    /**
     * Membership Function untuk Turbidity/NTU (3 trapezoids)
     * Low: [0, 0, 20, 35] - Terlalu jernih
     * Medium: [25, 35, 45, 60] - Optimal (ada plankton)
     * High: [50, 70, 150, 150] - Keruh berbahaya
     */
    private function turbidityMembership($value)
    {
        return [
            'low' => $this->trapezoid($value, 0, 0, 20, 35),
            'medium' => $this->trapezoid($value, 25, 35, 45, 60),
            'high' => $this->trapezoid($value, 50, 70, 150, 150),
        ];
    }

    /**
     * Membership Function untuk TDS/PPM (3 trapezoids)
     * Low: [0, 0, 500, 1500] - Terlalu tawar
     * Medium: [1000, 2000, 5000, 8000] - Payau optimal
     * High: [6000, 10000, 50000, 50000] - Terlalu asin
     */
    private function tdsMembership($value)
    {
        return [
            'low' => $this->trapezoid($value, 0, 0, 500, 1500),
            'medium' => $this->trapezoid($value, 1000, 2000, 5000, 8000),
            'high' => $this->trapezoid($value, 6000, 10000, 50000, 50000),
        ];
    }

    /**
     * Trapezoidal Membership Function
     * Returns degree of membership [0, 1]
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
     * 27 Fuzzy Rules untuk Tambak Udang Vaname
     * Format: [pH, Turbidity, TDS] → [Score, Aerator, Category]
     */
    private function getFuzzyRules()
    {
        return [
            // Rule 1-9: pH Low
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'low', 'score' => 35, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH rendah, air terlalu tawar & jernih'],
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 40, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH rendah mengganggu meski TDS ok'],
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'high', 'score' => 25, 'aerator' => 'on', 'category' => 'Critical', 'reason' => 'pH rendah + salinitas tinggi berbahaya'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 38, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH rendah, meski turbidity ok'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 45, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH rendah, parameter lain ok'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 35, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH rendah + TDS tinggi'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'low', 'score' => 20, 'aerator' => 'on', 'category' => 'Critical', 'reason' => 'pH rendah + air keruh'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 30, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH rendah + air keruh'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'high', 'score' => 15, 'aerator' => 'on', 'category' => 'Critical', 'reason' => 'Semua parameter buruk - BAHAYA!'],
            
            // Rule 10-18: pH Normal
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'low', 'score' => 55, 'aerator' => 'off', 'category' => 'Fair', 'reason' => 'pH ok, air terlalu tawar & jernih'],
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 75, 'aerator' => 'off', 'category' => 'Good', 'reason' => 'pH ok, TDS ok, kurang plankton'],
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'high', 'score' => 60, 'aerator' => 'off', 'category' => 'Fair', 'reason' => 'pH ok, TDS tinggi, air jernih'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 72, 'aerator' => 'off', 'category' => 'Good', 'reason' => 'pH & turbidity ok, TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 95, 'aerator' => 'off', 'category' => 'Excellent', 'reason' => 'KONDISI OPTIMAL! Semua parameter ideal'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 78, 'aerator' => 'off', 'category' => 'Good', 'reason' => 'pH & turbidity ok, TDS agak tinggi'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'low', 'score' => 50, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH ok, air keruh, TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 58, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH & TDS ok, air terlalu keruh'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'high', 'score' => 42, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'TDS tinggi + air keruh'],
            
            // Rule 19-27: pH High
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'low', 'score' => 40, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH tinggi, air tawar & jernih'],
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 52, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH tinggi, TDS ok'],
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'high', 'score' => 38, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH tinggi + TDS tinggi'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 48, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH tinggi, turbidity ok'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 68, 'aerator' => 'on', 'category' => 'Good', 'reason' => 'pH agak tinggi, parameter lain ok'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 55, 'aerator' => 'on', 'category' => 'Fair', 'reason' => 'pH tinggi + TDS tinggi'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'low', 'score' => 32, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH tinggi + air keruh'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 40, 'aerator' => 'on', 'category' => 'Poor', 'reason' => 'pH tinggi + air keruh'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'high', 'score' => 18, 'aerator' => 'on', 'category' => 'Critical', 'reason' => 'Semua parameter buruk - KRITIS!'],
        ];
    }

    /**
     * Main Evaluation Function - Fuzzy Mamdani with 27 Rules
     */
    public function evaluateWaterQuality($phValue, $tdsValue, $turbidityValue, $temperature = 25)
    {
        // Calculate Salinity dari TDS
        $salinityPpt = $this->calculateSalinity($tdsValue, $temperature);

        // Hitung membership degree untuk setiap input
        $phMem = $this->phMembership($phValue);
        $turbidityMem = $this->turbidityMembership($turbidityValue);
        $tdsMem = $this->tdsMembership($tdsValue);

        // Evaluasi semua 27 rules
        $rules = $this->getFuzzyRules();
        $evaluatedRules = [];

        foreach ($rules as $rule) {
            // Hitung rule strength dengan operator MIN (AND)
            $strength = min(
                $phMem[$rule['ph']],
                $turbidityMem[$rule['turbidity']],
                $tdsMem[$rule['tds']]
            );

            if ($strength > 0) {
                $evaluatedRules[] = [
                    'strength' => $strength,
                    'score' => $rule['score'],
                    'aerator' => $rule['aerator'],
                    'category' => $rule['category'],
                    'reason' => $rule['reason'],
                    'rule' => sprintf('IF pH=%s AND Turbidity=%s AND TDS=%s', 
                        $rule['ph'], $rule['turbidity'], $rule['tds'])
                ];
            }
        }

        // Jika tidak ada rule yang aktif
        if (empty($evaluatedRules)) {
            return [
                'water_quality_status' => 'Unknown',
                'water_quality_score' => 0,
                'salinity_ppt' => $salinityPpt,
                'aerator_status' => 'on',
                'recommendation' => 'Data sensor di luar rentang normal. Periksa kalibrasi sensor.',
                'fuzzy_details' => 'Tidak ada rule yang terpenuhi.',
            ];
        }

        // Defuzzifikasi: Weighted Average (Center of Gravity)
        $totalStrength = array_sum(array_column($evaluatedRules, 'strength'));
        $weightedScore = 0;
        
        foreach ($evaluatedRules as $rule) {
            $weightedScore += ($rule['strength'] * $rule['score']);
        }
        
        $finalScore = round($weightedScore / $totalStrength, 2);

        // Ambil rule dengan strength tertinggi untuk rekomendasi
        usort($evaluatedRules, function($a, $b) {
            return $b['strength'] <=> $a['strength'];
        });
        $dominantRule = $evaluatedRules[0];

        // Generate detailed fuzzy info
        $fuzzyDetails = sprintf(
            "pH: %.2f (Low: %.2f, Normal: %.2f, High: %.2f) | " .
            "Turbidity: %.2f NTU (Low: %.2f, Medium: %.2f, High: %.2f) | " .
            "TDS: %.2f PPM (Low: %.2f, Medium: %.2f, High: %.2f) | " .
            "Salinity: %.2f PPT | " .
            "Active Rules: %d | Dominant Rule Strength: %.2f",
            $phValue, $phMem['low'], $phMem['normal'], $phMem['high'],
            $turbidityValue, $turbidityMem['low'], $turbidityMem['medium'], $turbidityMem['high'],
            $tdsValue, $tdsMem['low'], $tdsMem['medium'], $tdsMem['high'],
            $salinityPpt,
            count($evaluatedRules),
            $dominantRule['strength']
        );

        // Generate recommendation berdasarkan dominant rule
        $recommendation = sprintf(
            "%s. Score: %.2f/100. %s",
            $dominantRule['reason'],
            $finalScore,
            $dominantRule['aerator'] === 'on' 
                ? 'Aerator AKTIF untuk meningkatkan kualitas air.' 
                : 'Aerator NONAKTIF. Kondisi stabil, lakukan monitoring rutin.'
        );

        return [
            'water_quality_status' => $dominantRule['category'],
            'water_quality_score' => $finalScore,
            'salinity_ppt' => $salinityPpt,
            'aerator_status' => $dominantRule['aerator'],
            'recommendation' => $recommendation,
            'fuzzy_details' => $fuzzyDetails,
            'rule_strength' => $dominantRule['strength'],
            'active_rules_count' => count($evaluatedRules),
        ];
    }
}
