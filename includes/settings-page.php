<?php
// Add settings page to WordPress admin menu
function aica_add_settings_page() {
    add_options_page('Simple AI Assistant Settings', 'Simple AI Assistant', 'manage_options', 'aica-settings', 'aica_render_settings_page');
}
add_action('admin_menu', 'aica_add_settings_page');

// Register settings
function aica_register_settings() {
    register_setting('aica_settings_group', 'aica_openai_api_key', 'aica_encrypt_api_key');
    register_setting('aica_settings_group', 'aica_openai_model', 'sanitize_text_field');
    register_setting('aica_settings_group', 'aica_rate_limit', 'absint');
    register_setting('aica_settings_group', 'aica_max_tokens', 'absint');
    register_setting('aica_settings_group', 'aica_temperature', 'aica_sanitize_float');
    register_setting('aica_settings_group', 'aica_system_message', 'sanitize_textarea_field');
    register_setting('aica_settings_group', 'aica_chat_title', 'sanitize_text_field');
    register_setting('aica_settings_group', 'aica_enable_on_pages', 'aica_sanitize_array');
    register_setting('aica_settings_group', 'aica_welcome_message', 'sanitize_textarea_field');
    register_setting('aica_settings_group', 'aica_allowed_user_roles', 'aica_sanitize_array');
    register_setting('aica_settings_group', 'aica_context_option', 'sanitize_text_field');
    register_setting('aica_settings_group', 'aica_require_consent', 'sanitize_text_field');
    register_setting('aica_settings_group', 'aica_data_retention_period', 'absint');
    
    // New graphic settings
    register_setting('aica_graphic_settings_group', 'aica_chat_bubble_text', 'sanitize_text_field');
    register_setting('aica_graphic_settings_group', 'aica_chat_bubble_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_chat_bubble_position', 'sanitize_text_field');
    register_setting('aica_graphic_settings_group', 'aica_chat_window_bg_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_chat_header_bg_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_chat_header_text_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_user_message_bg_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_user_message_text_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_ai_message_bg_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_ai_message_text_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_input_bg_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_input_text_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_send_button_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_send_button_text_color', 'sanitize_hex_color');
    register_setting('aica_graphic_settings_group', 'aica_font_family', 'sanitize_text_field');
    register_setting('aica_graphic_settings_group', 'aica_font_size', 'sanitize_text_field');
}
add_action('admin_init', 'aica_register_settings');


function aica_encrypt_api_key($api_key) {
    if (empty($api_key)) {
        return get_option('aica_openai_api_key'); // Return the existing encrypted key if the field is empty
    }
    $encryption_key = AUTH_KEY . SECURE_AUTH_KEY;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($api_key, 'aes-256-cbc', $encryption_key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function aica_decrypt_api_key($encrypted_api_key) {
    $encryption_key = AUTH_KEY . SECURE_AUTH_KEY;
    $data = base64_decode($encrypted_api_key);
    $iv = substr($data, 0, openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = substr($data, openssl_cipher_iv_length('aes-256-cbc'));
    return openssl_decrypt($encrypted, 'aes-256-cbc', $encryption_key, 0, $iv);
}



// Sanitize float values
function aica_sanitize_float($input) {
    return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

// Sanitize array values
function aica_sanitize_array($input) {
    return !is_array($input) ? array() : array_map('sanitize_text_field', $input);
}

// Add a function to verify nonce when loading the settings page
function aica_verify_settings_nonce() {
    // Check if we're on the settings page
    if (isset($_GET['page']) && $_GET['page'] === 'aica-settings') {
        // If nonce is not set or invalid, create a new one and redirect
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'aica_settings_nonce')) {
            $nonce = wp_create_nonce('aica_settings_nonce');
            wp_safe_redirect(add_query_arg('_wpnonce', $nonce, remove_query_arg('_wpnonce')));
            exit;
        }
    }
}
add_action('admin_init', 'aica_verify_settings_nonce');


function aica_render_settings_page() {
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'aica_settings_nonce')) {
        wp_die('Security check failed');
    }
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    <div class="wrap">
        <h1>Simple AI Assistant Settings</h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=aica-settings&tab=general'), 'aica_settings_nonce')); ?>" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General Settings</a>
            <a href="<?php echo esc_url(wp_nonce_url(admin_url('options-general.php?page=aica-settings&tab=graphic'), 'aica_settings_nonce')); ?>" class="nav-tab <?php echo $active_tab == 'graphic' ? 'nav-tab-active' : ''; ?>">Graphic Settings</a>
        </h2>
        
        <form method="post" action="options.php">
            <?php wp_nonce_field('aica_settings_nonce', 'aica_settings_nonce'); ?>
            <?php
            if ($active_tab == 'general') {
                settings_fields('aica_settings_group');
                do_settings_sections('aica_settings_group');
                wp_nonce_field('aica_general_settings_nonce', 'aica_general_settings_nonce_field');
                aica_render_general_settings();
            } else {
                settings_fields('aica_graphic_settings_group');
                do_settings_sections('aica_graphic_settings_group');
                aica_render_graphic_settings();
            }
            submit_button();
            ?>
        </form>
         <?php if ($active_tab == 'graphic'): ?>
            <form method="post" action="">
                <?php wp_nonce_field('aica_reset_graphic_settings', 'aica_reset_graphic_nonce'); ?>
                <input type="hidden" name="action" value="reset_graphic_settings">
                <input type="submit" class="button button-secondary" value="Reset to Defaults" onclick="return confirm('Are you sure you want to reset all graphic settings to their default values? This action cannot be undone.');">
            </form>
        <?php endif; ?>

        <?php if ($active_tab == 'general'): ?>
            <h2>Token Usage</h2>
            <?php
            $current_month_start = gmdate('Y-m-01');
            $current_month_end = gmdate('Y-m-t');
            $current_month_usage = aica_get_token_usage($current_month_start, $current_month_end);
            $total_usage = aica_get_token_usage();
            ?>
            <p>Current month's token usage: <?php echo number_format($current_month_usage); ?></p>
            <p>Total token usage: <?php echo number_format($total_usage); ?></p>

            <h2>Chat Logs</h2>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('aica_export_logs', 'aica_export_logs_nonce'); ?>
                <input type="hidden" name="action" value="aica_export_logs">
                <?php submit_button('Export Chat Logs', 'secondary', 'export_logs'); ?>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                     <tr>
                          <th>Timestamp</th>
                          <th>User</th>
                          <th>IP Address</th>
                          <th>Message</th>
                          <th>Token Usage</th>
                     </tr>
                 </thead>
                 <tbody>
               <?php
                   $page = isset($_GET['log_page']) ? intval($_GET['log_page']) : 1;
                    $logs_data = aica_get_chat_logs(20, $page);
                    foreach ($logs_data['logs'] as $log) {
                        echo "<tr>";
                        echo "<td>" . esc_html($log['timestamp']) . "</td>";
                        echo "<td>" . esc_html($log['username']) . "</td>";
                        echo "<td>" . esc_html($log['ip_address']) . "</td>";
                        echo "<td>" . esc_html($log['message']) . "</td>";
                        echo "<td>" . esc_html($log['token_usage']) . "</td>";
                        echo "</tr>";
               }
                ?>
                </tbody>
             </table>
              <?php
             $total_pages = ceil($logs_data['total'] / 20);
              if ($total_pages > 1) {
                 echo '<div class="tablenav"><div class="tablenav-pages">';
                 echo wp_kses_post( paginate_links(array(
                        'base' => add_query_arg('log_page', '%#%'),
                       'format' => '',
                       'prev_text' => __('&laquo;'),
                       'next_text' => __('&raquo;'),
                       'total' => $total_pages,
                       'current' => $page
                )));
                echo '</div></div>';
          }
          ?>

            <h2>Clear Chat History</h2>
            <p>Use this button to clear all chat history. This action cannot be undone.</p>
            <button id="aica-clear-history" class="button button-secondary">Clear Chat History</button>

            <script>
            jQuery(document).ready(function($) {
                $('#aica-clear-history').on('click', function() {
                    if (confirm('Are you sure you want to clear all chat history? This action cannot be undone.')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'aica_clear_history',
                                nonce: '<?php echo esc_js(wp_create_nonce("aica_clear_history_nonce")); ?>'
                            },
                            success: function(response) {
                                alert(response.data.message);
                                location.reload();
                            },
                            error: function() {
                                alert('An error occurred while clearing chat history.');
                            }
                        });
                    }
                });
            });
            </script>
        <?php endif; ?>
    </div>
    <?php
}


// Render the general settings page
function aica_render_general_settings() {
    $encrypted_api_key = get_option('aica_openai_api_key');
    $api_key = $encrypted_api_key ? aica_decrypt_api_key($encrypted_api_key) : '';

 
    ?>
    
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">OpenAI API Key</th>
                    <td>
                        <input type="password" name="aica_openai_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <p class="description">Enter your OpenAI API key. Keep this secret!</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Chat Title</th>
                    <td>
                        <input type="text" name="aica_chat_title" value="<?php echo esc_attr(get_option('aica_chat_title', 'Simple AI Assistant')); ?>" class="regular-text"  />
                        <p class="description">This is the name you will show in the chat window</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">OpenAI Model</th>
                    <td>
                        <select name="aica_openai_model">
                            <option value="gpt-3.5-turbo" <?php selected(get_option('aica_openai_model'), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected(get_option('aica_openai_model'), 'gpt-4'); ?>>GPT-4</option>
                        </select>
                        <p class="description">Select the OpenAI model to use for chat responses.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Require User Consent</th>
                    <td>
                        <select name="aica_require_consent">
                            <option value="yes" <?php selected(get_option('aica_require_consent'), 'yes'); ?>>Yes</option>
                            <option value="no" <?php selected(get_option('aica_require_consent'), 'no'); ?>>No</option>
                        </select>
                        <p class="description">Choose whether to require user consent before using the chat.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Data Retention Period (days)</th>
                    <td>
                        <input type="number" name="aica_data_retention_period" value="<?php echo esc_attr(get_option('aica_data_retention_period', 30)); ?>" class="small-text" min="1" />
                        <p class="description">Number of days to retain chat logs before automatic deletion.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Rate Limit (per hour)</th>
                    <td>
                        <input type="number" name="aica_rate_limit" value="<?php echo esc_attr(get_option('aica_rate_limit', 10)); ?>" class="small-text" min="1" />
                        <p class="description">Maximum number of requests allowed per hour per user.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Max Tokens</th>
                    <td>
                        <input type="number" name="aica_max_tokens" value="<?php echo intval(get_option('aica_max_tokens', 150)); ?>" class="small-text" min="1" max="2048" />
                        <p class="description">Maximum number of tokens to generate in the chat completion.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Temperature</th>
                    <td>
                        <input type="number" name="aica_temperature" value="<?php echo esc_attr(get_option('aica_temperature', 0.7)); ?>" class="small-text" min="0" max="1" step="0.1" />
                        <p class="description">Controls randomness: Lowering results in less random completions. As the temperature approaches zero, the model will become deterministic and repetitive.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">System Message</th>
                    <td>
                        <textarea name="aica_system_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('aica_system_message', 'You are a helpful assistant.')); ?></textarea>
                        <p class="description">The system message helps set the behavior of the assistant. For example, 'You are a helpful assistant.'</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Welcome Message</th>
                    <td>
                        <textarea name="aica_welcome_message" rows="4" cols="50" class="large-text"><?php echo esc_textarea(get_option('aica_welcome_message', 'Hello! How can I assist you today?')); ?></textarea>
                        <p class="description">The message displayed when a user starts a new chat session.</p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row">Context and History Options</th>
                    <td>
                        <?php
                        $context_option = get_option('aica_context_option', 'page_context');
                        $options = array(
                            'page_context' => 'Pass page context with every message',
                            'context_and_history' => 'Pass page context and chat history',
                            'chat_history' => 'Pass only chat history',
                            'no_context' => 'Don\'t pass anything'
                        );
                        foreach ($options as $value => $label) {
                            echo '<label><input type="radio" name="aica_context_option" value="' . esc_attr($value) . '" ' . checked($context_option, $value, false) . ' /> ' . esc_html($label) . '</label><br />';
                        }
                        ?>
                        <p class="description">Choose how to handle page context and chat history.</p>
                    </td>
                </tr>
    
                <tr valign="top">
                    <th scope="row">Enable on Pages</th>
                    <td>
                        <?php
                        $enabled_pages = get_option('aica_enable_on_pages', array('home', 'all'));
                        $page_options = array(
                            'home' => 'Home Page',
                            'all' => 'All Pages',
                            'posts' => 'All Posts',
                            'custom' => 'Custom Post Types'
                        );
                        foreach ($page_options as $value => $label) {
                            echo '<label><input type="checkbox" name="aica_enable_on_pages[]" value="' . esc_attr($value) . '" ' . checked(in_array($value, $enabled_pages), true, false) . ' /> ' . esc_html($label) . '</label><br />';
                        }
                        ?>
                        <p class="description">Select where to display the chat assistant.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Allowed User Roles</th>
                    <td>
                        <?php
                        $allowed_roles = get_option('aica_allowed_user_roles', array('administrator'));
                        $roles = wp_roles()->get_names();
                        $roles['guest'] = 'Guest (Not logged in)';
                        foreach ($roles as $role_value => $role_name) {
                            echo '<label><input type="checkbox" name="aica_allowed_user_roles[]" value="' . esc_attr($role_value) . '" ' . checked(in_array($role_value, $allowed_roles), true, false) . ' /> ' . esc_html($role_name) . '</label><br />';
                        }
                        ?>
                        <p class="description">Select which user roles are allowed to use the chat.</p>
                    </td>
                </tr>
            </table>
            
            <?php 
        
}

function aica_render_graphic_settings() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    ?>
    <style>
        .aica-settings-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .aica-settings-section {
            flex: 1 1 300px;
            max-width: 100%;
            background: #fff;
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 5px;
        }
        .aica-settings-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .aica-color-picker {
            display: flex;
            align-items: center;
        }
        .aica-color-picker .wp-color-result {
            margin-right: 10px;
        }
    </style>
    <div class="aica-settings-container">
        <div class="aica-settings-section">
            <h3>Chat Bubble</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Text</th>
                    <td>
                        <input type="text" name="aica_chat_bubble_text" value="<?php echo esc_attr(get_option('aica_chat_bubble_text', 'Chat')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Color</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_chat_bubble_color" value="<?php echo esc_attr(get_option('aica_chat_bubble_color', '#4a90e2')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Position</th>
                    <td>
                        <select name="aica_chat_bubble_position">
                            <option value="bottom-right" <?php selected(get_option('aica_chat_bubble_position'), 'bottom-right'); ?>>Bottom Right</option>
                            <option value="middle-right" <?php selected(get_option('aica_chat_bubble_position'), 'middle-right'); ?>>Middle Right</option>
                            <option value="top-right" <?php selected(get_option('aica_chat_bubble_position'), 'top-right'); ?>>Top Right</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aica-settings-section">
            <h3>Chat Window</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Background Color</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_chat_window_bg_color" value="<?php echo esc_attr(get_option('aica_chat_window_bg_color', '#ffffff')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Header Background</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_chat_header_bg_color" value="<?php echo esc_attr(get_option('aica_chat_header_bg_color', '#4a90e2')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Header Text Color</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_chat_header_text_color" value="<?php echo esc_attr(get_option('aica_chat_header_text_color', '#ffffff')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aica-settings-section">
            <h3>Messages</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">User Message Background</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_user_message_bg_color" value="<?php echo esc_attr(get_option('aica_user_message_bg_color', '#4a90e2')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">User Message Text</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_user_message_text_color" value="<?php echo esc_attr(get_option('aica_user_message_text_color', '#ffffff')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">AI Message Background</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_ai_message_bg_color" value="<?php echo esc_attr(get_option('aica_ai_message_bg_color', '#f0f0f0')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">AI Message Text</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_ai_message_text_color" value="<?php echo esc_attr(get_option('aica_ai_message_text_color', '#333333')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aica-settings-section">
            <h3>Input Area</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Input Background</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_input_bg_color" value="<?php echo esc_attr(get_option('aica_input_bg_color', '#ffffff')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Input Text Color</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_input_text_color" value="<?php echo esc_attr(get_option('aica_input_text_color', '#333333')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Send Button Color</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_send_button_color" value="<?php echo esc_attr(get_option('aica_send_button_color', '#4a90e2')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Send Button Text</th>
                    <td>
                        <div class="aica-color-picker">
                            <input type="text" name="aica_send_button_text_color" value="<?php echo esc_attr(get_option('aica_send_button_text_color', '#ffffff')); ?>" class="aica-color-field" />
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="aica-settings-section">
            <h3>Typography</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Font Family</th>
                    <td>
                        <select name="aica_font_family">
                            <option value="Arial, sans-serif" <?php selected(get_option('aica_font_family'), 'Arial, sans-serif'); ?>>Arial</option>
                            <option value="Helvetica, sans-serif" <?php selected(get_option('aica_font_family'), 'Helvetica, sans-serif'); ?>>Helvetica</option>
                            <option value="Georgia, serif" <?php selected(get_option('aica_font_family'), 'Georgia, serif'); ?>>Georgia</option>
                            <option value="'Times New Roman', serif" <?php selected(get_option('aica_font_family'), "'Times New Roman', serif"); ?>>Times New Roman</option>
                            <option value="'Courier New', monospace" <?php selected(get_option('aica_font_family'), "'Courier New', monospace"); ?>>Courier New</option>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Font Size</th>
                    <td>
                        <select name="aica_font_size">
                            <option value="12px" <?php selected(get_option('aica_font_size'), '12px'); ?>>Small (12px)</option>
                            <option value="14px" <?php selected(get_option('aica_font_size'), '14px'); ?>>Medium (14px)</option>
                            <option value="16px" <?php selected(get_option('aica_font_size'), '16px'); ?>>Large (16px)</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($){
        $('.aica-color-field').wpColorPicker();
    });
    </script>
    <?php
}

// Handle the reset action for graphic settings
function aica_handle_reset_graphic_settings() {
    if (isset($_POST['action']) && $_POST['action'] == 'reset_graphic_settings') {
        if (!wp_verify_nonce($_POST['aica_reset_graphic_nonce'], 'aica_reset_graphic_settings')) {
            wp_die('Security check failed');
        }

        $default_values = array(
            'aica_chat_bubble_text' => 'Chat',
            'aica_chat_bubble_color' => '#4a90e2',
            'aica_chat_bubble_position' => 'bottom-right',
            'aica_chat_window_bg_color' => '#ffffff',
            'aica_chat_header_bg_color' => '#4a90e2',
            'aica_chat_header_text_color' => '#ffffff',
            'aica_user_message_bg_color' => '#4a90e2',
            'aica_user_message_text_color' => '#ffffff',
            'aica_ai_message_bg_color' => '#f0f0f0',
            'aica_ai_message_text_color' => '#333333',
            'aica_input_bg_color' => '#ffffff',
            'aica_input_text_color' => '#333333',
            'aica_send_button_color' => '#4a90e2',
            'aica_send_button_text_color' => '#ffffff',
            'aica_font_family' => 'Arial, sans-serif',
            'aica_font_size' => '14px'
        );

        foreach ($default_values as $option_name => $default_value) {
            update_option($option_name, $default_value);
        }

        add_settings_error('aica_messages', 'aica_message', __('Graphic settings have been reset to default values.', 'aica'), 'updated');
    }
}
add_action('admin_init', 'aica_handle_reset_graphic_settings');



// Add a link to the settings page on the plugins page
function aica_add_settings_link($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=aica-settings') . '">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'aica_add_settings_link');