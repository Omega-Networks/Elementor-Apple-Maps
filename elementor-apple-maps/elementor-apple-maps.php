<?php
/**
 * Plugin Name: Elementor Apple Maps Widget
 * Description: A custom Elementor widget to embed Apple Maps in your WordPress site
 * Version: 1.0.0
 * Author: Leon Cassidy, Omega Networks
 * Text Domain: elementor-apple-maps
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Initialize the plugin
function elementor_apple_maps_widget_init() {
    return Elementor_Apple_Maps_Widget_Plugin::instance();
}

// Global instance for the plugin
$GLOBALS['elementor_apple_maps'] = elementor_apple_maps_widget_init();

// Directory structure creation upon activation
register_activation_hook(__FILE__, 'elementor_apple_maps_activate');

function elementor_apple_maps_activate() {
    // Create directories if they don't exist
    $dirs = array(
        ELEMENTOR_APPLE_MAPS_PATH . 'assets',
        ELEMENTOR_APPLE_MAPS_PATH . 'assets/js',
        ELEMENTOR_APPLE_MAPS_PATH . 'assets/css',
        ELEMENTOR_APPLE_MAPS_PATH . 'assets/images',
        ELEMENTOR_APPLE_MAPS_PATH . 'includes',
        ELEMENTOR_APPLE_MAPS_PATH . 'widgets',
    );
    
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
    
    // Create .htaccess file to protect private key
    $htaccess_file = ELEMENTOR_APPLE_MAPS_PATH . '.htaccess';
    if (!file_exists($htaccess_file)) {
        $htaccess_content = "# Prevent direct access to PHP files
<FilesMatch \"\.php$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow access to main plugin file
<Files \"elementor-apple-maps.php\">
    Order Allow,Deny
    Allow from all
</Files>

# Protect sensitive data
<FilesMatch \"jwt-generator\.php$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>
";
        file_put_contents($htaccess_file, $htaccess_content);
    }
}

// Clean up on uninstall
register_uninstall_hook(__FILE__, 'elementor_apple_maps_uninstall');

function elementor_apple_maps_uninstall() {
    // Delete plugin options
    delete_option('apple_maps_settings');
}


/**
 * Main Elementor Apple Maps Widget Class
 */
final class Elementor_Apple_Maps_Widget_Plugin {

    /**
     * Plugin Version
     */
    const VERSION = '1.0.0';

    /**
     * Minimum Elementor Version
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

    /**
     * Minimum PHP Version
     */
    const MINIMUM_PHP_VERSION = '7.4';

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Instance
     * Ensures only one instance of the class is loaded or can be loaded.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Define constants
        $this->define_constants();

        // Load translation
        add_action('init', array($this, 'i18n'));

        // Include JWT Generator
        require_once(ELEMENTOR_APPLE_MAPS_PATH . 'includes/jwt-generator.php');
        
        // Initialize the plugin
        add_action('plugins_loaded', array($this, 'init'));

        // Add settings page
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Add AJAX endpoints
        add_action('wp_ajax_get_mapkit_jwt', array($this, 'get_mapkit_jwt'));
        add_action('wp_ajax_nopriv_get_mapkit_jwt', array($this, 'get_mapkit_jwt'));
        add_action('wp_ajax_test_mapkit_credentials', array($this, 'test_mapkit_credentials'));
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('ELEMENTOR_APPLE_MAPS_VERSION', self::VERSION);
        define('ELEMENTOR_APPLE_MAPS_URL', plugins_url('/', __FILE__));
        define('ELEMENTOR_APPLE_MAPS_PATH', plugin_dir_path(__FILE__));
        define('ELEMENTOR_APPLE_MAPS_BASENAME', plugin_basename(__FILE__));
    }
    
    /**
     * Add settings link to plugin action links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=apple-maps-settings') . '">' . __('Settings', 'elementor-apple-maps') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Load Textdomain
     */
    public function i18n() {
        load_plugin_textdomain('elementor-apple-maps');
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Elementor is installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', array($this, 'admin_notice_missing_elementor'));
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_php_version'));
            return;
        }

        // Register the widget
        add_action('elementor/widgets/register', array($this, 'register_widgets'));

        // Register scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_scripts'));
        
        // Add options
        $this->add_options();
    }
    
    /**
     * Add options
     */
    public function add_options() {
        add_option('apple_maps_settings', array(
            'maps_private_key' => '',
            'maps_key_id' => '',
            'maps_team_id' => '',
            'mapkit_status' => ''
        ));
    }

    /**
     * Register Widget
     */
    public function register_widgets($widgets_manager) {
        // Include Widget files
        require_once(ELEMENTOR_APPLE_MAPS_PATH . 'widgets/apple-maps-widget.php');

        // Register widget
        $widgets_manager->register(new \Elementor_Apple_Maps());
    }

    /**
     * Register Scripts
     */
	public function register_scripts() {
	    // Remove any previous instances that might be causing conflicts
	    wp_deregister_script('apple-mapkit-js');
	    
	    // Register Apple MapKit JS with proper dependencies and loading strategy
	    wp_register_script(
	        'apple-mapkit-js',
	        'https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js',
	        array(), // No dependencies
	        self::VERSION,
	        true // Load in footer
	    );
	    
	    // Register plugin JS with jQuery dependency but NOT mapkit dependency
	    wp_register_script(
	        'elementor-apple-maps-js',
	        ELEMENTOR_APPLE_MAPS_URL . 'assets/js/apple-maps.js',
	        array('jquery'), // Only depend on jQuery, not on mapkit
	        self::VERSION,
	        true
	    );
	    
	    // Don't enqueue scripts here, let the widget do it when needed
	    
	    // Localize script
	    wp_localize_script(
	        'elementor-apple-maps-js',
	        'ElementorAppleMapsSettings',
	        array(
	            'ajaxUrl' => admin_url('admin-ajax.php'),
	            'nonce' => wp_create_nonce('elementor_apple_maps_nonce')
	        )
	    );
	    
	    // Register plugin CSS
	    wp_register_style(
	        'elementor-apple-maps-css',
	        ELEMENTOR_APPLE_MAPS_URL . 'assets/css/apple-maps.css',
	        array(),
	        self::VERSION
	    );
	}
    
    /**
     * Register Admin Scripts
     */
    public function register_admin_scripts($hook) {
        if ('settings_page_apple-maps-settings' !== $hook) {
            return;
        }
        
        // Register admin CSS
        wp_register_style(
            'elementor-apple-maps-admin-css',
            ELEMENTOR_APPLE_MAPS_URL . 'assets/css/admin-apple-maps-settings.css',
            array(),
            self::VERSION
        );
        wp_enqueue_style('elementor-apple-maps-admin-css');
        
        // Register admin JS
        wp_register_script(
            'elementor-apple-maps-admin-js',
            ELEMENTOR_APPLE_MAPS_URL . 'assets/js/admin-settings.js',
            array('jquery', 'apple-mapkit-js'),
            self::VERSION,
            true
        );
        
        // Localize the admin script with translations and data
        wp_localize_script(
            'elementor-apple-maps-admin-js',
            'ElementorAppleMapsAdmin',
            array(
                'nonce' => wp_create_nonce('elementor_apple_maps_nonce'),
                'testing' => __('Testing credentials...', 'elementor-apple-maps'),
                'authorized' => __('Authorized!', 'elementor-apple-maps'),
                'invalid' => __('Invalid credentials!', 'elementor-apple-maps'),
                'connectionError' => __('Connection error', 'elementor-apple-maps'),
                'pendingChanges' => __('Pending changes...', 'elementor-apple-maps'),
                'saving' => __('Saving...', 'elementor-apple-maps')
            )
        );
        
        wp_enqueue_script('elementor-apple-maps-admin-js');
        
        // Load MapKit JS in admin
        wp_enqueue_script('apple-mapkit-js');
    }
    
    /**
     * AJAX handler for JWT token generation
     */
    public function get_mapkit_jwt() {
        $options = get_option('apple_maps_settings');
        
        if (empty($options['maps_private_key']) || empty($options['maps_key_id']) || empty($options['maps_team_id'])) {
            wp_send_json_error('MapKit JS credentials are not configured', 401);
            return;
        }
        
        // Generate JWT token
        $token = Elementor_Apple_Maps_JWT::generate_token(
            $options['maps_key_id'],
            $options['maps_team_id'],
            $options['maps_private_key'],
            3600 // 1 hour expiration
        );
        
        if (is_wp_error($token)) {
            wp_send_json_error($token->get_error_message(), 500);
            return;
        }
        
        // Echo the token directly like the block plugin does
        echo $token;
        exit;
    }
    
    /**
     * AJAX handler for testing MapKit credentials
     */
    public function test_mapkit_credentials() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'elementor_apple_maps_nonce')) {
            wp_send_json_error('Invalid security token', 403);
            return;
        }
        
        // Get credentials from POST
        $private_key = isset($_POST['private_key']) ? $_POST['private_key'] : '';
        $key_id = isset($_POST['key_id']) ? $_POST['key_id'] : '';
        $team_id = isset($_POST['team_id']) ? $_POST['team_id'] : '';
        
        if (empty($private_key) || empty($key_id) || empty($team_id)) {
            wp_send_json_error('Missing required credentials', 400);
            return;
        }
        
        // Generate a test token
        $token = Elementor_Apple_Maps_JWT::generate_token(
            $key_id,
            $team_id,
            $private_key,
            60 // Short expiration for testing
        );
        
        if (is_wp_error($token)) {
            wp_send_json_error($token->get_error_message());
            return;
        }
        
        wp_send_json_success($token);
    }

    /**
     * Admin notice for missing Elementor
     */
    public function admin_notice_missing_elementor() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'elementor-apple-maps'),
            '<strong>' . esc_html__('Elementor Apple Maps Widget', 'elementor-apple-maps') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor-apple-maps') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice for minimum Elementor version
     */
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-apple-maps'),
            '<strong>' . esc_html__('Elementor Apple Maps Widget', 'elementor-apple-maps') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor-apple-maps') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice for minimum PHP version
     */
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-apple-maps'),
            '<strong>' . esc_html__('Elementor Apple Maps Widget', 'elementor-apple-maps') . '</strong>',
            '<strong>' . esc_html__('PHP', 'elementor-apple-maps') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Add settings page
     */
    public function add_plugin_page() {
        add_options_page(
            'Apple Maps Settings',
            'Apple Maps',
            'manage_options',
            'apple-maps-settings',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Settings page callback
     */
    public function create_admin_page() {
        // Set class property
        $this->options = get_option('apple_maps_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Apple Maps Settings', 'elementor-apple-maps'); ?></h1>
            <div class="maps-block-apple-settings">
                <form method="post" action="options.php">
                <?php
                    // This prints out all hidden setting fields
                    settings_fields('apple_maps_option_group');
                    do_settings_sections('apple-maps-settings');
                    submit_button(__('Confirm MapKit Credentials', 'elementor-apple-maps'));
                ?>
                </form>
                <div class="info-links">
                    <h3><?php echo esc_html__('Helpful Resources', 'elementor-apple-maps'); ?></h3>
                    <ul>
                        <li>
                            <a href="https://developer.apple.com/documentation/mapkitjs" target="_blank" rel="noopener">
                                <?php echo esc_html__('MapKit JS Documentation', 'elementor-apple-maps'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </li>
                        <li>
                            <a href="https://developer.apple.com/maps/mapkitjs/" target="_blank" rel="noopener">
                                <?php echo esc_html__('Apple Maps for Web', 'elementor-apple-maps'); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init() {
        register_setting(
            'apple_maps_option_group', // Option group
            'apple_maps_settings', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'apple_maps_setting_section', // ID
            'MapKit JS Credentials', // Title
            array($this, 'print_section_info'), // Callback
            'apple-maps-settings' // Page
        );

        add_settings_field(
            'maps_private_key', // ID
            'Private Key', // Title
            array($this, 'maps_private_key_callback'), // Callback
            'apple-maps-settings', // Page
            'apple_maps_setting_section' // Section
        );
        
        add_settings_field(
            'maps_key_id', // ID
            'Key ID', // Title
            array($this, 'maps_key_id_callback'), // Callback
            'apple-maps-settings', // Page
            'apple_maps_setting_section' // Section
        );
        
        add_settings_field(
            'maps_team_id', // ID
            'Team ID', // Title
            array($this, 'maps_team_id_callback'), // Callback
            'apple-maps-settings', // Page
            'apple_maps_setting_section' // Section
        );
        
        add_settings_field(
            'mapkit_status', // ID
            'MapKit Status', // Title
            array($this, 'mapkit_status_callback'), // Callback
            'apple-maps-settings', // Page
            'apple_maps_setting_section' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input) {
        error_log('[Elementor Apple Maps] sanitize() method started');
        
        $new_input = array();
        
        if (isset($input['maps_private_key'])) {
            // Preserve line breaks in private key but don't log the actual key
            $new_input['maps_private_key'] = sanitize_textarea_field($input['maps_private_key']);
            error_log('[Elementor Apple Maps] Private key provided: ' . (empty($new_input['maps_private_key']) ? 'No' : 'Yes (content hidden for security)'));
        } else {
            error_log('[Elementor Apple Maps] No private key provided in form submission');
        }
        
        if (isset($input['maps_key_id'])) {
            $new_input['maps_key_id'] = sanitize_text_field($input['maps_key_id']);
            error_log('[Elementor Apple Maps] Key ID provided: ' . (empty($new_input['maps_key_id']) ? 'No' : 'Yes - "' . $new_input['maps_key_id'] . '"'));
        } else {
            error_log('[Elementor Apple Maps] No Key ID provided in form submission');
        }
        
        if (isset($input['maps_team_id'])) {
            $new_input['maps_team_id'] = sanitize_text_field($input['maps_team_id']);
            error_log('[Elementor Apple Maps] Team ID provided: ' . (empty($new_input['maps_team_id']) ? 'No' : 'Yes - "' . $new_input['maps_team_id'] . '"'));
        } else {
            error_log('[Elementor Apple Maps] No Team ID provided in form submission');
        }
        
        // Default to empty status
        $new_input['mapkit_status'] = '';
        
        // Check if credentials are provided
        if (!empty($new_input['maps_private_key']) && 
            !empty($new_input['maps_key_id']) && 
            !empty($new_input['maps_team_id'])) {
            
            error_log('[Elementor Apple Maps] All required credentials provided, attempting validation');
            
            try {
                // Validate credentials by generating a test token
                error_log('[Elementor Apple Maps] Calling validate_credentials() method');
                $validation = Elementor_Apple_Maps_JWT::validate_credentials(
                    $new_input['maps_key_id'],
                    $new_input['maps_team_id'],
                    $new_input['maps_private_key']
                );
                
                if (is_wp_error($validation)) {
                    error_log('[Elementor Apple Maps] Validation returned WP_Error: ' . $validation->get_error_code() . ' - ' . $validation->get_error_message());
                    add_settings_error(
                        'apple_maps_settings',
                        'invalid_credentials',
                        'Invalid MapKit JS credentials: ' . $validation->get_error_message(),
                        'error'
                    );
                    error_log('[Elementor Apple Maps] Added settings error: invalid_credentials');
                } else {
                    error_log('[Elementor Apple Maps] Validation successful, setting status to "authorized"');
                    $new_input['mapkit_status'] = 'authorized';
                }
            } catch (Exception $e) {
                error_log('[Elementor Apple Maps] Exception caught during validation: ' . $e->getMessage());
                error_log('[Elementor Apple Maps] Exception stack trace: ' . $e->getTraceAsString());
                add_settings_error(
                    'apple_maps_settings',
                    'validation_exception',
                    'Failed to validate MapKit credentials due to a server error: ' . $e->getMessage(),
                    'error'
                );
                error_log('[Elementor Apple Maps] Added settings error: validation_exception');
            }
        } else {
            error_log('[Elementor Apple Maps] One or more required credentials missing, skipping validation');
        }

        error_log('[Elementor Apple Maps] Final mapkit_status: ' . ($new_input['mapkit_status'] ?: 'empty'));
        error_log('[Elementor Apple Maps] sanitize() method completed successfully');
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info() {
        ?>
        <section class="credentials-instructions">
            <p><?php echo esc_html__('In order to start using the Apple Maps widget, you will need to sign up for the Apple Developer Program and create your Maps identifiers, keys, and tokens.', 'elementor-apple-maps'); ?></p>
            <p><?php echo esc_html__('Follow the steps below to generate the Private Key, Key ID, and Team ID that you will need to configure the plugin and gain access to the MapKit JS API for the Apple Maps widget.', 'elementor-apple-maps'); ?></p>
            <h4><?php esc_html_e('1. Create an Apple Developer account', 'elementor-apple-maps'); ?></a></h4>
            <ul>
                <li><a href="https://developer.apple.com/programs/enroll/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Enroll in the Apple Developer Program as either an individual or organization.', 'elementor-apple-maps'); ?></a></li>
                <?php /* translators: %s is the URL of App Store Connect Contracts page. */ ?>
                <li><?php echo wp_kses_post(sprintf(__('Sign the Apple Developer Program License Agreement in the <a href="%s" target="_blank" rel="noopener noreferrer">Agreements, Tax, and Banking section of App Store Connect.</a>', 'elementor-apple-maps'), esc_url('https://appstoreconnect.apple.com/WebObjects/iTunesConnect.woa/da/jumpTo?page=contracts'))); ?></li>
            </ul>
            <h4><?php esc_html_e('2. Create a Maps Identifier and Private Key', 'elementor-apple-maps'); ?></a></h4>
            <ul>
                <li><a href="https://developer.apple.com/documentation/mapkitjs/creating_a_maps_identifier_and_a_private_key" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Create a Maps ID and a MapKit JS Private Key.', 'elementor-apple-maps'); ?></a></li>
                <li><?php echo wp_kses_data(__('Copy the Private Key, paste it into the respective field below, and ensure the key includes the <code>-----BEGIN PRIVATE KEY-----</code> and <code>-----END PRIVATE KEY-----</code> lines.', 'elementor-apple-maps')); ?></li>
                <li><?php echo wp_kses_data(__('Open the Key you created above, copy the <code>Key ID</code> value, and paste it into the respective field below.', 'elementor-apple-maps')); ?></li>
                <li><?php echo wp_kses_data(__('Open the Identifier you created above, copy the <code>App ID Prefix</code> value (notice the value is appended with <code>(Team ID)</code>), and paste it into the respective field below.', 'elementor-apple-maps')); ?></li>
                <li><?php echo wp_kses_data(__('Click the <code>Confirm MapKit Credentials</code> button below to gain access to the widget options and begin customizing your Apple Maps widget!', 'elementor-apple-maps')); ?></li>
            </ul>
        </section>
        <?php
    }

    /**
     * Private Key field callback
     */
    public function maps_private_key_callback() {
        $key  = 'maps_private_key';
        $name = "apple_maps_settings[$key]";
        ?>
        <div id="authkey-container">
            <textarea name="<?php echo esc_attr($name); ?>" class="large-text" rows="10" id="token-gen-authkey" placeholder="<?php esc_html_e('Paste your Private Key here', 'elementor-apple-maps'); ?>"><?php echo esc_textarea(isset($this->options[$key]) ? $this->options[$key] : ''); ?></textarea>
            <p class="description"><?php esc_html_e('Paste your private key, including the BEGIN and END lines.', 'elementor-apple-maps'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Key ID field callback
     */
    public function maps_key_id_callback() {
        $key  = 'maps_key_id';
        $name = "apple_maps_settings[$key]";
        ?>
        <input type="text" id="token-gen-kid" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr(isset($this->options[$key]) ? $this->options[$key] : ''); ?>" class="regular-text" placeholder="<?php esc_html_e('Your 10-character Key ID', 'elementor-apple-maps'); ?>" />
        <p class="description"><?php esc_html_e('The Key ID value from your MapKit JS key.', 'elementor-apple-maps'); ?></p>
        <?php
    }
    
    /**
     * Team ID field callback
     */
    public function maps_team_id_callback() {
        $key  = 'maps_team_id';
        $name = "apple_maps_settings[$key]";
        ?>
        <input type="text" id="token-gen-iss" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr(isset($this->options[$key]) ? $this->options[$key] : ''); ?>" class="regular-text" placeholder="<?php esc_html_e('Your 10-character Team ID', 'elementor-apple-maps'); ?>" />
        <p class="description"><?php esc_html_e('Your Team ID from the Apple Developer account.', 'elementor-apple-maps'); ?></p>
        <?php
    }
    
    /**
     * MapKit Status field callback
     */
    public function mapkit_status_callback() {
        $status = isset($this->options['mapkit_status']) ? $this->options['mapkit_status'] : '';
        $status_class = ($status === 'authorized') ? 'mapkit-valid' : 'mapkit-error';
        $status_text = ($status === 'authorized') ? __('Authorized!', 'elementor-apple-maps') : __('Not Authorized', 'elementor-apple-maps');
        ?>
        <div id="mapkit-credentials-status" class="<?php echo esc_attr($status_class); ?>">
            <?php echo esc_html($status_text); ?>
        </div>
        <p class="description">
            <?php esc_html_e('Status of your MapKit JS configuration.', 'elementor-apple-maps'); ?>
        </p>
        <?php
    }
}