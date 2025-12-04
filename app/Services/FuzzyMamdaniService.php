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
     * 
     * PENTING: NTU = Nephelometric Turbidity Units (ukuran kekeruhan)
     * - NTU RENDAH = Air JERNIH/BENING = Kurang plankton (TIDAK BAIK)
     * - NTU SEDANG = Air AGAK KERUH = Plankton seimbang (OPTIMAL)
     * - NTU TINGGI = Air SANGAT KERUH = Berbahaya untuk insang (KRITIS)
     * 
     * Referensi: Batas aman < 30 NTU, Bahaya > 60 NTU (risiko 4x kematian)
     * Kecerahan optimal (Secchi Disk): 30-45 cm
     * 
     * Clear/Jernih: [0, 0, 15, 25] - NTU rendah, air terlalu jernih (kurang plankton)
     * Optimal: [20, 25, 35, 45] - NTU sedang, kekeruhan optimal (plankton seimbang)
     * Turbid/Keruh: [40, 60, 150, 150] - NTU tinggi, air terlalu keruh (sumbat insang)
     */
    private function turbidityMembership($value)
    {
        return [
            'clear' => $this->trapezoid($value, 0, 0, 15, 25),    // Jernih/bening (kurang baik)
            'optimal' => $this->trapezoid($value, 20, 25, 35, 45), // Optimal
            'turbid' => $this->trapezoid($value, 40, 60, 150, 150), // Keruh (berbahaya)
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
     * 
     * Turbidity Labels:
     * - clear = Air jernih/bening (NTU rendah) = Kurang plankton
     * - optimal = Air agak keruh (NTU sedang) = Plankton seimbang  
     * - turbid = Air sangat keruh (NTU tinggi) = Berbahaya
     */
    private function getFuzzyRules()
    {
        return [
            // Rule 1-9: pH Low (Asam berbahaya untuk udang)
            ['ph' => 'low', 'turbidity' => 'clear', 'tds' => 'low', 'score' => 35, 'category' => 'Poor', 'reason' => 'pH rendah, air terlalu tawar & jernih (kurang plankton)'],
            ['ph' => 'low', 'turbidity' => 'clear', 'tds' => 'medium', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH rendah, air jernih meski TDS optimal'],
            ['ph' => 'low', 'turbidity' => 'clear', 'tds' => 'high', 'score' => 25, 'category' => 'Critical', 'reason' => 'pH rendah + salinitas tinggi + air jernih'],
            ['ph' => 'low', 'turbidity' => 'optimal', 'tds' => 'low', 'score' => 38, 'category' => 'Poor', 'reason' => 'pH rendah, meski kekeruhan optimal'],
            ['ph' => 'low', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 45, 'category' => 'Fair', 'reason' => 'pH rendah, kekeruhan & TDS optimal'],
            ['ph' => 'low', 'turbidity' => 'optimal', 'tds' => 'high', 'score' => 35, 'category' => 'Poor', 'reason' => 'pH rendah + TDS tinggi'],
            ['ph' => 'low', 'turbidity' => 'turbid', 'tds' => 'low', 'score' => 20, 'category' => 'Critical', 'reason' => 'pH rendah + air sangat keruh (bahaya insang)'],
            ['ph' => 'low', 'turbidity' => 'turbid', 'tds' => 'medium', 'score' => 30, 'category' => 'Poor', 'reason' => 'pH rendah + air sangat keruh'],
            ['ph' => 'low', 'turbidity' => 'turbid', 'tds' => 'high', 'score' => 15, 'category' => 'Critical', 'reason' => 'Semua parameter buruk - BAHAYA!'],
            
            // Rule 10-18: pH Normal (Kondisi ideal untuk udang vaname)
            ['ph' => 'normal', 'turbidity' => 'clear', 'tds' => 'low', 'score' => 55, 'category' => 'Fair', 'reason' => 'pH optimal, air terlalu jernih & tawar (kurang plankton)'],
            ['ph' => 'normal', 'turbidity' => 'clear', 'tds' => 'medium', 'score' => 75, 'category' => 'Good', 'reason' => 'pH & TDS optimal, air jernih (kurang plankton)'],
            ['ph' => 'normal', 'turbidity' => 'clear', 'tds' => 'high', 'score' => 60, 'category' => 'Fair', 'reason' => 'pH optimal, TDS tinggi, air jernih'],
            ['ph' => 'normal', 'turbidity' => 'optimal', 'tds' => 'low', 'score' => 72, 'category' => 'Good', 'reason' => 'pH & kekeruhan optimal, TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 95, 'category' => 'Excellent', 'reason' => 'KONDISI OPTIMAL! Semua parameter ideal untuk pertumbuhan udang'],
            ['ph' => 'normal', 'turbidity' => 'optimal', 'tds' => 'high', 'score' => 78, 'category' => 'Good', 'reason' => 'pH & kekeruhan optimal, TDS agak tinggi'],
            ['ph' => 'normal', 'turbidity' => 'turbid', 'tds' => 'low', 'score' => 50, 'category' => 'Fair', 'reason' => 'pH optimal, air sangat keruh (risiko insang), TDS rendah'],
            ['ph' => 'normal', 'turbidity' => 'turbid', 'tds' => 'medium', 'score' => 58, 'category' => 'Fair', 'reason' => 'pH & TDS optimal, air terlalu keruh (risiko insang)'],
            ['ph' => 'normal', 'turbidity' => 'turbid', 'tds' => 'high', 'score' => 42, 'category' => 'Poor', 'reason' => 'TDS tinggi + air sangat keruh (bahaya)'],
            
            // Rule 19-27: pH High (Basa berbahaya untuk udang)
            ['ph' => 'high', 'turbidity' => 'clear', 'tds' => 'low', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH tinggi, air tawar & jernih'],
            ['ph' => 'high', 'turbidity' => 'clear', 'tds' => 'medium', 'score' => 52, 'category' => 'Fair', 'reason' => 'pH tinggi, TDS optimal, air jernih'],
            ['ph' => 'high', 'turbidity' => 'clear', 'tds' => 'high', 'score' => 38, 'category' => 'Poor', 'reason' => 'pH tinggi + TDS tinggi + air jernih'],
            ['ph' => 'high', 'turbidity' => 'optimal', 'tds' => 'low', 'score' => 48, 'category' => 'Fair', 'reason' => 'pH tinggi, kekeruhan optimal'],
            ['ph' => 'high', 'turbidity' => 'optimal', 'tds' => 'medium', 'score' => 68, 'category' => 'Good', 'reason' => 'pH agak tinggi, kekeruhan & TDS optimal'],
            ['ph' => 'high', 'turbidity' => 'optimal', 'tds' => 'high', 'score' => 55, 'category' => 'Fair', 'reason' => 'pH tinggi + TDS tinggi'],
            ['ph' => 'high', 'turbidity' => 'turbid', 'tds' => 'low', 'score' => 32, 'category' => 'Poor', 'reason' => 'pH tinggi + air sangat keruh'],
            ['ph' => 'high', 'turbidity' => 'turbid', 'tds' => 'medium', 'score' => 40, 'category' => 'Poor', 'reason' => 'pH tinggi + air sangat keruh'],
            ['ph' => 'high', 'turbidity' => 'turbid', 'tds' => 'high', 'score' => 18, 'category' => 'Critical', 'reason' => 'Semua parameter buruk - KRITIS!'],
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

        // Generate detailed fuzzy info dengan label yang jelas
        // clear = jernih (NTU rendah), optimal = kekeruhan ideal, turbid = keruh (NTU tinggi)
        $fuzzyDetails = sprintf(
            "pH: %.2f (Asam: %.2f, Normal: %.2f, Basa: %.2f) | " .
            "Turbidity: %.2f NTU (Jernih: %.2f, Optimal: %.2f, Keruh: %.2f) | " .
            "TDS: %.2f PPM (Rendah: %.2f, Optimal: %.2f, Tinggi: %.2f) | " .
            "Salinity: %.2f PPT | " .
            "Active Rules: %d | Dominant Rule Strength: %.2f",
            $phValue, $phMem['low'], $phMem['normal'], $phMem['high'],
            $turbidityValue, $turbidityMem['clear'], $turbidityMem['optimal'], $turbidityMem['turbid'],
            $tdsValue, $tdsMem['low'], $tdsMem['medium'], $tdsMem['high'],
            $salinityPpt,
            count($evaluatedRules),
            $dominantRule['strength']
        );

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