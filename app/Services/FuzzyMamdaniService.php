<?php

declare(strict_types=1);

namespace App\Services;

/**
 * =============================================================================
 * FuzzyMamdaniService - Water Quality Monitoring for Vaname Shrimp Aquaculture
 * =============================================================================
 * 
 * Sistem monitoring kualitas air berbasis logika Fuzzy Mamdani untuk budidaya
 * udang vaname (Litopenaeus vannamei).
 * 
 * =============================================================================
 * REFACTORED VERSION - 27 RULE BASE IMPLEMENTATION
 * =============================================================================
 * 
 * KEY CHANGES FROM PREVIOUS VERSION:
 * 1. TDS (ppm) digunakan LANGSUNG sebagai input fuzzy
 * 2. Implementasi FULL 27 RULES (3³ permutasi: pH × Turbidity × TDS)
 * 3. Defuzzifikasi/Scoring logic dipisahkan ke method calculateScore() terpisah
 *    untuk memenuhi Single Responsibility Principle.
 * 
 * =============================================================================
 * BIOLOGICAL & SCIENTIFIC BASIS (Reference: kualitas_air_udangvaname.txt)
 * =============================================================================
 * 
 * pH PARAMETER (SNI 8037.1:2014):
 * - Optimal: 7.5 - 8.5 (aktivitas enzimatis maksimal, osmoregulasi efisien)
 * - Sub-optimal Rendah: 6.5 - 7.4 (nafsu makan menurun, metabolisme melambat)
 * - Sub-optimal Tinggi: 8.6 - 9.0 (risiko toksisitas NH3 meningkat)
 * - Kritis Asam: < 6.5 (gagal kalsifikasi, toksisitas H2S)
 * - Kritis Basa: > 9.0 (kerusakan insang, autotoksikasi amonia)
 * 
 * TDS PARAMETER (Direct Sensor Input):
 * - Low: < 350 ppm (defisiensi mineral, stres osmotik tinggi)
 * - Medium/Optimal: 300 - 800 ppm (keseimbangan mineral untuk air tawar/payau rendah)
 * - High: > 700 ppm (terlalu tinggi untuk sistem air tawar)
 * Note: TDS includes mineral composition (K+, Mg2+, Ca2+)
 * 
 * TURBIDITY PARAMETER (NTU - Nephelometric Turbidity Units):
 * - Clear/Jernih: 0 - 25 NTU (kurang plankton, tidak ideal)
 * - Optimal: 20 - 45 NTU (plankton seimbang, kecerahan 30-45 cm)
 * - Turbid/Keruh: > 40 NTU (risiko penyumbatan insang, >60 NTU = 4x risiko kematian)
 * 
 * =============================================================================
 * FUZZY INFERENCE SYSTEM SPECIFICATION
 * =============================================================================
 * 
 * Input Variables: pH, TDS (ppm), Turbidity (NTU)
 * Output Variable: Water Quality Score (0-100)
 * Membership Functions: Trapezoidal (3 MF per input)
 * Inference Method: Mamdani (MIN for AND, MAX for aggregation)
 * Defuzzification: Weighted Average (Center of Gravity approximation)
 * Total Rules: 27 (3 × 3 × 3 full factorial design)
 * 
 * @author TambaQ IoT System
 * @version 3.0.0 - 27-Rule Refactored Version
 * @see kualitas_air_udangvaname.txt
 */
class FuzzyMamdaniService
{
    // =========================================================================
    // CLASS PROPERTIES - Stored Values for Analysis
    // =========================================================================
    
    /** @var float Raw TDS input value (ppm) */
    private float $inputTds = 0.0;
    
    /** @var float Raw pH input value */
    private float $inputPh = 0.0;
    
    /** @var float Raw Turbidity input value (NTU) */
    private float $inputTurbidity = 0.0;
    
    /** @var array Fuzzified membership degrees for TDS */
    private array $fuzzifiedTds = [];
    
    /** @var array Fuzzified membership degrees for pH */
    private array $fuzzifiedPh = [];
    
    /** @var array Fuzzified membership degrees for Turbidity */
    private array $fuzzifiedTurbidity = [];
    
    /** @var array All evaluated rules with their firing strengths */
    private array $evaluatedRules = [];

    // =========================================================================
    // MEMBERSHIP FUNCTIONS - FUZZIFICATION LAYER
    // =========================================================================
    
    /**
     * Fuzzify pH input into linguistic variables.
     * 
     * Membership Sets (based on SNI 8037.1:2014 and biological constraints):
     * - LOW (Asam): [0, 0, 6.5, 7.2] - Acidic, dangerous for shrimp
     * - NORMAL (Optimal): [7.0, 7.5, 8.0, 8.5] - Golden window for growth
     * - HIGH (Basa): [8.2, 9.0, 14, 14] - Alkaline, NH3 toxicity risk
     * 
     * Biological Rationale:
     * - pH < 7.5: Hemolymph acidosis, molting failure, melanosis risk
     * - pH 7.5-8.5: Optimal enzyme activity, efficient osmoregulation
     * - pH > 8.5: Ammonia (NH3) toxicity crisis - logarithmic increase
     * 
     * @param float $value pH value (0-14 scale)
     * @return void Sets $this->fuzzifiedPh
     */
    private function fuzzifyPh(float $value): void
    {
        $this->fuzzifiedPh = [
            'low'    => $this->trapezoidMF($value, 0.0, 0.0, 6.5, 7.2),
            'normal' => $this->trapezoidMF($value, 7.0, 7.5, 8.0, 8.5),
            'high'   => $this->trapezoidMF($value, 8.2, 9.0, 14.0, 14.0),
        ];
    }

    /**
     * Fuzzify TDS input DIRECTLY into linguistic variables.
     * 
     * CRITICAL: TDS (ppm) is used DIRECTLY - NO conversion to salinity!
     * This is as per the strict requirement for direct sensor mapping.
     * 
     * Membership Sets (designed for low-salinity/freshwater aquaculture):
     * - LOW: [0, 0, 200, 350] - Mineral deficiency, osmotic stress
     * - MEDIUM: [300, 400, 600, 800] - Optimal mineral balance
     * - HIGH: [700, 1000, 3000, 3000] - Too high for freshwater systems
     * 
     * Biological Rationale (from kualitas_air_udangvaname.txt):
     * - TDS represents total mineral content (Na+, K+, Mg2+, Ca2+)
     * - Low TDS = High osmoregulatory cost (Na+/K+-ATPase stress)
     * - Optimal TDS = Balanced ionic environment for growth
     * - Correlates with 300-600 ppm for freshwater, higher for brackish
     * 
     * @param float $value TDS value in ppm (mg/L) - DIRECT INPUT
     * @return void Sets $this->fuzzifiedTds
     */
    private function fuzzifyTds(float $value): void
    {
        $this->fuzzifiedTds = [
            'low'    => $this->trapezoidMF($value, 0.0, 0.0, 200.0, 350.0),
            'medium' => $this->trapezoidMF($value, 300.0, 400.0, 600.0, 800.0),
            'high'   => $this->trapezoidMF($value, 700.0, 1000.0, 3000.0, 3000.0),
        ];
    }

    /**
     * Fuzzify Turbidity input into linguistic variables.
     * 
     * Membership Sets (based on aquaculture research):
     * - CLEAR (Jernih): [0, 0, 15, 25] - Too clear, insufficient plankton
     * - OPTIMAL: [20, 25, 35, 45] - Balanced plankton, good Secchi visibility
     * - TURBID (Keruh): [40, 60, 150, 150] - Dangerous for gill health
     * 
     * Biological Rationale:
     * - Clear water (< 20 NTU): Lacks natural food (phytoplankton)
     * - Optimal turbidity: Secchi disk visibility 30-45 cm
     * - High turbidity (> 60 NTU): 4x mortality risk from gill clogging
     * 
     * @param float $value Turbidity in NTU (Nephelometric Turbidity Units)
     * @return void Sets $this->fuzzifiedTurbidity
     */
    private function fuzzifyTurbidity(float $value): void
    {
        $this->fuzzifiedTurbidity = [
            'clear'   => $this->trapezoidMF($value, 0.0, 0.0, 15.0, 25.0),
            'optimal' => $this->trapezoidMF($value, 20.0, 25.0, 35.0, 45.0),
            'turbid'  => $this->trapezoidMF($value, 40.0, 60.0, 150.0, 150.0),
        ];
    }

    /**
     * Trapezoidal Membership Function.
     * 
     * Shape: Flat-top trapezoid defined by four points [a, b, c, d]
     *        _______
     *       /       \
     *      /         \
     *   __/           \__
     *     a   b   c   d
     * 
     * - Rises from 0 at 'a' to 1 at 'b'
     * - Stays at 1 from 'b' to 'c' (core region)
     * - Falls from 1 at 'c' to 0 at 'd'
     * 
     * Special cases:
     * - If a == b: Left shoulder (starts at full membership)
     * - If c == d: Right shoulder (ends at full membership)
     * 
     * @param float $x Input crisp value
     * @param float $a Start of rise (μ=0)
     * @param float $b End of rise / start of plateau (μ=1)
     * @param float $c End of plateau / start of fall (μ=1)
     * @param float $d End of fall (μ=0)
     * @return float Membership degree [0.0, 1.0]
     */
    private function trapezoidMF(float $x, float $a, float $b, float $c, float $d): float
    {
        // Left shoulder case: a == b means full membership from -∞ to b
        if ($a == $b) {
            if ($x <= $b) {
                return 1.0;
            }
            if ($x >= $d) {
                return 0.0;
            }
            if ($x <= $c) {
                return 1.0;
            }
            // Falling edge for left shoulder
            return ($d - $x) / max(0.0001, $d - $c);
        }
        
        // Right shoulder case: c == d means full membership from c to +∞
        if ($c == $d) {
            if ($x >= $c) {
                return 1.0;
            }
            if ($x <= $a) {
                return 0.0;
            }
            if ($x >= $b) {
                return 1.0;
            }
            // Rising edge for right shoulder
            return ($x - $a) / max(0.0001, $b - $a);
        }
        
        // Standard trapezoid case
        if ($x <= $a || $x >= $d) {
            return 0.0;
        }
        
        // Core region: full membership
        if ($x >= $b && $x <= $c) {
            return 1.0;
        }
        
        // Rising edge
        if ($x > $a && $x < $b) {
            return ($x - $a) / ($b - $a);
        }
        
        // Falling edge
        return ($d - $x) / ($d - $c);
    }

    // =========================================================================
    // RULE BASE - 27 FUZZY RULES (3³ Full Factorial)
    // =========================================================================
    
    /**
     * Define the complete 27-Rule Base for Vaname Shrimp Water Quality.
     * 
     * ==========================================================================
     * RULE BASE DESIGN PHILOSOPHY
     * ==========================================================================
     * 
     * This rule base implements a 3³ = 27 rule full factorial design covering
     * all combinations of:
     * - pH: low, normal, high
     * - Turbidity: clear, optimal, turbid
     * - TDS: low, medium, high
     * 
     * SCORING PRINCIPLES (based on biological constraints):
     * 1. pH has VETO POWER - extreme pH (low or high) severely penalizes score
     *    regardless of other parameters due to acute physiological stress
     * 2. TDS LOW is CRITICAL - indicates severe mineral deficiency
     * 3. TURBIDITY TURBID amplifies stress when combined with other poor parameters
     * 4. OPTIMAL conditions: pH normal + TDS medium + Turbidity optimal = EXCELLENT
     * 
     * SCORE CATEGORIES:
     * - Critical: 0-30 (immediate action required)
     * - Poor: 31-50 (significant problems, monitoring & correction needed)
     * - Fair: 51-65 (acceptable but improvable)
     * - Good: 66-80 (good conditions, maintain)
     * - Excellent: 81-100 (optimal conditions)
     * 
     * ==========================================================================
     * 
     * @return array Array of 27 rules with [ph, turbidity, tds, score, category, reason]
     */
    private function getRuleBase(): array
    {
        return [
            // =================================================================
            // RULES 1-9: pH LOW (Asam berbahaya untuk udang)
            // pH < 7.2 causes acidosis, molting failure, melanosis
            // =================================================================
            
            // Rule 1: pH Low + Clear + TDS Low
            [
                'ph' => 'low',
                'turbidity' => 'clear',
                'tds' => 'low',
                'score' => 35,
                'category' => 'Poor',
                'reason' => 'pH rendah (asidosis), air terlalu jernih (kurang plankton), TDS rendah (defisiensi mineral)'
            ],
            
            // Rule 2: pH Low + Clear + TDS Medium
            [
                'ph' => 'low',
                'turbidity' => 'clear',
                'tds' => 'medium',
                'score' => 40,
                'category' => 'Poor',
                'reason' => 'pH rendah (asidosis), air jernih meski TDS optimal'
            ],
            
            // Rule 3: pH Low + Clear + TDS High
            [
                'ph' => 'low',
                'turbidity' => 'clear',
                'tds' => 'high',
                'score' => 25,
                'category' => 'Critical',
                'reason' => 'pH rendah + TDS tinggi = stres osmotik ganda, air jernih'
            ],
            
            // Rule 4: pH Low + Optimal Turbidity + TDS Low
            [
                'ph' => 'low',
                'turbidity' => 'optimal',
                'tds' => 'low',
                'score' => 38,
                'category' => 'Poor',
                'reason' => 'pH rendah (asidosis), meski kekeruhan optimal; TDS rendah'
            ],
            
            // Rule 5: pH Low + Optimal Turbidity + TDS Medium
            [
                'ph' => 'low',
                'turbidity' => 'optimal',
                'tds' => 'medium',
                'score' => 45,
                'category' => 'Fair',
                'reason' => 'pH rendah (asidosis), kekeruhan & TDS optimal - koreksi pH diperlukan'
            ],
            
            // Rule 6: pH Low + Optimal Turbidity + TDS High
            [
                'ph' => 'low',
                'turbidity' => 'optimal',
                'tds' => 'high',
                'score' => 35,
                'category' => 'Poor',
                'reason' => 'pH rendah + TDS tinggi = sinergi negatif'
            ],
            
            // Rule 7: pH Low + Turbid + TDS Low
            [
                'ph' => 'low',
                'turbidity' => 'turbid',
                'tds' => 'low',
                'score' => 20,
                'category' => 'Critical',
                'reason' => 'pH rendah + air sangat keruh (bahaya insang) + TDS rendah - KRITIS!'
            ],
            
            // Rule 8: pH Low + Turbid + TDS Medium
            [
                'ph' => 'low',
                'turbidity' => 'turbid',
                'tds' => 'medium',
                'score' => 30,
                'category' => 'Poor',
                'reason' => 'pH rendah + air sangat keruh (penyumbatan insang)'
            ],
            
            // Rule 9: pH Low + Turbid + TDS High
            [
                'ph' => 'low',
                'turbidity' => 'turbid',
                'tds' => 'high',
                'score' => 15,
                'category' => 'Critical',
                'reason' => 'Semua parameter BURUK - pH asam, air keruh, TDS tinggi. BAHAYA KEMATIAN MASSAL!'
            ],
            
            // =================================================================
            // RULES 10-18: pH NORMAL (Kondisi ideal untuk udang vaname)
            // pH 7.5-8.5 is the "golden window" - optimal enzyme activity
            // =================================================================
            
            // Rule 10: pH Normal + Clear + TDS Low
            [
                'ph' => 'normal',
                'turbidity' => 'clear',
                'tds' => 'low',
                'score' => 55,
                'category' => 'Fair',
                'reason' => 'pH optimal, air terlalu jernih (kurang plankton), TDS rendah (defisiensi mineral)'
            ],
            
            // Rule 11: pH Normal + Clear + TDS Medium
            [
                'ph' => 'normal',
                'turbidity' => 'clear',
                'tds' => 'medium',
                'score' => 75,
                'category' => 'Good',
                'reason' => 'pH & TDS optimal, air jernih (perlu penambahan plankton/pupuk)'
            ],
            
            // Rule 12: pH Normal + Clear + TDS High
            [
                'ph' => 'normal',
                'turbidity' => 'clear',
                'tds' => 'high',
                'score' => 60,
                'category' => 'Fair',
                'reason' => 'pH optimal, TDS tinggi, air jernih - kurangi TDS & tambah plankton'
            ],
            
            // Rule 13: pH Normal + Optimal Turbidity + TDS Low
            [
                'ph' => 'normal',
                'turbidity' => 'optimal',
                'tds' => 'low',
                'score' => 72,
                'category' => 'Good',
                'reason' => 'pH & kekeruhan optimal, TDS rendah - tambah mineral (KCl, MgCl2)'
            ],
            
            // Rule 14: pH Normal + Optimal Turbidity + TDS Medium - BEST CASE
            [
                'ph' => 'normal',
                'turbidity' => 'optimal',
                'tds' => 'medium',
                'score' => 95,
                'category' => 'Excellent',
                'reason' => 'KONDISI OPTIMAL! Semua parameter ideal untuk pertumbuhan maksimal udang vaname'
            ],
            
            // Rule 15: pH Normal + Optimal Turbidity + TDS High
            [
                'ph' => 'normal',
                'turbidity' => 'optimal',
                'tds' => 'high',
                'score' => 78,
                'category' => 'Good',
                'reason' => 'pH & kekeruhan optimal, TDS agak tinggi - monitoring mineral balance'
            ],
            
            // Rule 16: pH Normal + Turbid + TDS Low
            [
                'ph' => 'normal',
                'turbidity' => 'turbid',
                'tds' => 'low',
                'score' => 50,
                'category' => 'Fair',
                'reason' => 'pH optimal, air sangat keruh (risiko insang), TDS rendah'
            ],
            
            // Rule 17: pH Normal + Turbid + TDS Medium
            [
                'ph' => 'normal',
                'turbidity' => 'turbid',
                'tds' => 'medium',
                'score' => 58,
                'category' => 'Fair',
                'reason' => 'pH & TDS optimal, air terlalu keruh (risiko penyumbatan insang)'
            ],
            
            // Rule 18: pH Normal + Turbid + TDS High
            [
                'ph' => 'normal',
                'turbidity' => 'turbid',
                'tds' => 'high',
                'score' => 42,
                'category' => 'Poor',
                'reason' => 'TDS tinggi + air sangat keruh - stres ganda pada sistem respirasi'
            ],
            
            // =================================================================
            // RULES 19-27: pH HIGH (Basa berbahaya - toksisitas NH3)
            // pH > 8.5 causes ammonia toxicity crisis (NH3 increases exponentially)
            // =================================================================
            
            // Rule 19: pH High + Clear + TDS Low
            [
                'ph' => 'high',
                'turbidity' => 'clear',
                'tds' => 'low',
                'score' => 40,
                'category' => 'Poor',
                'reason' => 'pH tinggi (risiko NH3), air jernih, TDS rendah'
            ],
            
            // Rule 20: pH High + Clear + TDS Medium
            [
                'ph' => 'high',
                'turbidity' => 'clear',
                'tds' => 'medium',
                'score' => 52,
                'category' => 'Fair',
                'reason' => 'pH tinggi (risiko toksisitas amonia), TDS optimal, air jernih'
            ],
            
            // Rule 21: pH High + Clear + TDS High
            [
                'ph' => 'high',
                'turbidity' => 'clear',
                'tds' => 'high',
                'score' => 38,
                'category' => 'Poor',
                'reason' => 'pH tinggi + TDS tinggi = stres osmotik, air jernih'
            ],
            
            // Rule 22: pH High + Optimal Turbidity + TDS Low
            [
                'ph' => 'high',
                'turbidity' => 'optimal',
                'tds' => 'low',
                'score' => 48,
                'category' => 'Fair',
                'reason' => 'pH tinggi (koreksi dengan molase), kekeruhan optimal, TDS rendah'
            ],
            
            // Rule 23: pH High + Optimal Turbidity + TDS Medium
            [
                'ph' => 'high',
                'turbidity' => 'optimal',
                'tds' => 'medium',
                'score' => 68,
                'category' => 'Good',
                'reason' => 'pH agak tinggi, kekeruhan & TDS optimal - turunkan pH dengan molase/gula'
            ],
            
            // Rule 24: pH High + Optimal Turbidity + TDS High
            [
                'ph' => 'high',
                'turbidity' => 'optimal',
                'tds' => 'high',
                'score' => 55,
                'category' => 'Fair',
                'reason' => 'pH tinggi + TDS tinggi, kekeruhan optimal - koreksi pH & TDS'
            ],
            
            // Rule 25: pH High + Turbid + TDS Low
            [
                'ph' => 'high',
                'turbidity' => 'turbid',
                'tds' => 'low',
                'score' => 32,
                'category' => 'Poor',
                'reason' => 'pH tinggi + air sangat keruh - kombinasi berbahaya'
            ],
            
            // Rule 26: pH High + Turbid + TDS Medium
            [
                'ph' => 'high',
                'turbidity' => 'turbid',
                'tds' => 'medium',
                'score' => 40,
                'category' => 'Poor',
                'reason' => 'pH tinggi (risiko NH3) + air sangat keruh (risiko insang)'
            ],
            
            // Rule 27: pH High + Turbid + TDS High - WORST CASE
            [
                'ph' => 'high',
                'turbidity' => 'turbid',
                'tds' => 'high',
                'score' => 18,
                'category' => 'Critical',
                'reason' => 'Semua parameter BURUK - pH basa (NH3 toksik), air keruh, TDS tinggi. KRITIS!'
            ],
        ];
    }

    // =========================================================================
    // INFERENCE ENGINE - RULE EVALUATION
    // =========================================================================
    
    /**
     * Evaluate all 27 fuzzy rules using Mamdani inference.
     * 
     * Inference Process:
     * 1. For each rule, calculate firing strength using MIN operator (AND)
     * 2. Firing strength = MIN(μ_pH, μ_Turbidity, μ_TDS)
     * 3. Store all rules with non-zero firing strength
     * 
     * @return void Sets $this->evaluatedRules
     */
    private function evaluateRules(): void
    {
        $this->evaluatedRules = [];
        $rules = $this->getRuleBase();
        
        foreach ($rules as $index => $rule) {
            // Calculate rule firing strength using MIN (AND) operator
            $firingStrength = min(
                $this->fuzzifiedPh[$rule['ph']],
                $this->fuzzifiedTurbidity[$rule['turbidity']],
                $this->fuzzifiedTds[$rule['tds']]
            );
            
            // Only store rules with non-zero firing strength
            if ($firingStrength > 0) {
                $this->evaluatedRules[] = [
                    'rule_number' => $index + 1,
                    'strength' => $firingStrength,
                    'score' => $rule['score'],
                    'category' => $rule['category'],
                    'reason' => $rule['reason'],
                    'antecedent' => sprintf(
                        'IF pH=%s AND Turbidity=%s AND TDS=%s',
                        strtoupper($rule['ph']),
                        strtoupper($rule['turbidity']),
                        strtoupper($rule['tds'])
                    ),
                ];
            }
        }
    }

    // =========================================================================
    // DEFUZZIFICATION - DECOUPLED SCORING LOGIC
    // =========================================================================
    
    /**
     * Calculate final water quality score using Weighted Average defuzzification.
     * 
     * ==========================================================================
     * DECOUPLED SCORING METHOD (Single Responsibility Principle)
     * ==========================================================================
     * 
     * This method is separated from the main evaluation logic to:
     * 1. Ensure Single Responsibility - scoring is independent concern
     * 2. Allow easy modification of defuzzification strategy
     * 3. Improve testability and maintainability
     * 
     * Defuzzification Method: Weighted Average (Center of Gravity approximation)
     * 
     * Formula: Score = Σ(strength_i × score_i) / Σ(strength_i)
     * 
     * This is equivalent to the Centroid method when output membership functions
     * are singletons (which they effectively are in our rule consequents).
     * 
     * @return array [score, dominantRule]
     */
    private function calculateScore(): array
    {
        // Handle edge case: no rules fired
        if (empty($this->evaluatedRules)) {
            return [
                'score' => 0.0,
                'dominant_rule' => null,
            ];
        }
        
        // Calculate weighted average (defuzzification)
        $totalStrength = 0.0;
        $weightedSum = 0.0;
        
        foreach ($this->evaluatedRules as $rule) {
            $weightedSum += $rule['strength'] * $rule['score'];
            $totalStrength += $rule['strength'];
        }
        
        // Avoid division by zero
        $finalScore = ($totalStrength > 0) 
            ? round($weightedSum / $totalStrength, 2) 
            : 0.0;
        
        // Find dominant rule (highest firing strength)
        $dominantRule = $this->evaluatedRules[0];
        foreach ($this->evaluatedRules as $rule) {
            if ($rule['strength'] > $dominantRule['strength']) {
                $dominantRule = $rule;
            }
        }
        
        return [
            'score' => $finalScore,
            'dominant_rule' => $dominantRule,
        ];
    }

    /**
     * Get quality label from numeric score.
     * 
     * @param float $score Water quality score (0-100)
     * @return string Human-readable quality label
     */
    private function getQualityLabel(float $score): string
    {
        if ($score >= 85) return 'Sangat Baik (Excellent)';
        if ($score >= 70) return 'Baik (Good)';
        if ($score >= 50) return 'Cukup (Fair)';
        if ($score >= 30) return 'Buruk (Poor)';
        return 'Kritis (Critical)';
    }

    /**
     * Get quality category from numeric score.
     * 
     * @param float $score Water quality score (0-100)
     * @return string Category code
     */
    private function getQualityCategory(float $score): string
    {
        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 50) return 'Fair';
        if ($score >= 30) return 'Poor';
        return 'Critical';
    }

    // =========================================================================
    // RECOMMENDATION GENERATOR
    // =========================================================================
    
    /**
     * Generate actionable recommendations based on analysis results.
     * 
     * @param float $score Final quality score
     * @param array|null $dominantRule The rule with highest firing strength
     * @return string Detailed recommendation text
     */
    private function generateRecommendation(float $score, ?array $dominantRule): string
    {
        if ($dominantRule === null) {
            return 'Data sensor di luar rentang normal. Periksa kalibrasi sensor.';
        }
        
        $recommendation = sprintf(
            "%s. Score: %.2f/100. Kategori: %s",
            $dominantRule['reason'],
            $score,
            $dominantRule['category']
        );
        
        // Add action guidance based on score range
        if ($score < 30) {
            $recommendation .= ' ⚠️ DARURAT! Segera lakukan partial water exchange dan periksa semua parameter air.';
        } elseif ($score < 50) {
            $recommendation .= ' 🔍 Monitoring ketat diperlukan. Persiapkan dan lakukan tindakan korektif.';
        } elseif ($score < 70) {
            $recommendation .= ' ✓ Monitoring rutin, kondisi dapat ditingkatkan dengan penyesuaian minor.';
        } elseif ($score < 85) {
            $recommendation .= ' ✓✓ Kondisi baik, pertahankan monitoring rutin.';
        } else {
            $recommendation .= ' ✓✓✓ Kondisi excellent! Pertahankan parameter saat ini.';
        }
        
        return $recommendation;
    }

    // =========================================================================
    // MAIN PUBLIC API - ORCHESTRATION LAYER
    // =========================================================================
    
    /**
     * Main entry point: Evaluate Water Quality using Fuzzy Mamdani Inference.
     * 
     * ==========================================================================
     * ORCHESTRATION FLOW
     * ==========================================================================
     * 
     * 1. Store raw input values
     * 2. FUZZIFICATION: Convert crisp inputs to fuzzy membership degrees
     *    - pH → {low, normal, high}
     *    - TDS (DIRECT) → {low, medium, high}
     *    - Turbidity → {clear, optimal, turbid}
     * 3. INFERENCE: Evaluate all 27 rules, calculate firing strengths
     * 4. DEFUZZIFICATION: Calculate final score using Weighted Average (via calculateScore())
     * 5. Generate output report with recommendations
     * 
     * ==========================================================================
     * 
     * @param float $phValue pH measurement (0-14 scale)
     * @param float $tdsValue TDS in ppm - USED DIRECTLY, NO CONVERSION
     * @param float $turbidityValue Turbidity in NTU
     * @param float $temperature Temperature in °C (reserved for future use)
     * @return array Complete analysis results
     */
    public function evaluateWaterQuality(
        float $phValue,
        float $tdsValue,
        float $turbidityValue,
        float $temperature = 25.0
    ): array {
        // Step 1: Store raw input values
        $this->inputPh = $phValue;
        $this->inputTds = $tdsValue;
        $this->inputTurbidity = $turbidityValue;
        
        // Step 2: FUZZIFICATION - Convert crisp inputs to fuzzy sets
        $this->fuzzifyPh($phValue);
        $this->fuzzifyTds($tdsValue);  // DIRECT TDS USAGE
        $this->fuzzifyTurbidity($turbidityValue);
        
        // Step 3: INFERENCE - Evaluate all 27 rules
        $this->evaluateRules();
        
        // Handle case where no rules fired
        if (empty($this->evaluatedRules)) {
            return [
                'water_quality_status' => 'Unknown',
                'water_quality_score' => 0,
                'category' => 'Unknown',
                'recommendation' => 'Data sensor di luar rentang normal. Periksa kalibrasi sensor.',
                'fuzzy_details' => 'Tidak ada rule yang terpenuhi - nilai sensor mungkin di luar range.',
                'rule_strength' => 0,
                'active_rules_count' => 0,
            ];
        }
        
        // Step 4: DEFUZZIFICATION - Calculate final score (decoupled method)
        $scoreResult = $this->calculateScore();
        $finalScore = $scoreResult['score'];
        $dominantRule = $scoreResult['dominant_rule'];
        
        // Step 5: Build output report
        $fuzzyDetails = sprintf(
            "pH: %.2f (Asam: %.2f, Normal: %.2f, Basa: %.2f) | " .
            "TDS: %.0f ppm (Rendah: %.2f, Optimal: %.2f, Tinggi: %.2f) | " .
            "Turbidity: %.1f NTU (Jernih: %.2f, Optimal: %.2f, Keruh: %.2f) | " .
            "Active Rules: %d | Dominant Rule #%d (Strength: %.2f)",
            $phValue,
            $this->fuzzifiedPh['low'],
            $this->fuzzifiedPh['normal'],
            $this->fuzzifiedPh['high'],
            $tdsValue,
            $this->fuzzifiedTds['low'],
            $this->fuzzifiedTds['medium'],
            $this->fuzzifiedTds['high'],
            $turbidityValue,
            $this->fuzzifiedTurbidity['clear'],
            $this->fuzzifiedTurbidity['optimal'],
            $this->fuzzifiedTurbidity['turbid'],
            count($this->evaluatedRules),
            $dominantRule['rule_number'],
            $dominantRule['strength']
        );
        
        return [
            // Primary outputs
            'water_quality_score' => $finalScore,
            'water_quality_status' => $this->getQualityLabel($finalScore),
            'category' => $this->getQualityCategory($finalScore),
            
            // Actionable insight
            'recommendation' => $this->generateRecommendation($finalScore, $dominantRule),
            
            // Fuzzy analysis details
            'fuzzy_details' => $fuzzyDetails,
            'rule_strength' => $dominantRule['strength'],
            'active_rules_count' => count($this->evaluatedRules),
            'dominant_rule' => $dominantRule['reason'],
            
            // Raw membership values (for debugging/visualization)
            'membership' => [
                'ph' => $this->fuzzifiedPh,
                'tds' => $this->fuzzifiedTds,
                'turbidity' => $this->fuzzifiedTurbidity,
            ],
            'evaluated_rules' => $this->evaluatedRules,
        ];
    }

    /**
     * Simplified analysis method (alternative API signature).
     * 
     * This method provides a simpler interface where TDS comes first,
     * matching common sensor data formats.
     * 
     * @param float $tds TDS in ppm (DIRECT - no conversion)
     * @param float $ph pH value
     * @param float $turbidity Turbidity in NTU (optional, defaults to 30)
     * @return array Analysis results
     */
    public function analyze(float $tds, float $ph, float $turbidity = 30.0): array
    {
        return $this->evaluateWaterQuality($ph, $tds, $turbidity);
    }
}
