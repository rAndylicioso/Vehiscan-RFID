<?php
/**
 * VehiScan-RFID Helper Functions
 */

// Rate limiting for login
function checkLoginAttempts($username) {
    $lockout_file = sys_get_temp_dir() . '/login_attempts_' . md5($username);
    
    if (file_exists($lockout_file)) {
        $data = json_decode(file_get_contents($lockout_file), true);
        
        if ($data['attempts'] >= 5 && (time() - $data['first_attempt']) < 900) {
            return ['allowed' => false, 'wait' => 900 - (time() - $data['first_attempt'])];
        }
        
        if ((time() - $data['first_attempt']) >= 900) {
            unlink($lockout_file);
            return ['allowed' => true];
        }
    }
    
    return ['allowed' => true];
}

function recordLoginAttempt($username, $success) {
    $lockout_file = sys_get_temp_dir() . '/login_attempts_' . md5($username);
    
    if ($success) {
        if (file_exists($lockout_file)) {
            unlink($lockout_file);
        }
        return;
    }
    
    if (file_exists($lockout_file)) {
        $data = json_decode(file_get_contents($lockout_file), true);
        $data['attempts']++;
    } else {
        $data = ['attempts' => 1, 'first_attempt' => time()];
    }
    
    file_put_contents($lockout_file, json_encode($data));
}