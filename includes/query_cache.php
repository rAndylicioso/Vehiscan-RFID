<?php
/**
 * Query Cache System
 * Uses APCu if available, falls back to in-memory array
 * 
 * Usage:
 * $data = QueryCache::get('key');
 * if (!$data) {
 *     $data = fetchFromDatabase();
 *     QueryCache::set('key', $data, 300); // Cache for 5 minutes
 * }
 */

class QueryCache
{
    private static $cache = [];
    private static $enabled = true;
    private static $stats = ['hits' => 0, 'misses' => 0, 'sets' => 0];

    /**
     * Check if APCu is available
     */
    public static function isAPCuAvailable()
    {
        return function_exists('apcu_fetch') && ini_get('apc.enabled');
    }

    /**
     * Get cached value
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    public static function get($key)
    {
        if (!self::$enabled) {
            return null;
        }

        if (self::isAPCuAvailable()) {
            $value = apcu_fetch($key, $success);
            if ($success) {
                self::$stats['hits']++;
                return $value;
            }
        } else {
            if (isset(self::$cache[$key])) {
                self::$stats['hits']++;
                return self::$cache[$key];
            }
        }

        self::$stats['misses']++;
        return null;
    }

    /**
     * Set cached value
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 300 = 5 minutes)
     * @return bool Success status
     */
    public static function set($key, $value, $ttl = 300)
    {
        if (!self::$enabled) {
            return false;
        }

        self::$stats['sets']++;

        if (self::isAPCuAvailable()) {
            return apcu_store($key, $value, $ttl);
        }

        self::$cache[$key] = $value;
        return true;
    }

    /**
     * Delete cached value
     * @param string $key Cache key (supports wildcards with *)
     * @return bool Success status
     */
    public static function delete($key)
    {
        if (self::isAPCuAvailable()) {
            // Handle wildcard deletion
            if (strpos($key, '*') !== false) {
                $pattern = '/^' . str_replace('*', '.*', preg_quote($key, '/')) . '$/';
                $iterator = new APCUIterator($pattern);
                foreach ($iterator as $entry) {
                    apcu_delete($entry['key']);
                }
                return true;
            }
            return apcu_delete($key);
        }

        // Handle wildcard deletion for in-memory cache
        if (strpos($key, '*') !== false) {
            $pattern = '/^' . str_replace('*', '.*', preg_quote($key, '/')) . '$/';
            foreach (array_keys(self::$cache) as $cacheKey) {
                if (preg_match($pattern, $cacheKey)) {
                    unset(self::$cache[$cacheKey]);
                }
            }
            return true;
        }

        unset(self::$cache[$key]);
        return true;
    }

    /**
     * Clear all cache
     * @return bool Success status
     */
    public static function clear()
    {
        if (self::isAPCuAvailable()) {
            return apcu_clear_cache();
        }

        self::$cache = [];
        return true;
    }

    /**
     * Enable cache
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * Disable cache
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Get cache statistics
     * @return array Statistics array
     */
    public static function getStats()
    {
        $stats = self::$stats;
        $total = $stats['hits'] + $stats['misses'];
        $stats['hit_rate'] = $total > 0 ? round(($stats['hits'] / $total) * 100, 2) : 0;
        $stats['backend'] = self::isAPCuAvailable() ? 'APCu' : 'In-Memory';
        return $stats;
    }

    /**
     * Reset statistics
     */
    public static function resetStats()
    {
        self::$stats = ['hits' => 0, 'misses' => 0, 'sets' => 0];
    }
}
?>