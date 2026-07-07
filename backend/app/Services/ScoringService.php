<?php

namespace App\Services;

class ScoringService
{
    // Composite score weights
    private const W_SLOPE      =  40.0;
    private const W_ACCEL      =  10.0;
    private const W_SMA_RATIO  =  20.0;
    private const W_VOLUME     =  10.0;
    private const W_SEASONAL   =  -5.0;
    private const W_VOLATILITY =  -5.0;

    // Status thresholds (Google Trends values 0-100, weekly)
    // slope 0.025 ≈ topic 4x'd over a year in log scale; 0.010 ≈ steady moderate growth
    private const SLOPE_EXPLODING    =  0.025;
    private const SLOPE_PEAKED       = -0.015;
    private const SMA_RATIO_EXPLODING = 1.05;
    private const MIN_DATA_RATIO     =  0.20;  // < 20% points > 2 → noise

    /**
     * Score a topic from its time-series points (array of {ts, value}).
     * Returns: status, score, growth_3m, growth_6m, growth_12m
     */
    public function score(array $points, ?int $volume = null): array
    {
        $values = array_values(array_map(fn($p) => (float)($p['value'] ?? 0), $points));
        $n = count($values);

        // Gate: not enough data
        $nonZero = count(array_filter($values, fn($v) => $v > 2));
        if ($n < 8 || $nonZero < $n * self::MIN_DATA_RATIO) {
            return $this->noiseResult();
        }

        $smoothed = $this->movingAverage($values, 4);
        $meanSmooth = array_sum($smoothed) / count($smoothed);
        if ($meanSmooth < 1.0) {
            return $this->noiseResult();
        }

        $slope      = $this->logLinearSlope(array_slice($smoothed, -12));
        $fullSlope  = $this->logLinearSlope($smoothed);
        $accel      = $this->acceleration($smoothed);
        $smaRatio   = $this->smaRatio($smoothed);
        $volatility = $this->volatility($smoothed);
        $seasonal   = $this->seasonality($values);
        $eventDriven = $this->isEventDriven($smoothed);
        $recentAvg  = array_sum(array_slice($smoothed, -4)) / 4;

        $status = $this->computeStatus($slope, $fullSlope, $smaRatio, $eventDriven, $recentAvg);
        $score  = $this->computeScore($slope, $accel, $smaRatio, $volume ?? 0, $seasonal, $volatility);

        return [
            'status'     => $status,
            'score'      => round($score, 4),
            'growth_3m'  => round($this->growthPct($smoothed, 13), 2),
            'growth_6m'  => round($this->growthPct($smoothed, 26), 2),
            'growth_12m' => round($this->growthPct($smoothed, 52), 2),
        ];
    }

    // ── Core math ─────────────────────────────────────────────────────────────

    private function movingAverage(array $values, int $window): array
    {
        $result = [];
        foreach ($values as $i => $_) {
            $slice    = array_slice($values, max(0, $i - $window + 1), min($i + 1, $window));
            $result[] = array_sum($slice) / count($slice);
        }
        return $result;
    }

    private function logLinearSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0.0;

        $logY  = array_map(fn($v) => log(max($v, 0.01)), $values);
        $xs    = range(0, $n - 1);
        $sumX  = array_sum($xs);
        $sumY  = array_sum($logY);
        $sumXY = 0.0;
        $sumX2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $xs[$i] * $logY[$i];
            $sumX2 += $xs[$i] ** 2;
        }

        $denom = $n * $sumX2 - $sumX ** 2;
        return abs($denom) < 1e-10 ? 0.0 : ($n * $sumXY - $sumX * $sumY) / $denom;
    }

    private function acceleration(array $smoothed): float
    {
        $n = count($smoothed);
        if ($n < 6) return 0.0;

        $recent = array_slice($smoothed, max(0, $n - 6));
        $prev   = array_slice($smoothed, max(0, $n - 12), 6);

        return count($prev) >= 2
            ? $this->logLinearSlope($recent) - $this->logLinearSlope($prev)
            : 0.0;
    }

    private function smaRatio(array $smoothed, int $short = 4, int $long = 12): float
    {
        $n     = count($smoothed);
        $sShort = array_sum(array_slice($smoothed, max(0, $n - $short))) / $short;
        $sLong  = array_sum(array_slice($smoothed, max(0, $n - $long)))  / $long;
        return $sLong > 0 ? $sShort / $sLong : 1.0;
    }

    private function growthPct(array $smoothed, int $lookback): float
    {
        $n        = count($smoothed);
        $lookback = min($lookback, $n);
        if ($lookback < 2) return 0.0;

        $recent  = array_slice($smoothed, -4);
        $old     = array_slice($smoothed, max(0, $n - $lookback), 4);
        $meanR   = array_sum($recent) / max(1, count($recent));
        $meanO   = array_sum($old)    / max(1, count($old));

        return $meanO > 0.5 ? ($meanR - $meanO) / $meanO * 100 : 0.0;
    }

    private function volatility(array $smoothed): float
    {
        $n    = count($smoothed);
        $mean = array_sum($smoothed) / $n;
        if ($mean < 0.5) return 1.0;

        $variance = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $smoothed)) / $n;
        return sqrt($variance) / $mean;
    }

    private function seasonality(array $values): float
    {
        $n   = count($values);
        $lag = 52;
        if ($n < $lag + 4) return 0.0;

        $x   = array_slice($values, 0, $n - $lag);
        $y   = array_slice($values, $lag);
        $len = min(count($x), count($y));

        return $len >= 4 ? max(0, $this->pearsonCorr(array_slice($x, 0, $len), array_slice($y, 0, $len))) : 0.0;
    }

    private function pearsonCorr(array $x, array $y): float
    {
        $n    = count($x);
        $mX   = array_sum($x) / $n;
        $mY   = array_sum($y) / $n;
        $cov  = $varX = $varY = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dx   = $x[$i] - $mX;
            $dy   = $y[$i] - $mY;
            $cov  += $dx * $dy;
            $varX += $dx ** 2;
            $varY += $dy ** 2;
        }

        $denom = sqrt($varX * $varY);
        return $denom > 0 ? $cov / $denom : 0.0;
    }

    private function isEventDriven(array $smoothed): bool
    {
        $n      = count($smoothed);
        if ($n < 8) return false;

        $maxVal = max($smoothed);
        $maxIdx = array_search($maxVal, $smoothed);
        $mean   = array_sum($smoothed) / $n;
        $std    = sqrt(array_sum(array_map(fn($v) => ($v - $mean) ** 2, $smoothed)) / $n);

        if ($std < 0.5) return false;

        $zScore      = ($maxVal - $mean) / $std;
        $currentMean = array_sum(array_slice($smoothed, -4)) / 4;

        // Big spike, peak happened recently, current level collapsed
        return $zScore > 2.5 && $maxIdx > ($n * 0.6) && $currentMean < $maxVal * 0.5;
    }

    // ── Status & score ────────────────────────────────────────────────────────

    private function computeStatus(
        float $slope,
        float $fullSlope,
        float $smaRatio,
        bool  $eventDriven,
        float $recentAvg,
    ): string {
        // "Peaked" wins when the full-series or recent trend is clearly declining
        if ($slope < self::SLOPE_PEAKED || $fullSlope < self::SLOPE_PEAKED) {
            return 'peaked';
        }

        // "Exploding" requirements:
        // 1. Recent slope strong enough
        // 2. Short SMA above long SMA (momentum)
        // 3. Not a one-off event spike that already subsided
        // 4. Overall series not declining (fullSlope ≥ 0)
        // 5. Recent absolute level ≥ 15 (real interest, not noise recovery)
        if (
            $slope > self::SLOPE_EXPLODING
            && $smaRatio > self::SMA_RATIO_EXPLODING
            && !$eventDriven
            && $fullSlope >= 0.0
            && $recentAvg >= 15.0
        ) {
            return 'exploding';
        }

        return 'regular';
    }

    private function computeScore(
        float $slope,
        float $accel,
        float $smaRatio,
        int   $volume,
        float $seasonal,
        float $volatility,
    ): float {
        return self::W_SLOPE     * $slope
             + self::W_ACCEL     * $accel
             + self::W_SMA_RATIO * ($smaRatio - 1.0)
             + self::W_VOLUME    * (log($volume + 1) / 15.0)
             + self::W_SEASONAL  * $seasonal
             + self::W_VOLATILITY * $volatility;
    }

    private function noiseResult(): array
    {
        return ['status' => 'noise', 'score' => -1.0, 'growth_3m' => 0.0, 'growth_6m' => 0.0, 'growth_12m' => 0.0];
    }
}
