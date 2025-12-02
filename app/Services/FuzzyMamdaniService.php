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
     * Disesuaikan dengan research: Batas aman < 30 NTU, Bahaya > 60 NTU
     * Low: [0, 0, 15, 25] - Terlalu jernih (kurang plankton)
     * Medium: [20, 25, 35, 45] - Optimal (plankton seimbang)
     * High: [40, 60, 150, 150] - Keruh berbahaya (risiko 4x kematian)
     */
    private function turbidityMembership($value)
    {
        return [
            'low' => $this->trapezoid($value, 0, 0, 15, 25),
            'medium' => $this->trapezoid($value, 20, 25, 35, 45),
            'high' => $this->trapezoid($value, 40, 60, 150, 150),
        ];
    }

    /**
     * Membership Function untuk TDS/PPM (3 trapezoids)
     * Disesuaikan untuk BUDIDAYA AIR TAWAR (Low Salinity Farming)
     * Low: [0, 0, 200, 350] - Terlalu tawar (defisiensi mineral)
     * Medium: [300, 400, 600, 800] - Optimal air tawar (0.5-1.4 ppt)
     * High: [700, 1000, 3000, 3000] - Terlalu tinggi untuk air tawar
     */
    private function tdsMembership($value)
    {
        return [
            'low' => $this->trapezoid($value, 0, 0, 200, 350),
            'medium' => $this->trapezoid($value, 300, 400, 600, 800),
            'high' => $this->trapezoid($value, 700, 1000, 3000, 3000),
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
     * Format: [pH, Turbidity, TDS] → [Score, Category]
     */
    private function getFuzzyRules()
    {
        return [
            // Rule 1-9: pH Low (Asam berbahaya untuk udang)
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'low', 'score' => 35, 'category' => 'Poor', 'reason' => 'pH rendah, air terlalu tawar & jernih'],
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH rendah mengganggu meski TDS optimal'],
            ['ph' => 'low', 'turbidity' => 'low', 'tds' => 'high', 'score' => 25, 'category' => 'Critical', 'reason' => 'pH rendah + salinitas tinggi berbahaya'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 38, 'category' => 'Poor', 'reason' => 'pH rendah, meski turbidity optimal'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 45, 'category' => 'Fair', 'reason' => 'pH rendah, parameter lain optimal'],
            ['ph' => 'low', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 35, 'category' => 'Poor', 'reason' => 'pH rendah + TDS tinggi'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'low', 'score' => 20, 'category' => 'Critical', 'reason' => 'pH rendah + air keruh'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 30, 'category' => 'Poor', 'reason' => 'pH rendah + air keruh'],
            ['ph' => 'low', 'turbidity' => 'high', 'tds' => 'high', 'score' => 15, 'category' => 'Critical', 'reason' => 'Semua parameter buruk - BAHAYA!'],
            
            // Rule 10-18: pH Normal (Kondisi ideal untuk udang vaname)
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'low', 'score' => 55, 'category' => 'Fair', 'reason' => 'pH optimal, air terlalu tawar & jernih'],
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 75, 'category' => 'Good', 'reason' => 'pH optimal, TDS optimal, kurang plankton'],
            ['ph' => 'normal', 'turbidity' => 'low', 'tds' => 'high', 'score' => 60, 'category' => 'Fair', 'reason' => 'pH optimal, TDS tinggi, air jernih'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 72, 'category' => 'Good', 'reason' => 'pH & turbidity optimal, TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 95, 'category' => 'Excellent', 'reason' => 'KONDISI OPTIMAL! Semua parameter ideal untuk pertumbuhan udang'],
            ['ph' => 'normal', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 78, 'category' => 'Good', 'reason' => 'pH & turbidity optimal, TDS agak tinggi'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'low', 'score' => 50, 'category' => 'Fair', 'reason' => 'pH optimal, air keruh, TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 58, 'category' => 'Fair', 'reason' => 'pH & TDS optimal, air terlalu keruh'],
            ['ph' => 'normal', 'turbidity' => 'high', 'tds' => 'high', 'score' => 42, 'category' => 'Poor', 'reason' => 'TDS tinggi + air keruh'],
            
            // Rule 19-27: pH High (Basa berbahaya untuk udang)
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'low', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH tinggi, air tawar & jernih'],
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'medium', 'score' => 52, 'category' => 'Fair', 'reason' => 'pH tinggi, TDS optimal'],
            ['ph' => 'high', 'turbidity' => 'low', 'tds' => 'high', 'score' => 38, 'category' => 'Poor', 'reason' => 'pH tinggi + TDS tinggi'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'low', 'score' => 48, 'category' => 'Fair', 'reason' => 'pH tinggi, turbidity optimal'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'medium', 'score' => 68, 'category' => 'Good', 'reason' => 'pH agak tinggi, parameter lain optimal'],
            ['ph' => 'high', 'turbidity' => 'medium', 'tds' => 'high', 'score' => 55, 'category' => 'Fair', 'reason' => 'pH tinggi + TDS tinggi'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'low', 'score' => 32, 'category' => 'Poor', 'reason' => 'pH tinggi + air keruh'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'medium', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH tinggi + air keruh'],
            ['ph' => 'high', 'turbidity' => 'high', 'tds' => 'high', 'score' => 18, 'category' => 'Critical', 'reason' => 'Semua parameter buruk - KRITIS!'],
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
                    'category' => $rule['category'],
                    'reason' => $rule['reason'],
                    'rule' => sprintf('IF pH=%s AND Turbidity=%s AND TDS=%s', 
                        $rule['ph'], $rule['turbidity'], $rule['tds'])
                ];
            }
        }

        // Jika tidak ada rule yang aktif
        // Jika tidak ada rule yang aktif
        if (empty($evaluatedRules)) {
            return [
                'water_quality_status' => 'Unknown',
                'water_quality_score' => 0,
                'category' => 'Unknown',
                'salinity_ppt' => $salinityPpt,
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
        // Generate recommendation berdasarkan dominant rule
        $recommendation = sprintf(
            "%s. Score: %.2f/100. Kategori: %s",
            $dominantRule['reason'],
            $finalScore,
            $dominantRule['category']
        );

        // Tambahkan saran aksi berdasarkan kategori
        if ($finalScore < 30) {
            $recommendation .= ' ⚠️ Segera lakukan partial water exchange dan periksa parameter air.';
        } elseif ($finalScore < 50) {
            $recommendation .= ' 🔍 Monitoring ketat diperlukan. Persiapkan tindakan korektif.';
        } elseif ($finalScore < 70) {
            $recommendation .= ' ✓ Monitoring rutin, kondisi dapat ditingkatkan.';
        } elseif ($finalScore < 85) {
            $recommendation .= ' ✓✓ Kondisi baik, pertahankan monitoring rutin.';
        } else {
            $recommendation .= ' ✓✓✓ Kondisi excellent, pertahankan!';
        }

        return [
            'water_quality_status' => $dominantRule['category'],
            'water_quality_score' => $finalScore,
            'category' => $dominantRule['category'],
            'salinity_ppt' => $salinityPpt,
            'recommendation' => $recommendation,
            'fuzzy_details' => $fuzzyDetails,
            'rule_strength' => $dominantRule['strength'],
            'active_rules_count' => count($evaluatedRules),
        ];
    }
}