<?php
class AICA_Logger {
    private $log_file;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/aica_logs.json';
    }

    public function log($message, $level = 'info', $context = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown',
            'username' => is_user_logged_in() ? wp_get_current_user()->user_login : 'Guest',
            'user_id' => get_current_user_id(),
            'guest_id' => isset($context['guest_id']) ? $context['guest_id'] : '',
            'token_usage' => isset($context['token_usage']) ? $context['token_usage'] : 0,
            'context' => $context,
        );

        $logs = $this->get_logs();
        $logs[] = $log_entry;
        file_put_contents($this->log_file, json_encode($logs));
    }

    public function get_logs() {
        if (file_exists($this->log_file)) {
            $logs = json_decode(file_get_contents($this->log_file), true);
            return is_array($logs) ? $logs : array();
        }
        return array();
    }

    public function clear_logs() {
        file_put_contents($this->log_file, json_encode(array()));
    }

    public function rotate_logs() {
        $logs = $this->get_logs();
        $current_date = current_time('Y-m-d');
        $logs_to_keep = array();

        foreach ($logs as $log) {
            if (strpos($log['timestamp'], $current_date) === 0) {
                $logs_to_keep[] = $log;
            }
        }

        file_put_contents($this->log_file, json_encode($logs_to_keep));
    }

    public function anonymize_logs_by_user_id($user_id) {
        $logs = $this->get_logs();
        $anonymized_logs = array_map(function($log) use ($user_id) {
            if ($log['user_id'] == $user_id) {
                $log['ip_address'] = 'anonymized';
                $log['username'] = 'anonymized';
                $log['user_id'] = 0;
                $log['guest_id'] = '';
                // Remove any other personal information from context
                unset($log['context']['user_message']);
                unset($log['context']['ai_response']);
            }
            return $log;
        }, $logs);
        $success = file_put_contents($this->log_file, json_encode($anonymized_logs)) !== false;
        return $success;
    }

    public function anonymize_logs_by_ip($ip_address) {
        $logs = $this->get_logs();
        $anonymized_logs = array_map(function($log) use ($ip_address) {
            if ($log['ip_address'] == $ip_address) {
                $log['ip_address'] = 'anonymized';
                $log['username'] = 'anonymized';
                $log['user_id'] = 0;
                $log['guest_id'] = '';
                // Remove any other personal information from context
                unset($log['context']['user_message']);
                unset($log['context']['ai_response']);
            }
            return $log;
        }, $logs);
        $success = file_put_contents($this->log_file, json_encode($anonymized_logs)) !== false;
        return $success;
    }
    
    public function anonymize_logs_by_guest_id($guest_id) {
        $logs = $this->get_logs();
        $anonymized_logs = array_map(function($log) use ($guest_id) {
            if ($log['guest_id'] == $guest_id) {
                $log['ip_address'] = 'anonymized';
                $log['username'] = 'anonymized';
                $log['user_id'] = 0;
                $log['guest_id'] = '';
                // Remove any other personal information from context
                unset($log['context']['user_message']);
                unset($log['context']['ai_response']);
            }
            return $log;
        }, $logs);
        $success = file_put_contents($this->log_file, json_encode($anonymized_logs)) !== false;
        return $success;
    }
    
    public function delete_logs_by_timestamp($timestamp) {
        $logs = $this->get_logs();
        $filtered_logs = array_filter($logs, function($log) use ($timestamp) {
            return strtotime($log['timestamp']) > strtotime($timestamp);
        });
        $success = file_put_contents($this->log_file, json_encode(array_values($filtered_logs)));
        return $success;
    }

    public function delete_old_logs($retention_period) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_period days"));
        $this->delete_logs_by_timestamp($cutoff_date);
    }

    public function get_token_usage($start_date = null, $end_date = null) {
        $logs = $this->get_logs();
        $total_tokens = 0;

        foreach ($logs as $log) {
            if ((!$start_date || strtotime($log['timestamp']) >= strtotime($start_date)) &&
                (!$end_date || strtotime($log['timestamp']) <= strtotime($end_date))) {
                $total_tokens += $log['token_usage'];
            }
        }

        return $total_tokens;
    }
}