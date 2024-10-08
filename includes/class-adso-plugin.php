<?php

class Adso_Plugin {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('ADSO_PLUGIN_VERSION')) {
            $this->version = ADSO_PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'adso-plugin';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-adso-plugin-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-adso-plugin-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-adso-plugin-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-adso-plugin-public.php';

        $this->loader = new Adso_Plugin_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Adso_Plugin_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new Adso_Plugin_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this, 'add_admin_menu_page');
        $this->loader->add_action('admin_menu', $this, 'add_settings_sub_menu');
        $this->loader->add_action('admin_init', $this, 'register_settings');
        $this->loader->add_action('wp_ajax_generate_myfastway_token', $this, 'generate_token_ajax_handler');
        $this->loader->add_action('wp_ajax_get_myfastway_location_data', $this, 'get_location_data_ajax_handler');
        $this->loader->add_action('wp_ajax_clear_myfastway_token', $this, 'clear_token_ajax_handler'); // New clear token handler
        $this->loader->add_action('wp_ajax_send_location_data', $this, 'send_location_data_ajax_handler'); // New send location data handler
    }

    public function add_admin_menu_page() {
        add_menu_page('Server & WooCommerce Info', 'Server Info', 'manage_options', 'adso-server-info', array($this, 'display_server_info'), 'dashicons-admin-generic', 6);
    }

    public function add_settings_sub_menu() {
        add_submenu_page('woocommerce', 'ADSO Settings 2', 'ADSO Settings', 'manage_options', 'adso-settings', array($this, 'display_settings_page'));
    }

    public function display_server_info() {
        $wp_version = get_bloginfo('version');
        $wc_version = class_exists('WooCommerce') ? WC()->version : 'Not installed';
        $php_memory_limit = ini_get('memory_limit');
        $php_max_execution_time = ini_get('max_execution_time');
        $php_max_input_vars = ini_get('max_input_vars');
        $num_plugins = count(get_plugins());
        $wp_debug = defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled';
        $server_ip = $_SERVER['SERVER_ADDR'];

        // New server details
        $php_version = phpversion();
        $server_software = $_SERVER['SERVER_SOFTWARE'];
        $max_upload_size = size_format(wp_max_upload_size());
        $php_post_max_size = ini_get('post_max_size');
        $php_upload_max_filesize = ini_get('upload_max_filesize');
        $php_max_execution_time = ini_get('max_execution_time');
        $php_display_errors = ini_get('display_errors') ? 'On' : 'Off';

        echo '<div class="wrap"><h1>Server Information</h1>';
        echo '<table class="form-table">';
        echo "<tr><th>WordPress Version:</th><td>$wp_version</td></tr>";
        echo "<tr><th>WooCommerce Version:</th><td>$wc_version</td></tr>";
        echo "<tr><th>PHP Memory Limit:</th><td>$php_memory_limit</td></tr>";
        echo "<tr><th>PHP Max Execution Time:</th><td>$php_max_execution_time</td></tr>";
        echo "<tr><th>PHP Max Input Vars:</th><td>$php_max_input_vars</td></tr>";
        echo "<tr><th>Number of Installed Plugins:</th><td>$num_plugins</td></tr>";
        echo "<tr><th>WP Debug Mode:</th><td>$wp_debug</td></tr>";
        echo "<tr><th>Server IP Address:</th><td>$server_ip</td></tr>";
        echo '</table>';
        echo '<button id="copyButton" style="margin-top: 20px;">Copy Info</button>';
        echo '<script>
            document.getElementById("copyButton").addEventListener("click", function() {
                var el = document.createElement("textarea");
                el.value = "WordPress Version: ' . $wp_version . '\\n' .
                           'WooCommerce Version: ' . $wc_version . '\\n' .
                           'PHP Version: ' . $php_version . '\\n' .
                           'Web Server: ' . $server_software . '\\n' .
                           'PHP Memory Limit: ' . $php_memory_limit . '\\n' .
                           'PHP Max Execution Time: ' . $php_max_execution_time . ' seconds\\n' .
                           'PHP Max Input Vars: ' . $php_max_input_vars . '\\n' .
                           'Max Upload Size: ' . $max_upload_size . '\\n' .
                           'PHP Post Max Size: ' . $php_post_max_size . '\\n' .
                           'PHP Upload Max Filesize: ' . $php_upload_max_filesize . '\\n' .
                           'Number of Installed Plugins: ' . $num_plugins . '\\n' .
                           'WP Debug Mode: ' . $wp_debug . '\\n' .
                           'PHP Display Errors: ' . $php_display_errors . '\\n' .
                           'Server IP Address: ' . $server_ip . '";
                document.body.appendChild(el);
                el.select();
                document.execCommand("copy");
                document.body.removeChild(el);
                alert("Information copied to clipboard!");
            });
        </script>';
        echo '</div>';
    }

    public function display_settings_page() {
        echo '<div class="wrap"><h1>ADSO Settings Page 2</h1>';
        echo '<form method="post" action="options.php">';

        settings_fields('adso_options_group');
        do_settings_sections('adso_settings');

        submit_button('Save Changes');

        echo '</form>';

        // Add token generation button
        echo '<button id="generate-token" class="button button-secondary">Generate Token</button>';

        // Add token display field
        $current_token = get_option('adso_myfastway_access_token', 'Default Token Value');
        echo '<div id="token-display" style="margin-top: 20px;">';
        echo '<h3>Current Token:</h3>';
        echo '<input type="text" id="current-token" value="' . esc_attr($current_token) . '" readonly style="width: 100%;">';
        echo '</div>';

        // Add Clear Token button
        echo '<button id="clear-token" class="button button-secondary" style="margin-top: 20px;">Clear Token</button>';

        // Add Get Location Data button
        echo '<button id="get-location-data" class="button button-primary" style="margin-top: 20px;">Get Location Data</button>';

        // Display Location Data Result
        echo '<div id="location-data-result" style="margin-top: 20px;">';
        echo '<h3>Location Data Result:</h3>';
        echo '<pre id="location-data-output"></pre>';
        echo '</div>';

        // Add JavaScript for token generation, clearing, and fetching location data
        echo '<script>
            jQuery(document).ready(function($) {
                $("#generate-token").on("click", function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "generate_myfastway_token"
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#current-token").val(response.data.token);
                            } else {
                                alert("Error generating token: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("An error occurred while generating the token.");
                        }
                    });
                });

                $("#clear-token").on("click", function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "clear_myfastway_token"
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#current-token").val("Token cleared");
                            } else {
                                alert("Error clearing token: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("An error occurred while clearing the token.");
                        }
                    });
                });

                // Handle Location Data button click
                $("#get-location-data").on("click", function(e) {
                    e.preventDefault();
                    $.ajax({
                        url: ajaxurl,
                        type: "POST",
                        data: {
                            action: "send_location_data"
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#location-data-output").text(JSON.stringify(response.data, null, 2));
                            } else {
                                alert("Error fetching location data: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("An error occurred while fetching location data.");
                        }
                    });
                });
            });
        </script>';

        echo '</div>';
    }

    public function register_settings() {
        register_setting('adso_options_group', 'adso_myfastway_api_secret');
        register_setting('adso_options_group', 'adso_myfastway_api_key');

        add_settings_section(
            'adso_myfastway_section',
            'MyFastWay API Settings',
            array($this, 'myfastway_section_callback'),
            'adso_settings'
        );

        add_settings_field(
            'adso_myfastway_api_secret',
            'API Secret',
            array($this, 'myfastway_api_secret_callback'),
            'adso_settings',
            'adso_myfastway_section'
        );

        add_settings_field(
            'adso_myfastway_api_key',
            'API Key',
            array($this, 'myfastway_api_key_callback'),
            'adso_settings',
            'adso_myfastway_section'
        );

        add_settings_field(
            'adso_myfastway_current_token',
            'Current Token',
            array($this, 'myfastway_current_token_callback'),
            'adso_settings',
            'adso_myfastway_section'
        );
    }

    public function myfastway_section_callback() {
        echo '<p>Enter your MyFastWay API credentials below:</p>';
    }

    public function myfastway_api_secret_callback() {
        $api_secret = get_option('adso_myfastway_api_secret');
        echo '<input type="password" name="adso_myfastway_api_secret" value="' . esc_attr($api_secret) . '" class="regular-text">';
    }

    public function myfastway_api_key_callback() {
        $api_key = get_option('adso_myfastway_api_key');
        echo '<input type="text" name="adso_myfastway_api_key" value="' . esc_attr($api_key) . '" class="regular-text">';
    }

    public function myfastway_current_token_callback() {
        $current_token = get_option('adso_myfastway_access_token', 'Default Token Value');
        echo '<input type="text" id="adso_myfastway_current_token" value="' . esc_attr($current_token) . '" readonly class="regular-text">';
    }

    public function generate_token_ajax_handler() {
        $token = $this->get_bearer_token();
        if ($token) {
            update_option('adso_myfastway_access_token', $token);
            update_option('adso_myfastway_token_expires', time() + 3600); // Token expires in 1 hour
            wp_send_json_success(array('token' => $token));
        } else {
            wp_send_json_error(array('message' => 'Failed to generate token'));
        }
    }

    public function clear_token_ajax_handler() {
        // Clear the token by updating the option with an empty value
        update_option('adso_myfastway_access_token', '');
        update_option('adso_myfastway_token_expires', 0); // Reset expiration time

        wp_send_json_success();
    }

    public function send_location_data_ajax_handler() {
        // Retrieve the stored token
        $token = get_option('adso_myfastway_access_token');
        if (!$token) {
            wp_send_json_error(array('message' => 'No token available.'));
            return;
        }

        // Prepare the request body
        $body = json_encode([
            'conTypeId' => 1,
            'from' => [
                'streetAddress' => '10 Bridge Avenue',
                'locality' => 'Te Atatu',
                'postalCode' => '0610',
                'country' => 'NZ'
            ],
            'to' => [
                'streetAddress' => '145 Symonds Street',
                'locality' => 'Grafton',
                'postalCode' => '1010',
                'country' => 'NZ'
            ]
        ]);

        // Set up the API request
		$token = "eyJhbGciOiJSUzI1NiIsImtpZCI6IkZEODZERDU4NkY5OTg1NERDMzIyRTRBNzY0QjAxMUFDMjkzRUEwMEEiLCJ0eXAiOiJhdCtqd3QiLCJ4NXQiOiJfWWJkV0ctWmhVM0RJdVNuWkxBUnJDay1vQW8ifQ.eyJuYmYiOjE3MjgzNzczODIsImV4cCI6MTcyODM4MDk4MiwiaXNzIjoiaHR0cHM6Ly9pZGVudGl0eS5mYXN0d2F5Lm9yZyIsImF1ZCI6ImZ3LWZsMi1hcGktbnoiLCJjbGllbnRfaWQiOiJmdy1mbDItQVVLMDgzMDQ3Mi1iMzkyNzgwNjFkNWQiLCJjb3VudHJ5aWQiOiI2IiwicGFyZW50aWQiOiIwIiwiY3VzdG9tZXJpZCI6IjEzNzQ4MSIsInNjb3BlIjpbImZ3LWZsMi1hcGktbnoiXX0.eUJ3EC594my-Kgh6xVhVMLHlonGNs5PfO5FFCmkGJsLkqdUmyujh5iHANQLNdnpxFlgaLmdX-HqoUa-2tjttlg0znT_FnsP7-VQJD_iJ1uSomjkEJMiQn-RqnsVmQFgGCNnXEZdY0CXBl-qoQd1qRwY5NF3mwpRXEMVmW3bAJOU4rXhCfXERRxXF8DgtkE1H4fGm1ro4LLIcOoRS-VEEFu-ZlPqyt8BI-_64UG9jMVpD4sv4o99hEhKpRq3DHWMNjTWRRbyDDVEGH0Q40cMc1kRFH394DdpfDzo4GTLIN0hQstV7AdcHJZJAyqk0C3x-kJlGowWKeeAduFvMdcK51szzHRZSBC2EQmF0GCj5OnNmwfEabOfpeoNrTm2FM2I9q5CBJqDT_TjmjulRIfuBViRfXjx0ESUqkdfZYHGw9SrGcqiI5gZJAdeuSFPEbwMgK5SgFFcKfXgbNdk49WYVAL75ihLe5GkSt_MfpTyfnMOXKwkklIhr9SE0qhKt9IMCHb3MfAXElLCvzGmy4lNuhMNfYp4j0hE3SkQkX8Ux8g2jQHBNSx7n-KZfeub4ufEgXVh1RFnCU57t305LLo6AC5-ixYPC0Mi3yPFgn_xVmRU7mMG92K4UBRpkZU3MRvreUBPBF13Z1PZOtRY03sZX-AR1rkbqosfmxVNUwFFw-H8"; 
        $response = wp_remote_post('https://api.myfastway.co.nz/api/location', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ],
            'body' => $body,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if (!isset($data['data'])) {
            wp_send_json_error(array('message' => 'Invalid API response.'));
            return;
        }

        wp_send_json_success($data['data']);
    }

    private function define_public_hooks() {
        $plugin_public = new Adso_Plugin_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }

    private function get_bearer_token() {
        $client_id = 'fw-fl2-AUK0890381-fd593011eca8';
        $client_secret = '846ecd30-f252-465f-a189-2a6fc5f9ff17';
        $token_url = 'https://identity.aramexconnect.co.nz/connect/token';
        $args = [
            'body' => http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret
            ]),
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'timeout' => 30
        ];

        $response = wp_remote_post($token_url, $args);

        if (is_wp_error($response)) {
            error_log('Token generation failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!isset($data['access_token'])) {
            error_log('Token generation failed, no access token returned.');
            return false;
        }

        return $data['access_token'];
    }

    public function get_stored_bearer_token() {
        $token = get_option('adso_myfastway_access_token');
        $expires = get_option('adso_myfastway_token_expires', 0);

        if (!$token || time() >= $expires) {
            $token = $this->get_bearer_token();
            if ($token) {
                update_option('adso_myfastway_access_token', $token);
                update_option('adso_myfastway_token_expires', time() + 3600); // Token expires in 1 hour
            }
        }

        return $token;
    }
}