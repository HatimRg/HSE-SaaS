<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyHeadcount extends BaseModel
{
    protected $table = 'daily_headcounts';

    protected $fillable = [
        'company_id',
        'project_id',
        'user_id',
        'date',
        'men_count',
        'women_count',
        'total_count',
        'contractor_count',
        'visitor_count',
        'shift',
        'notes',
        'weather_conditions',
        'temperature',
    ];

    protected $casts = [
        'date' => 'date',
        'men_count' => 'integer',
        'women_count' => 'integer',
        'total_count' => 'integer',
        'contractor_count' => 'integer',
        'visitor_count' => 'integer',
        'temperature' => 'decimal:1',
    ];

    /**
     * Get the project.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the reporter.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Calculate total from men/women counts.
     */
    public function calculateTotal(): void
    {
        $this->total_count = ($this->men_count ?? 0) + ($this->women_count ?? 0);
    }

    /**
     * Get shifts.
     */
    public static function getShifts(): array
    {
        return [
            'day' => 'Day Shift (Jour)',
            'night' => 'Night Shift (Nuit)',
            'evening' => 'Evening Shift (Soir)',
        ];
    }

    /**
     * Get weather options.
     */
    public static function getWeatherConditions(): array
    {
        return [
            'sunny' => 'Sunny (Ensoleillé)',
            'cloudy' => 'Cloudy (Nuageux)',
            'rainy' => 'Rainy (Pluvieux)',
            'windy' => 'Windy (Venteux)',
            'stormy' => 'Stormy (Orageux)',
            'foggy' => 'Foggy (Brumeux)',
            'snowy' => 'Snowy (Neigeux)',
        ];
    }

    /**
     * Scope: For date range.
     */
    public function scopeForDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    /**
     * Scope: For specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope: By shift.
     */
    public function scopeByShift($query, string $shift)
    {
        return $query->where('shift', $shift);
    }

    /**
     * Get aggregate statistics for a period.
     */
    public static function getPeriodStats(int $projectId, string $start, string $end): array
    {
        $stats = self::where('project_id', $projectId)
            ->whereBetween('date', [$start, $end])
            ->selectRaw('
                SUM(men_count) as total_men,
                SUM(women_count) as total_women,
                SUM(total_count) as total_workers,
                AVG(total_count) as avg_daily_workers,
                MAX(total_count) as max_daily_workers,
                MIN(total_count) as min_daily_workers,
                COUNT(DISTINCT date) as days_count
            ')
            ->first();

        return [
            'totalMen' => (int) $stats->total_men,
            'totalWomen' => (int) $stats->total_women,
            'totalWorkers' => (int) $stats->total_workers,
            'avgDailyWorkers' => round($stats->avg_daily_workers, 1),
            'maxDailyWorkers' => (int) $stats->max_daily_workers,
            'minDailyWorkers' => (int) $stats->min_daily_workers,
            'daysCount' => (int) $stats->days_count,
        ];
    }
}
