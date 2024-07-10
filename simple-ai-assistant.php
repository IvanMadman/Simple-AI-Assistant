<?php
/**
Plugin Name: Simple AI Assistant
Plugin URI: https://github.com/ivanmadman/Simple-AI-Assistant
Description: A plugin to provide a chat on your website with the actual page passed as context
Version: 1.0.0
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.5
Author: Ivanmadman
Author URI: https://github.com/IvanMadman
Text domain: simple-ai-assistant
License: GPL2
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/logger.php';
// Add new option for consent
add_option('aica_require_consent', 'yes');
// Add new option for data retention period (in days)
add_option('aica_data_retention_period', 30);

// Initialize logger

$aica_logger = new AICA_Logger();

// Enqueue necessary scripts and styles
function aica_enqueue_scripts() {
    if (!aica_should_display_chat()) {
        return;
    }

    wp_enqueue_script('aica-script', plugin_dir_url(__FILE__) . 'js/aica-script.js', array('jquery'), '1.4', true);
    wp_enqueue_style('aica-style', plugin_dir_url(__FILE__) . 'css/aica-style.css');
    
    // Pass the current page content and settings to JavaScript
    wp_localize_script('aica-script', 'aicaData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aica_chat_nonce'),
        'pageContent' => wp_strip_all_tags(get_the_content()),
        'pageUrl' => get_permalink(),
        'chatTitle' => get_option('aica_chat_title', 'Simple AI Assistant'),
        'chatBubbleText' => get_option('aica_chat_bubble_text', 'Chat'),
        'chatBubbleColor' => get_option('aica_chat_bubble_color', '#4a90e2'),
        'chatBubblePosition' => get_option('aica_chat_bubble_position', 'bottom-right'),
        'welcomeMessage' => get_option('aica_welcome_message', 'Hello! How can I assist you today?'),
        'contextOption' => get_option('aica_context_option', 'page_context'),
        'requireConsent' => get_option('aica_require_consent', 'yes'),
        'consentMessage' => __('By using this chat, you agree to our data processing as described in our Privacy Policy.', 'aica'),
    ));
}
add_action('wp_enqueue_scripts', 'aica_enqueue_scripts');

function aica_add_chat_bubble() {
    if (!aica_should_display_chat()) {
        return;
    }
    $chat_bubble_title = esc_html(get_option('aica_chat_title', 'Simple AI Assistant'));
    $chat_bubble_text = esc_html(get_option('aica_chat_bubble_text', 'Chat'));
    $chat_bubble_color = esc_attr(get_option('aica_chat_bubble_color', '#4a90e2'));
    $chat_bubble_position = esc_attr(get_option('aica_chat_bubble_position', 'bottom-right'));
    ?>
    <div id='aica-chat-bubble' class='aica-<?php echo esc_attr($chat_bubble_position); ?>' style='background-color: <?php echo esc_attr($chat_bubble_color); ?>;'>
        <?php echo esc_html($chat_bubble_text); ?>
    </div>
    <div id="aica-chat-window" style="display:none;">
        <div id="aica-chat-header">
            <span><?php echo esc_html($chat_bubble_title); ?></span>
            <button id="aica-close-button"><?php echo esc_html_x('X', 'close button', 'simple-ai-assistant'); ?></button>
        </div>
        <div id="aica-chat-messages"></div>
        <div id="aica-chat-input">
            <input type="text" id="aica-user-input" placeholder="<?php echo esc_attr__('Type your message...', 'simple-ai-assistant'); ?>">
            <button id="aica-send-button"><?php echo esc_html__('Send', 'simple-ai-assistant'); ?></button>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'aica_add_chat_bubble');


// Handle AJAX request to OpenAI API
function aica_handle_chat_request() {
    global $aica_logger;

    try {
        // Verify nonce for security
        check_ajax_referer('aica_chat_nonce', 'nonce');

        // Check if the request is coming from the same domain
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $host = $_SERVER['HTTP_HOST'];
        if (strpos($referer, $host) === false) {
            throw new Exception('Invalid request origin.');
        }

        // Check rate limit
        if (!aica_check_rate_limit()) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }

        // Check user permissions
        if (!aica_user_can_chat()) {
            throw new Exception('You do not have permission to use the chat.');
        }
        $encrypted_api_key = get_option('aica_openai_api_key');
        $api_key = $encrypted_api_key ? aica_decrypt_api_key($encrypted_api_key) : '';
        if (empty($api_key)) {
            throw new Exception('OpenAI API key is not set.');
        }

        $model = get_option('aica_openai_model', 'gpt-3.5-turbo');
        $max_tokens = get_option('aica_max_tokens', 150);
        $temperature = get_option('aica_temperature', 0.7);
        $system_message = get_option('aica_system_message', 'You are a helpful assistant.');

        $user_message = sanitize_text_field($_POST['message']);
        $page_content = sanitize_text_field($_POST['pageContent']);
        $page_url = esc_url_raw($_POST['pageUrl']);
        $context_option = sanitize_text_field($_POST['contextOption']);
        $chat_history = isset($_POST['chatHistory']) ? $_POST['chatHistory'] : array();

        if (empty($user_message)) {
            throw new Exception('User message cannot be empty.');
        }
        
        
        
        $messages = array(
            array('role' => 'system', 'content' => $system_message),
        );

        // Add context and history based on the selected option
        switch ($context_option) {
            case 'page_context':
                $messages[] = array('role' => 'system', 'content' => "The current page context is: $page_content\nCurrent page URL: $page_url");
                break;
            case 'context_and_history':
                $messages[] = array('role' => 'system', 'content' => "The current page context is: $page_content\nCurrent page URL: $page_url");
                $messages = array_merge($messages, $chat_history);
                break;
            case 'chat_history':
                $messages = array_merge($messages, $chat_history);
                break;
            case 'no_context':
                // Don't add any context or history
                break;
        }

        $messages[] = array('role' => 'user', 'content' => $user_message);

        // Prepare the data for the OpenAI API request
        $data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => intval($max_tokens),
            'temperature' => floatval($temperature),
            'n' => 1,
            'stop' => null,
        );

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            throw new Exception('Failed to connect to OpenAI API: ' . $response->get_error_message());
        }

        $http_status = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($http_status !== 200) {
            $error_message = isset($response_data['error']['message']) ? $response_data['error']['message'] : 'Unknown error occurred.';
            throw new Exception('OpenAI API error: ' . $error_message);
        }

        if (!isset($response_data['choices'][0]['message']['content'])) {
            throw new Exception('Unexpected response format from OpenAI API.');
        }

        $ai_response = $response_data['choices'][0]['message']['content'];
        $token_usage = $response_data['usage']['total_tokens'];
        aica_increment_rate_limit();
        aica_update_token_usage($token_usage);
        
        $user_id = get_current_user_id();
        $username = $user_id ? get_userdata($user_id)->user_login : 'Guest';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $aica_logger->log('Chat request successful', 'info', array(
            'user_message' => $user_message,
            'ai_response' => $ai_response,
            'token_usage' => $token_usage,
            'username' => $username,
            'ip_address' => $ip_address,
            'user_id' => $user_id,
            'guest_id' => aica_get_guest_id()
        ));

        wp_send_json_success(array('response' => $ai_response, 'token_usage' => $token_usage));

    } catch (Exception $e) {
        $aica_logger->log('Chat request failed: ' . $e->getMessage(), 'error', array(
            'user_message' => isset($user_message) ? $user_message : 'N/A',
            'username' => isset($username) ? $username : 'N/A',
            'ip_address' => isset($ip_address) ? $ip_address : 'N/A',
        ));
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}
add_action('wp_ajax_aica_chat', 'aica_handle_chat_request');
add_action('wp_ajax_nopriv_aica_chat', 'aica_handle_chat_request');

// Generate or retrieve guest ID
function aica_get_guest_id() {
    if (!isset($_COOKIE['aica_guest_id'])) {
        $guest_id = wp_generate_uuid4();
        setcookie('aica_guest_id', $guest_id, time() + (86400 * 30), COOKIEPATH, COOKIE_DOMAIN);
    } else {
        $guest_id = $_COOKIE['aica_guest_id'];
    }
    return $guest_id;
}

// Check if chat should be displayed on current page
function aica_should_display_chat() {
    $enabled_pages = get_option('aica_enable_on_pages', array('home', 'all'));
    $allowed_user_roles = get_option('aica_allowed_user_roles', array('administrator'));
    
    if (!aica_user_can_chat()) {
        return false;
    }
    
    if (in_array('all', $enabled_pages)) return true;
    if (is_front_page() && in_array('home', $enabled_pages)) return true;
    if (is_single() && in_array('posts', $enabled_pages)) return true;
    if (!is_singular(array('post', 'page')) && in_array('custom', $enabled_pages)) return true;
    
    return false;
}

// Check if the current user can use the chat
function aica_user_can_chat() {
    $allowed_user_roles = get_option('aica_allowed_user_roles', array('administrator'));
    
    if (!is_user_logged_in() && in_array('guest', $allowed_user_roles)) {
        return true;
    }
    
    $user = wp_get_current_user();
    $user_roles = (array) $user->roles;
    
    return array_intersect($allowed_user_roles, $user_roles) ? true : false;
}

// Rate limiting functions
function aica_check_rate_limit() {
    $rate_limit = get_option('aica_rate_limit', 10);
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $transient_name = 'aica_rate_limit_' . md5($user_ip);
    $current_count = get_transient($transient_name);

    return $current_count < $rate_limit;
}

// Handle AJAX request to delete user data
function aica_delete_user_data() {
    check_ajax_referer('aica_chat_nonce', 'nonce');
    
    global $aica_logger;
    $user_id = get_current_user_id();
    $anonymized = false;

    if ($user_id === 0) {
        $guest_id = aica_get_guest_id();
        $anonymized = $aica_logger->anonymize_logs_by_guest_id($guest_id);
    } else {
        $anonymized = $aica_logger->anonymize_logs_by_user_id($user_id);
    }

    if ($anonymized) {
        wp_send_json_success(array('message' => __('Your personal data has been removed from the chat logs.', 'aica')));
    } else {
        wp_send_json_error(array('message' => __('Failed to remove personal data from chat logs. Please try again.', 'aica')));
    }
}
add_action('wp_ajax_aica_delete_user_data', 'aica_delete_user_data');
add_action('wp_ajax_nopriv_aica_delete_user_data', 'aica_delete_user_data');

// Add a function to show chat logs for the admin panel
function aica_get_chat_logs($limit = 100, $page = 1) {
    global $aica_logger;
    $logs = $aica_logger->get_logs();
    $total = count($logs);
    $logs = array_slice($logs, ($page - 1) * $limit, $limit);
    return array('logs' => $logs, 'total' => $total);
}

// Implement data retention limit
function aica_cleanup_old_data() {
    global $aica_logger;
    $retention_period = get_option('aica_data_retention_period', 30);
    $aica_logger->delete_old_logs($retention_period);
}
add_action('aica_daily_cleanup', 'aica_cleanup_old_data');


function aica_increment_rate_limit() {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $transient_name = 'aica_rate_limit_' . md5($user_ip);
    $current_count = get_transient($transient_name);

    if ($current_count === false) {
        set_transient($transient_name, 1, HOUR_IN_SECONDS);
    } else {
        set_transient($transient_name, $current_count + 1, HOUR_IN_SECONDS);
    }
}

// Schedule daily cleanup if not already scheduled
if (!wp_next_scheduled('aica_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'aica_daily_cleanup');
}

// Add privacy policy content
function aica_add_privacy_policy_content() {
    if (!function_exists('wp_add_privacy_policy_content')) {
        return;
    }
    $content = sprintf( /* translators: %d: data retention period */ 
        __('When you use the AI Chat Assistant on our website, we collect and store your chat messages and interactions to provide and improve the service. This data is retained for %d days, after which it is automatically deleted. You can request deletion of your data at any time through the chat interface. For more information, please see our full Privacy Policy.', 'aica'),
        get_option('aica_data_retention_period', 30)
    );
    
    wp_add_privacy_policy_content('AI Chat Assistant', wp_kses_post(wpautop($content, false)));
}
add_action('admin_init', 'aica_add_privacy_policy_content');


//  Token usage tracking
function aica_get_token_usage($start_date = null, $end_date = null) {
    global $aica_logger;
    return $aica_logger->get_token_usage($start_date, $end_date);
}

function aica_update_token_usage($tokens) {
    $usage = get_option('aica_token_usage', array());
    $current_month = gmdate('Y-m');
    
    if (!isset($usage[$current_month])) {
        $usage[$current_month] = 0;
    }
    
    $usage[$current_month] += $tokens;
    update_option('aica_token_usage', $usage);
}

// AJAX handler for updating token usage
function aica_ajax_update_token_usage() {
    check_ajax_referer('aica_chat_nonce', 'nonce');
    $tokens = intval($_POST['tokens']);
    aica_update_token_usage($tokens);
    wp_send_json_success();
}
add_action('wp_ajax_aica_update_token_usage', 'aica_ajax_update_token_usage');
add_action('wp_ajax_nopriv_aica_update_token_usage', 'aica_ajax_update_token_usage');

// Export chat logs
function aica_export_chat_logs() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    global $aica_logger;
    $logs = $aica_logger->get_logs();
    $csv = "Timestamp,Level,Message,User Message,Username,IP Address,User ID,Guest ID,Token Usage\n";

    foreach ($logs as $log) {
        $csv .= sprintf(
            "%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
            $log['timestamp'],
            $log['level'],
            str_replace('"', '""', $log['message']),
            isset($log['context']['user_message']) ? $log['context']['user_message'] : 'N/A',
            isset($log['context']['username']) ? $log['context']['username'] : 'N/A',
            isset($log['context']['ip_address']) ? $log['context']['ip_address'] : 'N/A',
            isset($log['context']['user_id']) ? $log['context']['user_id'] : 'N/A',
            isset($log['context']['guest_id']) ? $log['context']['guest_id'] : 'N/A',
            isset($log['context']['token_usage']) ? $log['context']['token_usage'] : 'N/A'
        );
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="chat_logs.csv"');
    echo esc_html($csv);
    exit;
}
add_action('admin_post_aica_export_logs', 'aica_export_chat_logs');

// New feature: Clear chat history for GDPR compliance
function aica_clear_chat_history() {
    check_ajax_referer('aica_clear_history_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
    }

    global $aica_logger;
    $aica_logger->clear_logs();
    
    wp_send_json_success(array('message' => 'Chat history cleared successfully.'));
}
add_action('wp_ajax_aica_clear_history', 'aica_clear_chat_history');

// Schedule daily log rotation
if (!wp_next_scheduled('aica_daily_log_rotation')) {
    wp_schedule_event(time(), 'daily', 'aica_daily_log_rotation');
}

add_action('aica_daily_log_rotation', array($aica_logger, 'rotate_logs'));