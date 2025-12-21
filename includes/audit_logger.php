<?php
/**
 * Enhanced Audit Logger
 * 
 * Comprehensive audit logging system for tracking all system activities
 * Logs: authentication, data changes, config changes, backup operations, security events
 * 
 * @version 1.0.0
 * @created 2025-11-20
 */

class AuditLogger {
    private static $pdo;
    private static $initialized = false;
    
    /**
     * Initialize the audit logger with database connection
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
        self::$initialized = true;
    }
    
    /**
     * Check if logger is initialized
     */
    private static function checkInit() {
        if (!self::$initialized || !self::$pdo) {
            throw new Exception('AuditLogger not initialized. Call AuditLogger::init($pdo) first.');
        }
    }
    
    /**
     * Main logging function
     */
    public static function log($eventType, $action, $data = []) {
        try {
            self::checkInit();
            
            $defaults = [
                'username' => $_SESSION['username'] ?? null,
                'user_role' => $_SESSION['role'] ?? null,
                'table_name' => null,
                'record_id' => null,
                'old_values' => null,
                'new_values' => null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
                'severity' => 'low',
                'status' => 'success',
                'error_message' => null,
                'session_id' => session_id() ?: null
            ];
            
            $data = array_merge($defaults, $data);
            
            // Convert arrays to JSON
            if (is_array($data['old_values'])) {
                $data['old_values'] = json_encode($data['old_values']);
            }
            if (is_array($data['new_values'])) {
                $data['new_values'] = json_encode($data['new_values']);
            }
            
            $stmt = self::$pdo->prepare("
                INSERT INTO audit_logs_enhanced (
                    event_type, action, username, user_role, table_name, record_id,
                    old_values, new_values, ip_address, user_agent, request_method,
                    request_uri, severity, status, error_message, session_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $eventType,
                $action,
                $data['username'],
                $data['user_role'],
                $data['table_name'],
                $data['record_id'],
                $data['old_values'],
                $data['new_values'],
                $data['ip_address'],
                $data['user_agent'],
                $data['request_method'],
                $data['request_uri'],
                $data['severity'],
                $data['status'],
                $data['error_message'],
                $data['session_id']
            ]);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log authentication events (login, logout, failed attempts)
     */
    public static function logAuth($action, $success, $username = null) {
        return self::log('auth', $action, [
            'username' => $username ?? $_SESSION['username'] ?? 'unknown',
            'severity' => $success ? 'low' : 'medium',
            'status' => $success ? 'success' : 'failure'
        ]);
    }
    
    /**
     * Log data changes (create, update, delete)
     */
    public static function logDataChange($action, $table, $recordId, $oldValues = null, $newValues = null) {
        return self::log('data', $action, [
            'table_name' => $table,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'severity' => 'low'
        ]);
    }
    
    /**
     * Log configuration changes
     */
    public static function logConfig($action, $setting, $oldValue = null, $newValue = null) {
        return self::log('config', $action, [
            'table_name' => 'security_settings',
            'old_values' => ['setting' => $setting, 'value' => $oldValue],
            'new_values' => ['setting' => $setting, 'value' => $newValue],
            'severity' => 'medium'
        ]);
    }
    
    /**
     * Log backup operations
     */
    public static function logBackup($action, $backupSize = null, $success = true, $errorMsg = null) {
        return self::log('backup', $action, [
            'new_values' => ['backup_size' => $backupSize],
            'severity' => 'medium',
            'status' => $success ? 'success' : 'failure',
            'error_message' => $errorMsg
        ]);
    }
    
    /**
     * Log security events (suspicious activity, policy violations)
     */
    public static function logSecurity($action, $severity = 'high', $details = []) {
        return self::log('security', $action, array_merge([
            'severity' => $severity,
            'status' => 'warning'
        ], $details));
    }
    
    /**
     * Log employee management actions
     */
    public static function logEmployee($action, $employeeId, $details = []) {
        return self::log('employee', $action, array_merge([
            'table_name' => 'users',
            'record_id' => $employeeId,
            'severity' => 'low'
        ], $details));
    }
    
    /**
     * Get recent audit logs
     */
    public static function getRecentLogs($limit = 100, $eventType = null) {
        try {
            self::checkInit();
            
            $sql = "SELECT * FROM audit_logs_enhanced";
            $params = [];
            
            if ($eventType) {
                $sql .= " WHERE event_type = ?";
                $params[] = $eventType;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = (int)$limit;
            
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get logs by user
     */
    public static function getLogsByUser($username, $limit = 100) {
        try {
            self::checkInit();
            
            $stmt = self::$pdo->prepare("
                SELECT * FROM audit_logs_enhanced 
                WHERE username = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$username, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get logs by severity
     */
    public static function getLogsBySeverity($severity, $limit = 100) {
        try {
            self::checkInit();
            
            $stmt = self::$pdo->prepare("
                SELECT * FROM audit_logs_enhanced 
                WHERE severity = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$severity, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get failed login attempts
     */
    public static function getFailedLogins($hours = 24, $limit = 100) {
        try {
            self::checkInit();
            
            $stmt = self::$pdo->prepare("
                SELECT * FROM audit_logs_enhanced 
                WHERE event_type = 'auth' 
                AND status = 'failure' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([(int)$hours, (int)$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics
     */
    public static function getStats($days = 7) {
        try {
            self::checkInit();
            
            $stmt = self::$pdo->prepare("
                SELECT 
                    event_type,
                    status,
                    COUNT(*) as count
                FROM audit_logs_enhanced
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY event_type, status
                ORDER BY event_type, status
            ");
            $stmt->execute([(int)$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("AuditLogger Error: " . $e->getMessage());
            return [];
        }
    }
}
