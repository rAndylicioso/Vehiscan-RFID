<?php
/**
 * Cache Invalidation Helper
 * Provides functions to invalidate cache when data changes
 */

require_once __DIR__ . '/query_cache.php';

class CacheInvalidator
{
    /**
     * Invalidate dashboard stats cache
     * Call this when logs, homeowners, or passes are added/updated
     */
    public static function invalidateDashboard()
    {
        QueryCache::delete('dashboard_stats_*');
    }

    /**
     * Invalidate logs cache
     * Call this when new logs are added
     */
    public static function invalidateLogs()
    {
        QueryCache::delete('access_logs_*');
        QueryCache::delete('recent_logs_*');
        self::invalidateDashboard(); // Dashboard shows log counts
    }

    /**
     * Invalidate visitor passes cache
     * Call this when passes are added/updated/approved/rejected
     */
    public static function invalidatePasses()
    {
        QueryCache::delete('visitor_passes_*');
        QueryCache::delete('pending_passes_*');
        self::invalidateDashboard(); // Dashboard shows pass counts
    }

    /**
     * Invalidate homeowner cache
     * Call this when homeowner data is updated
     * @param int|null $homeownerId Specific homeowner ID or null for all
     */
    public static function invalidateHomeowner($homeownerId = null)
    {
        if ($homeownerId) {
            QueryCache::delete("homeowner_data_{$homeownerId}");
            QueryCache::delete("homeowner_vehicles_{$homeownerId}");
        } else {
            QueryCache::delete('homeowner_*');
        }
        self::invalidateDashboard(); // Dashboard shows homeowner counts
    }

    /**
     * Invalidate all caches
     * Use sparingly - only for major data changes
     */
    public static function invalidateAll()
    {
        QueryCache::clear();
    }
}
?>