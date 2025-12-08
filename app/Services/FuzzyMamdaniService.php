<?php

namespace App\Services;

/**
 * =============================================================================
 * FuzzyMamdaniService - Low-Salinity Shrimp Aquaculture Monitoring System
 * =============================================================================
 * 
 * Sistem monitoring kualitas air berbasis logika Fuzzy Mamdani untuk budidaya
 * udang vaname (Litopenaeus vannamei) di lingkungan salinitas rendah (inland).
 * 
 * BIOLOGICAL CONSTRAINTS IMPLEMENTED:
 * 1. Salinity Conversion: S(ppt) = TDS(ppm) / 1000 (linear approximation)
 * 2. TDS Critical Threshold: <1000 ppm = Fatal Zone (Osmoregulatory Cost)
 * 3. pH Optimal Window: 7.5 - 8.5 (Strict, with Veto Power for extremes)
 * 4. Non-linear Rules: Poor pH overrides good TDS (Synergistic Stressors)
 * 
 * PHYSIOLOGICAL BASIS:
 * - Isosmotic Point: ~24-25 ppt (700-750 mOsm/kg)
 * - Below isosmotic: Udang = hyper-osmoregulator (Na+/K+-ATPase active)
 * - TDS <1000 ppm: Severe ionic gradient = High metabolic cost
 * - pH >8.5: NH3 toxicity risk (Ammonia equilibrium shift)
 * - pH <7.5: Acidosis risk (Hemocyanin oxygen affinity reduced)
 * 
 * @author TambaQ IoT System
 * @version 2.0.0 - Low-Salinity Optimized
 */
class FuzzyMamdaniService
{
    // =========================================================================
    // STORED VALUES FOR ANALYSIS
    // =========================================================================
    private float $inputTds = 0;
    private float $inputPh = 0;
    private float $inputTurbidity = 0;
    private float $calculatedSalinity = 0;
    
    // Fuzzified membership degrees
    private array $fuzzifiedTds = [];
    private array $fuzzifiedPh = [];
    private array $fuzzifiedTurbidity = [];
    
    // Rule evaluation results
    private array $rulesOutput = [];
    private array $evaluatedRules = [];

    // =========================================================================
    // SALINITY CONVERSION
    // =========================================================================
    
    /**
     * Calculate Salinity (ppt) from TDS (ppm)
     * 
     * Formula: Salinity (ppt) = TDS (ppm) / 1000
     * 
     * Basis: Linear approximation for field operations in low-salinity
     * inland aquaculture. Valid for TDS meters calibrated with NaCl standard.
     * 
     * Note: For higher accuracy, consider ionic composition (K+, Mg2+, Ca2+)
     * as inland water often has different ion ratios than seawater.
     * 
     * @param float $tdsValue TDS in ppm (mg/L)
     * @return float Salinity in ppt
     */
    public function calculateSalinity(float $tdsValue): float
    {
        if ($tdsValue < 0) return 0.0;
        return round($tdsValue / 1000, 2);
    }

    // =========================================================================
    // MEMBERSHIP FUNCTIONS - TDS (Critical for Osmoregulation)
    // =========================================================================
    
    /**
     * TDS Membership Function - Low-Salinity Aquaculture Optimized
     * 
     * BIOLOGICAL RATIONALE:
     * - <1000 ppm: FATAL ZONE - Steep osmotic gradient forces maximum
     *   Na+/K+-ATPase activity. Energy diverted from growth to homeostasis.
     *   Risk of potassium (K+) and magnesium (Mg2+) deficiency.
     * 
     * - 800-1200 ppm: MARGINAL ZONE - Transition area. Survivable but
     *   suboptimal. High stress if combined with poor pH.
     * 
     * - >1000-1200 ppm: OPTIMAL ZONE - Reduced osmotic pressure allows
     *   energy allocation for somatic growth. Minimum osmoregulatory cost.
     * 
     * Membership Sets:
     * - LOW (Bahaya): Left trapezoid [0, 0, 800, 1000] - Fatal osmotic stress
     * - MARGINAL: Triangle [800, 1000, 1200] - Transition zone
     * - OPTIMAL: Right trapezoid [1000, 1200, ∞, ∞] - Viable for growth
     */
    private function fuzzifyTds(float $val): void
    {
        // LOW: Full membership until 800, drops to 0 at 1000
        // Steep penalty curve representing exponential mortality risk
        $this->fuzzifiedTds['LOW'] = $this->trapezoidLeft($val, 800, 1000);
        
        // MARGINAL: Triangle peaked at 1000, spans 800-1200
        // Captures uncertainty zone around critical threshold
        $this->fuzzifiedTds['MARGINAL'] = $this->triangle($val, 800, 1000, 1200);
        
        // OPTIMAL: Starts rising at 1000, full at 1200+
        // Values >1200 ppm are fully optimal
        $this->fuzzifiedTds['OPTIMAL'] = $this->trapezoidRight($val, 1000, 1200);
    }

    // =========================================================================
    // MEMBERSHIP FUNCTIONS - pH (Strict Window with Veto Power)
    // =========================================================================
    
    /**
     * pH Membership Function - Strict Biological Constraints
     * 
     * BIOLOGICAL RATIONALE:
     * 
     * ACIDIC (pH < 7.5):
     * - Hemolymph acidosis risk
     * - Reduced hemocyanin oxygen affinity (Bohr Effect)
     * - Polyphenol Oxidase activation → melanosis/blackspot
     * - Impaired molting success
     * 
     * OPTIMAL (pH 7.5 - 8.5):
     * - "Golden Window" per SNI standards
     * - Minimal NH3 toxicity (TAN mostly as NH4+)
     * - Stable hemolymph pH
     * - Flat-top trapezoid: no penalty for small fluctuations
     * 
     * ALKALINE (pH > 8.5) - VETO POWER:
     * - Ammonia toxicity crisis!
     * - At pH 8.0: ~5% NH3 (manageable)
     * - At pH 9.0: 30-50% NH3 (LETHAL)
     * - Logarithmic increase in toxicity
     * - This condition OVERRIDES good TDS (synergistic stressor)
     * 
     * Membership Sets:
     * - ACIDIC: Left trapezoid [0, 0, 7.0, 7.5] - Acidosis risk
     * - OPTIMAL: Full trapezoid [7.2, 7.5, 8.5, 8.8] - Golden window
     * - ALKALINE: Right trapezoid [8.5, 9.0, 14, 14] - NH3 toxicity (VETO)
     */
    private function fuzzifyPh(float $val): void
    {
        // ACIDIC: Full until pH 7.0, drops to 0 at 7.5
        $this->fuzzifiedPh['ACIDIC'] = $this->trapezoidLeft($val, 7.0, 7.5);
        
        // OPTIMAL: Flat-top from 7.5 to 8.5 (stable golden window)
        // Rises from 7.2, stays at 1.0 from 7.5-8.5, drops to 0 at 8.8
        $this->fuzzifiedPh['OPTIMAL'] = $this->trapezoidFull($val, 7.2, 7.5, 8.5, 8.8);
        
        // ALKALINE: Starts rising at 8.5, full at 9.0
        // Has VETO POWER - overrides good TDS due to acute NH3 lethality
        $this->fuzzifiedPh['ALKALINE'] = $this->trapezoidRight($val, 8.5, 9.0);
    }

    // =========================================================================
    // MEMBERSHIP FUNCTIONS - TURBIDITY (Supporting Parameter)
    // =========================================================================
    
    /**
     * Turbidity Membership Function
     * 
     * BIOLOGICAL RATIONALE:
     * - NTU LOW (Jernih): Insufficient plankton, poor natural food
     * - NTU OPTIMAL: Balanced plankton, good Secchi disk visibility (30-45cm)
     * - NTU HIGH (Keruh): Gill clogging risk, especially critical when
     *   osmoregulation is already stressed (low TDS environment)
     * 
     * Note: High turbidity + Low TDS = Compounded stress on gills
     */
    private function fuzzifyTurbidity(float $val): void
    {
        // CLEAR: Too clear, lacks plankton (0-25 NTU)
        $this->fuzzifiedTurbidity['CLEAR'] = $this->trapezoidLeft($val, 15, 25);
        
        // OPTIMAL: Good plankton balance (20-45 NTU)
        $this->fuzzifiedTurbidity['OPTIMAL'] = $this->trapezoidFull($val, 20, 25, 35, 45);
        
        // TURBID: Dangerous for gills (>40 NTU)
        $this->fuzzifiedTurbidity['TURBID'] = $this->trapezoidRight($val, 40, 60);
    }

    // =========================================================================
    // MATHEMATICAL HELPER FUNCTIONS (Membership Curves)
    // =========================================================================
    
    /**
     * Triangle Membership Function
     * Peak at b, rises from a, falls to c
     */
    private function triangle(float $x, float $a, float $b, float $c): float
    {
        if ($x <= $a || $x >= $c) return 0.0;
        if ($x == $b) return 1.0;
        if ($x < $b) return ($x - $a) / max(0.0001, $b - $a);
        return ($c - $x) / max(0.0001, $c - $b);
    }
    
    /**
     * Full Trapezoid Membership Function
     * Rises a→b, flat b→c, falls c→d
     */
    private function trapezoidFull(float $x, float $a, float $b, float $c, float $d): float
    {
        if ($x <= $a || $x >= $d) return 0.0;
        if ($x >= $b && $x <= $c) return 1.0;
        if ($x < $b) return ($x - $a) / max(0.0001, $b - $a);
        return ($d - $x) / max(0.0001, $d - $c);
    }
    
    /**
     * Left Shoulder Trapezoid (1 from -∞ to a, drops a→b)
     */
    private function trapezoidLeft(float $x, float $a, float $b): float
    {
        if ($x <= $a) return 1.0;
        if ($x >= $b) return 0.0;
        return ($b - $x) / ($b - $a);
    }
    
    /**
     * Right Shoulder Trapezoid (rises a→b, 1 from b to +∞)
     */
    private function trapezoidRight(float $x, float $a, float $b): float
    {
        if ($x <= $a) return 0.0;
        if ($x >= $b) return 1.0;
        return ($x - $a) / ($b - $a);
    }

    // =========================================================================
    // FUZZY RULE BASE - WITH VETO POWER LOGIC
    // =========================================================================
    
    /**
     * Evaluate Fuzzy Rules with Synergistic Stressor Logic
     * 
     * KEY DESIGN PRINCIPLES:
     * 
     * 1. pH VETO POWER: Extreme pH (ACIDIC or ALKALINE) forces POOR output
     *    regardless of TDS. This models acute toxicity that cannot be
     *    compensated by good mineral levels.
     * 
     * 2. TDS LOW = FATAL: Low TDS triggers severe penalty. In low-salinity
     *    farming, TDS <1000 ppm means chronic osmotic stress.
     * 
     * 3. SYNERGISTIC STRESSORS: Marginal TDS + non-optimal pH = POOR
     *    (worse than either stressor alone)
     * 
     * 4. TURBIDITY MODIFIER: High turbidity amplifies stress when TDS is
     *    already marginal (gill clogging + osmoregulation burden)
     * 
     * Output Sets:
     * - POOR: Center at 20 (Score 0-40)
     * - MODERATE: Center at 50 (Score 30-70)
     * - EXCELLENT: Center at 90 (Score 60-100)
     */
    private function evaluateRules(): void
    {
        $this->rulesOutput = [
            'POOR' => 0.0,
            'MODERATE' => 0.0,
            'EXCELLENT' => 0.0
        ];
        
        $this->evaluatedRules = [];
        
        // =====================================================================
        // RULE 1: EXCELLENT - All parameters optimal
        // IF TDS=OPTIMAL AND pH=OPTIMAL AND Turbidity=OPTIMAL THEN EXCELLENT
        // =====================================================================
        $rule1Strength = min(
            $this->fuzzifiedTds['OPTIMAL'],
            $this->fuzzifiedPh['OPTIMAL'],
            $this->fuzzifiedTurbidity['OPTIMAL']
        );
        if ($rule1Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 1,
                'strength' => $rule1Strength,
                'output' => 'EXCELLENT',
                'description' => 'Semua parameter optimal - kondisi ideal untuk pertumbuhan udang'
            ];
        }
        
        // =====================================================================
        // RULE 2: EXCELLENT (Relaxed) - TDS & pH optimal, turbidity acceptable
        // IF TDS=OPTIMAL AND pH=OPTIMAL THEN EXCELLENT (weight 0.9)
        // =====================================================================
        $rule2Strength = min(
            $this->fuzzifiedTds['OPTIMAL'],
            $this->fuzzifiedPh['OPTIMAL']
        ) * 0.9; // Slightly reduced weight
        if ($rule2Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 2,
                'strength' => $rule2Strength,
                'output' => 'EXCELLENT',
                'description' => 'TDS & pH optimal - kondisi sangat baik'
            ];
        }
        
        // =====================================================================
        // RULE 3: POOR - TDS LOW (Fatal Zone)
        // IF TDS=LOW THEN POOR (HIGH PRIORITY)
        // Osmoregulatory cost is too high, regardless of other parameters
        // =====================================================================
        $rule3Strength = $this->fuzzifiedTds['LOW'];
        if ($rule3Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 3,
                'strength' => $rule3Strength,
                'output' => 'POOR',
                'description' => 'TDS <1000 ppm - Zona FATAL! Biaya osmoregulasi terlalu tinggi. Risiko defisiensi mineral (K+, Mg2+)'
            ];
        }
        
        // =====================================================================
        // RULE 4: POOR - pH ALKALINE (VETO POWER - Ammonia Toxicity)
        // IF pH=ALKALINE THEN POOR (ABSOLUTE VETO)
        // NH3 toxicity is acutely lethal, overrides all other parameters
        // =====================================================================
        $rule4Strength = $this->fuzzifiedPh['ALKALINE'];
        if ($rule4Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 4,
                'strength' => $rule4Strength,
                'output' => 'POOR',
                'description' => 'pH >8.5 - BAHAYA! Risiko toksisitas Amonia (NH3). Pada pH 9.0, NH3 bisa mencapai 30-50%!'
            ];
        }
        
        // =====================================================================
        // RULE 5: POOR - pH ACIDIC (Veto Power - Acidosis)
        // IF pH=ACIDIC THEN POOR
        // Hemolymph acidosis, reduced oxygen transport, melanosis risk
        // =====================================================================
        $rule5Strength = $this->fuzzifiedPh['ACIDIC'];
        if ($rule5Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 5,
                'strength' => $rule5Strength,
                'output' => 'POOR',
                'description' => 'pH <7.5 - BAHAYA! Risiko asidosis, molting gagal, melanosis (bintik hitam)'
            ];
        }
        
        // =====================================================================
        // RULE 6: MODERATE - Marginal TDS with Optimal pH
        // IF TDS=MARGINAL AND pH=OPTIMAL THEN MODERATE
        // Survivable but suboptimal, close monitoring needed
        // =====================================================================
        $rule6Strength = min(
            $this->fuzzifiedTds['MARGINAL'],
            $this->fuzzifiedPh['OPTIMAL']
        );
        if ($rule6Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 6,
                'strength' => $rule6Strength,
                'output' => 'MODERATE',
                'description' => 'TDS marginal (800-1200 ppm) + pH optimal - Dapat ditoleransi, tapi tingkatkan TDS'
            ];
        }
        
        // =====================================================================
        // RULE 7: POOR - Marginal TDS + Non-optimal pH (Synergistic Stressor)
        // IF TDS=MARGINAL AND (pH=ACIDIC OR pH=ALKALINE) THEN POOR
        // Combination of stressors is worse than individual
        // =====================================================================
        $rule7Strength = min(
            $this->fuzzifiedTds['MARGINAL'],
            max($this->fuzzifiedPh['ACIDIC'], $this->fuzzifiedPh['ALKALINE'])
        );
        if ($rule7Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 7,
                'strength' => $rule7Strength,
                'output' => 'POOR',
                'description' => 'TDS marginal + pH buruk - SINERGI NEGATIF! Mekanisme kompensasi fisiologis runtuh'
            ];
        }
        
        // =====================================================================
        // RULE 8: MODERATE - Optimal TDS + Clear Water
        // IF TDS=OPTIMAL AND pH=OPTIMAL AND Turbidity=CLEAR THEN MODERATE
        // Good water chemistry but insufficient plankton
        // =====================================================================
        $rule8Strength = min(
            $this->fuzzifiedTds['OPTIMAL'],
            $this->fuzzifiedPh['OPTIMAL'],
            $this->fuzzifiedTurbidity['CLEAR']
        );
        if ($rule8Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 8,
                'strength' => $rule8Strength,
                'output' => 'MODERATE',
                'description' => 'Air terlalu jernih - kurang plankton sebagai pakan alami'
            ];
        }
        
        // =====================================================================
        // RULE 9: MODERATE - Optimal TDS/pH but Turbid Water
        // IF TDS=OPTIMAL AND pH=OPTIMAL AND Turbidity=TURBID THEN MODERATE
        // Good chemistry but gill clogging risk
        // =====================================================================
        $rule9Strength = min(
            $this->fuzzifiedTds['OPTIMAL'],
            $this->fuzzifiedPh['OPTIMAL'],
            $this->fuzzifiedTurbidity['TURBID']
        );
        if ($rule9Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 9,
                'strength' => $rule9Strength,
                'output' => 'MODERATE',
                'description' => 'Air terlalu keruh (>60 NTU) - risiko penyumbatan insang'
            ];
        }
        
        // =====================================================================
        // RULE 10: POOR - Low TDS + Turbid (Compounded Gill Stress)
        // IF TDS=LOW AND Turbidity=TURBID THEN POOR (Amplified)
        // Gills already stressed for osmoregulation + physical clogging
        // =====================================================================
        $rule10Strength = min(
            $this->fuzzifiedTds['LOW'],
            $this->fuzzifiedTurbidity['TURBID']
        );
        if ($rule10Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 10,
                'strength' => $rule10Strength,
                'output' => 'POOR',
                'description' => 'TDS rendah + Air keruh - KRITIS! Insang sudah bekerja keras untuk osmoregulasi, sekarang tersumbat partikel'
            ];
        }
        
        // =====================================================================
        // RULE 11: MODERATE - Optimal TDS, slightly off pH
        // IF TDS=OPTIMAL AND (partial ACIDIC or partial ALKALINE) THEN MODERATE
        // Catches edge cases near pH boundaries
        // =====================================================================
        $phEdgeCase = max(
            min($this->fuzzifiedPh['ACIDIC'], 0.5),
            min($this->fuzzifiedPh['ALKALINE'], 0.5)
        );
        $rule11Strength = min($this->fuzzifiedTds['OPTIMAL'], $phEdgeCase);
        if ($rule11Strength > 0) {
            $this->evaluatedRules[] = [
                'id' => 11,
                'strength' => $rule11Strength,
                'output' => 'MODERATE',
                'description' => 'TDS optimal tapi pH mendekati batas - monitor ketat'
            ];
        }
        
        // =====================================================================
        // AGGREGATE OUTPUT (Max Method)
        // Take highest firing strength for each output set
        // =====================================================================
        foreach ($this->evaluatedRules as $rule) {
            $output = $rule['output'];
            if ($rule['strength'] > $this->rulesOutput[$output]) {
                $this->rulesOutput[$output] = $rule['strength'];
            }
        }
    }

    // =========================================================================
    // DEFUZZIFICATION - CENTROID METHOD
    // =========================================================================
    
    /**
     * Defuzzification using Centroid (Center of Gravity) Method
     * 
     * Output membership functions (triangular):
     * - POOR: (0, 0, 40) - Low quality area
     * - MODERATE: (30, 50, 70) - Medium quality area
     * - EXCELLENT: (60, 100, 100) - High quality area
     * 
     * Formula: COG = Σ(x * μ(x)) / Σ(μ(x))
     * 
     * @return float Crisp output score (0-100)
     */
    private function defuzzify(): float
    {
        $step = 1; // Integration resolution
        $numerator = 0.0;   // Sum of x * mu
        $denominator = 0.0; // Sum of mu
        
        for ($i = 0; $i <= 100; $i += $step) {
            // Calculate membership degree for output value i
            $muPoor = $this->triangle($i, 0, 0, 40);
            $muModerate = $this->triangle($i, 30, 50, 70);
            $muExcellent = $this->triangle($i, 60, 100, 100);
            
            // Clip output curves based on rule firing strength (Mamdani implication)
            $clippedPoor = min($muPoor, $this->rulesOutput['POOR']);
            $clippedModerate = min($muModerate, $this->rulesOutput['MODERATE']);
            $clippedExcellent = min($muExcellent, $this->rulesOutput['EXCELLENT']);
            
            // Aggregate (Union using Max)
            $aggregatedMu = max($clippedPoor, $clippedModerate, $clippedExcellent);
            
            // Accumulate moments
            $numerator += $i * $aggregatedMu;
            $denominator += $aggregatedMu;
        }
        
        // Avoid division by zero
        if ($denominator == 0) return 0.0;
        
        return round($numerator / $denominator, 2);
    }

    // =========================================================================
    // QUALITY LABELING
    // =========================================================================
    
    private function getQualityLabel(float $score): string
    {
        if ($score >= 80) return 'Sangat Baik (Excellent)';
        if ($score >= 60) return 'Baik (Good)';
        if ($score >= 40) return 'Cukup (Moderate)';
        if ($score >= 20) return 'Buruk (Poor)';
        return 'Kritis (Critical)';
    }
    
    private function getQualityCategory(float $score): string
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Moderate';
        if ($score >= 20) return 'Poor';
        return 'Critical';
    }

    // =========================================================================
    // RECOMMENDATION GENERATOR (Actionable Insights)
    // =========================================================================
    
    /**
     * Generate specific chemical/management recommendations
     * 
     * Provides actionable advice based on detected issues:
     * - Low TDS: Mineral supplementation (KCl, MgCl2, rock salt)
     * - Low pH: Lime application (CaCO3, Dolomite)
     * - High pH: Water exchange, molasses/probiotic application
     * - Turbidity issues: Water management recommendations
     * 
     * @param float $score Quality score
     * @param float $tds TDS value
     * @param float $ph pH value
     * @param float $turbidity Turbidity value
     * @return string Detailed recommendation
     */
    private function generateRecommendation(float $score, float $tds, float $ph, float $turbidity): string
    {
        $recommendations = [];
        $urgency = '';
        
        // =====================================================================
        // TDS RECOMMENDATIONS (Osmoregulatory Cost Management)
        // =====================================================================
        if ($tds < 800) {
            $urgency = '🚨 DARURAT! ';
            $recommendations[] = sprintf(
                "TDS KRITIS (%.0f ppm) - Jauh di bawah ambang batas 1000 ppm! " .
                "Biaya osmoregulasi MAKSIMAL. SEGERA tambahkan: " .
                "(1) Garam krosok untuk menaikkan TDS, " .
                "(2) KCl (Kalium Klorida) untuk rasio Na:K optimal (28:1), " .
                "(3) MgCl2 atau Dolomit untuk Magnesium (rasio Mg:Ca ~3.4:1). " .
                "Target: >1000 ppm minimum, ideal 1200+ ppm.",
                $tds
            );
        } elseif ($tds < 1000) {
            $recommendations[] = sprintf(
                "⚠️ TDS RENDAH (%.0f ppm) - Di bawah ambang batas optimal! " .
                "Tambahkan mineral (KCl, MgCl2) dan garam krosok untuk mencapai minimal 1000 ppm. " .
                "Salinitas saat ini: %.2f ppt (target >1 ppt).",
                $tds, $this->calculatedSalinity
            );
        } elseif ($tds < 1200) {
            $recommendations[] = sprintf(
                "TDS MARGINAL (%.0f ppm) - Zona transisi. Pertimbangkan peningkatan ke 1200+ ppm " .
                "untuk mengurangi beban osmoregulasi udang.",
                $tds
            );
        }
        
        // =====================================================================
        // pH RECOMMENDATIONS (Acid-Base Balance)
        // =====================================================================
        if ($ph < 7.0) {
            $urgency = '🚨 DARURAT! ';
            $recommendations[] = sprintf(
                "pH SANGAT ASAM (%.2f) - Risiko asidosis akut! " .
                "SEGERA aplikasikan kapur pertanian (CaCO3) atau Dolomit. " .
                "Dosis awal: 20-30 kg/ha. Monitor setiap 2 jam.",
                $ph
            );
        } elseif ($ph < 7.5) {
            $recommendations[] = sprintf(
                "⚠️ pH ASAM (%.2f) - Risiko asidosis hemolimfa, molting gagal, melanosis. " .
                "Aplikasikan kapur pertanian (CaCO3) atau Dolomit. " .
                "Jaga alkalinitas >80-100 ppm CaCO3 untuk buffer stabil.",
                $ph
            );
        } elseif ($ph > 9.0) {
            $urgency = '🚨 DARURAT! ';
            $recommendations[] = sprintf(
                "pH SANGAT BASA (%.2f) - TOKSISITAS AMONIA AKUT! " .
                "Pada pH ini, NH3 (beracun) bisa mencapai 30-50%% dari Total Ammonia Nitrogen! " .
                "SEGERA: (1) Pergantian air parsial 30-50%%, " .
                "(2) Stop feeding, (3) Aplikasi molase/probiotik untuk menekan pH, " .
                "(4) Nyalakan aerator maksimal untuk volatilisasi NH3.",
                $ph
            );
        } elseif ($ph > 8.5) {
            $recommendations[] = sprintf(
                "⚠️ pH BASA (%.2f) - Risiko keracunan Amonia (NH3)! " .
                "Lakukan pergantian air parsial, aplikasi molase/probiotik. " .
                "Hindari pemupukan urea. Monitor TAN (Total Ammonia Nitrogen).",
                $ph
            );
        }
        
        // =====================================================================
        // TURBIDITY RECOMMENDATIONS
        // =====================================================================
        if ($turbidity < 15) {
            $recommendations[] = sprintf(
                "Air terlalu JERNIH (%.1f NTU) - Kurang plankton sebagai pakan alami. " .
                "Pertimbangkan pemupukan untuk merangsang pertumbuhan fitoplankton.",
                $turbidity
            );
        } elseif ($turbidity > 60) {
            $recommendations[] = sprintf(
                "Air terlalu KERUH (%.1f NTU) - Risiko penyumbatan insang! " .
                "Terutama berbahaya jika TDS rendah (insang sudah bekerja keras untuk osmoregulasi). " .
                "Lakukan sedimentasi atau pergantian air.",
                $turbidity
            );
        }
        
        // =====================================================================
        // SYNERGISTIC STRESSOR WARNING
        // =====================================================================
        if ($tds < 1000 && ($ph < 7.5 || $ph > 8.5)) {
            $recommendations[] = "⚠️ SINERGI NEGATIF: TDS rendah + pH tidak optimal = " .
                "Mekanisme kompensasi fisiologis udang terancam runtuh! Prioritaskan koreksi segera.";
        }
        
        // =====================================================================
        // POSITIVE FEEDBACK
        // =====================================================================
        if (empty($recommendations)) {
            if ($score >= 80) {
                return "✅ KONDISI OPTIMAL! Semua parameter dalam rentang ideal. " .
                    "Pertahankan feeding regime dan monitoring rutin. " .
                    "Salinitas: " . $this->calculatedSalinity . " ppt.";
            } elseif ($score >= 60) {
                return "✓ Kondisi BAIK. Parameter dalam batas toleransi. " .
                    "Lanjutkan monitoring untuk deteksi dini perubahan.";
            } else {
                return "Parameter dalam batas toleransi, namun perhatikan tren perubahan kualitas air.";
            }
        }
        
        return $urgency . implode(" | ", $recommendations);
    }

    // =========================================================================
    // MAIN EVALUATION FUNCTION - PUBLIC API
    // =========================================================================
    
    /**
     * Evaluate Water Quality using Fuzzy Mamdani Inference
     * 
     * Main entry point for water quality analysis. Implements complete
     * Fuzzy Mamdani pipeline: Fuzzification → Inference → Defuzzification
     * 
     * @param float $phValue pH measurement
     * @param float $tdsValue TDS in ppm (mg/L)
     * @param float $turbidityValue Turbidity in NTU
     * @param float $temperature Temperature in °C (optional, for future use)
     * @return array Complete analysis results
     */
    public function evaluateWaterQuality(float $phValue, float $tdsValue, float $turbidityValue, float $temperature = 25): array
    {
        // Store input values
        $this->inputPh = $phValue;
        $this->inputTds = $tdsValue;
        $this->inputTurbidity = $turbidityValue;
        
        // Step 1: Calculate derived metrics
        $this->calculatedSalinity = $this->calculateSalinity($tdsValue);
        
        // Step 2: Fuzzification (Crisp → Fuzzy)
        $this->fuzzifyTds($tdsValue);
        $this->fuzzifyPh($phValue);
        $this->fuzzifyTurbidity($turbidityValue);
        
        // Step 3: Rule Evaluation (Inference Engine)
        $this->evaluateRules();
        
        // Step 4: Defuzzification (Fuzzy → Crisp)
        $score = $this->defuzzify();
        
        // Get dominant rule for reporting
        $dominantRule = null;
        $maxStrength = 0;
        foreach ($this->evaluatedRules as $rule) {
            if ($rule['strength'] > $maxStrength) {
                $maxStrength = $rule['strength'];
                $dominantRule = $rule;
            }
        }
        
        // Build fuzzy details string
        $fuzzyDetails = sprintf(
            "pH: %.2f (Asam: %.2f, Optimal: %.2f, Basa: %.2f) | " .
            "TDS: %.0f ppm (Rendah: %.2f, Marginal: %.2f, Optimal: %.2f) | " .
            "Turbidity: %.1f NTU (Jernih: %.2f, Optimal: %.2f, Keruh: %.2f) | " .
            "Salinity: %.2f ppt | " .
            "Output Strength (Poor: %.2f, Moderate: %.2f, Excellent: %.2f) | " .
            "Active Rules: %d",
            $phValue, 
            $this->fuzzifiedPh['ACIDIC'], 
            $this->fuzzifiedPh['OPTIMAL'], 
            $this->fuzzifiedPh['ALKALINE'],
            $tdsValue, 
            $this->fuzzifiedTds['LOW'], 
            $this->fuzzifiedTds['MARGINAL'], 
            $this->fuzzifiedTds['OPTIMAL'],
            $turbidityValue, 
            $this->fuzzifiedTurbidity['CLEAR'], 
            $this->fuzzifiedTurbidity['OPTIMAL'], 
            $this->fuzzifiedTurbidity['TURBID'],
            $this->calculatedSalinity,
            $this->rulesOutput['POOR'],
            $this->rulesOutput['MODERATE'],
            $this->rulesOutput['EXCELLENT'],
            count($this->evaluatedRules)
        );
        
        // Generate recommendation
        $recommendation = $this->generateRecommendation($score, $tdsValue, $phValue, $turbidityValue);
        
        return [
            // Primary outputs
            'water_quality_score' => $score,
            'water_quality_status' => $this->getQualityLabel($score),
            'category' => $this->getQualityCategory($score),
            
            // Derived metrics
            'salinity_ppt' => $this->calculatedSalinity,
            
            // Actionable insight
            'recommendation' => $recommendation,
            
            // Fuzzy analysis details
            'fuzzy_details' => $fuzzyDetails,
            'rule_strength' => $maxStrength,
            'active_rules_count' => count($this->evaluatedRules),
            'dominant_rule' => $dominantRule ? $dominantRule['description'] : 'No rules fired',
            
            // Raw membership values for debugging/visualization
            'membership' => [
                'ph' => $this->fuzzifiedPh,
                'tds' => $this->fuzzifiedTds,
                'turbidity' => $this->fuzzifiedTurbidity,
            ],
            'output_aggregation' => $this->rulesOutput,
            'evaluated_rules' => $this->evaluatedRules,
        ];
    }
    
    /**
     * Simplified analysis method (matches reference document signature)
     * 
     * @param float $tds TDS in ppm
     * @param float $ph pH value
     * @param float $turbidity Turbidity in NTU (optional)
     * @return array Analysis results
     */
    public function analyze(float $tds, float $ph, float $turbidity = 30): array
    {
        return $this->evaluateWaterQuality($ph, $tds, $turbidity);
    }
}