<?php

/**
 * Plugin Name: RDS AI Excerpt Generator
 * Plugin URI: https://github.com/your-repo/rds-ai-excerpt-generator
 * Description: Automatically generate post excerpts using AI based on post content. Requires RDS AI Engine plugin.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: rds-ai-excerpt
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
	exit;
}

// Define plugin constants
define('RDS_AI_EXCERPT_VERSION', '1.0.0');
define('RDS_AI_EXCERPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RDS_AI_EXCERPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RDS_AI_EXCERPT_BASENAME', plugin_basename(__FILE__));

// Activation and deactivation hooks
register_activation_hook(__FILE__, 'rds_ai_excerpt_activate');
register_deactivation_hook(__FILE__, 'rds_ai_excerpt_deactivate');

/**
 * Plugin activation function
 */
function rds_ai_excerpt_activate()
{
	require_once RDS_AI_EXCERPT_PLUGIN_DIR . 'includes/class-activator.php';
	RDS_AI_Excerpt_Activator::activate();
}

/**
 * Plugin deactivation function
 */
function rds_ai_excerpt_deactivate()
{
	require_once RDS_AI_EXCERPT_PLUGIN_DIR . 'includes/class-activator.php';
	RDS_AI_Excerpt_Activator::deactivate();
}

/**
 * Load plugin classes
 */
function rds_ai_excerpt_load_classes()
{
	// Include all required files
	$includes = array(
		'class-activator',
		'class-admin-settings',
		'class-ajax-handler',
		'class-asset-loader',
		'class-editor-integration',
		'class-post-type-manager',
		'class-engine-integration', // NEW: AI Engine integration
	);

	foreach ($includes as $file) {
		$file_path = RDS_AI_EXCERPT_PLUGIN_DIR . 'includes/' . $file . '.php';
		if (file_exists($file_path)) {
			require_once $file_path;
		}
	}
}

/**
 * Show notice if RDS AI Engine is missing
 */
function rds_ai_excerpt_missing_dependency_notice()
{
	if (!current_user_can('activate_plugins')) {
		return;
	}

?>
	<div class="notice notice-error">
		<p>
			<strong><?php _e('RDS AI Excerpt Generator', 'rds-ai-excerpt'); ?>:</strong>
			<?php _e('This plugin requires RDS AI Engine to be installed and activated.', 'rds-ai-excerpt'); ?>
			<a href="<?php echo admin_url('plugin-install.php?s=RDS+AI+Engine&tab=search&type=term'); ?>">
				<?php _e('Install RDS AI Engine', 'rds-ai-excerpt'); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Show notice if no AI model is selected
 */
function rds_ai_excerpt_no_model_notice()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$screen = get_current_screen();
	if ($screen && $screen->id === 'settings_page_rds-ai-excerpt-settings') {
		return; // Don't show on settings page
	}

	$selected_model = rds_ai_excerpt_get_option('selected_model_id');
	if (empty($selected_model)) {
	?>
		<div class="notice notice-warning">
			<p>
				<strong><?php _e('RDS AI Excerpt Generator', 'rds-ai-excerpt'); ?>:</strong>
				<?php _e('No AI model selected. Please select a model in ', 'rds-ai-excerpt'); ?>
				<a href="<?php echo admin_url('options-general.php?page=rds-ai-excerpt-settings'); ?>">
					<?php _e('plugin settings', 'rds-ai-excerpt'); ?>
				</a>.
			</p>
		</div>
<?php
	}
}

/**
 * Initialize the plugin
 */
function rds_ai_excerpt_init()
{
	// Load classes first
	rds_ai_excerpt_load_classes();

	// Check if RDS AI Engine is available
	$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
	if (!$engine_integration->is_ai_engine_available()) {
		// Show admin notice
		add_action('admin_notices', 'rds_ai_excerpt_missing_dependency_notice');

		// Don't initialize other functionality
		return;
	}

	// Load text domain for translations
	load_plugin_textdomain(
		'rds-ai-excerpt',
		false,
		dirname(RDS_AI_EXCERPT_BASENAME) . '/languages'
	);

	// Check if AI model is selected
	add_action('admin_notices', 'rds_ai_excerpt_no_model_notice');

	// Initialize main classes
	if (is_admin()) {
		$admin_settings = new RDS_AI_Excerpt_Admin_Settings();
		$admin_settings->init();

		$editor_integration = new RDS_AI_Excerpt_Editor_Integration();
		$editor_integration->init();

		$asset_loader = new RDS_AI_Excerpt_Asset_Loader();
		$asset_loader->init();
	}

	// AJAX handler
	$ajax_handler = new RDS_AI_Excerpt_AJAX_Handler();
	$ajax_handler->init();

	// Post type manager
	$post_type_manager = new RDS_AI_Excerpt_Post_Type_Manager();
	$post_type_manager->init();
}

// Hook initialization
add_action('plugins_loaded', 'rds_ai_excerpt_init');

/**
 * Helper function to get plugin option
 */
function rds_ai_excerpt_get_option($option_name, $default = false)
{
	// Get all settings
	$options = get_option('rds_ai_excerpt_settings', array());

	// If options don't exist or not an array, return default
	if (!is_array($options)) {
		return $default;
	}

	// Check if option exists
	if (isset($options[$option_name])) {
		$value = $options[$option_name];

		// For numeric defaults, ensure value is numeric
		if (is_numeric($default) && is_numeric($value)) {
			return (int) $value;
		}

		return $value;
	}

	return $default;
}

/**
 * Helper function to log messages for debugging
 */
function rds_ai_excerpt_log($message, $type = 'info')
{
	// Check if logging is enabled
	$enable_logging = rds_ai_excerpt_get_option('enable_logging', false);

	// If logging is disabled and no global debug constant, return
	if (!$enable_logging && (!defined('RDS_AI_EXCERPT_DEBUG') || !constant('RDS_AI_EXCERPT_DEBUG'))) {
		return;
	}

	$log_dir = WP_CONTENT_DIR . '/rds-ai-excerpt-logs';

	// Create log directory if it doesn't exist
	if (!file_exists($log_dir)) {
		wp_mkdir_p($log_dir);
	}

	$log_file = $log_dir . '/debug.log';
	$timestamp = current_time('mysql');
	$log_entry = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($type), $message);

	// Write to log file
	file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Helper function to check plugin requirements
 */
function rds_ai_excerpt_check_requirements()
{
	$errors = array();

	// Check PHP version
	if (version_compare(PHP_VERSION, '7.4', '<')) {
		$errors[] = sprintf(
			__('PHP version 7.4 or higher is required. You are running version %s.', 'rds-ai-excerpt'),
			PHP_VERSION
		);
	}

	// Check WordPress version
	global $wp_version;
	if (version_compare($wp_version, '6.0', '<')) {
		$errors[] = sprintf(
			__('WordPress version 6.0 or higher is required. You are running version %s.', 'rds-ai-excerpt'),
			$wp_version
		);
	}

	// Check if RDS AI Engine is active
	if (!class_exists('RDS_AIE_Main')) {
		$errors[] = __('RDS AI Engine plugin is required. Please install and activate it first.', 'rds-ai-excerpt');
	}

	return $errors;
}

/**
 * Get available AI models for dropdown
 */
function rds_ai_excerpt_get_models_for_dropdown()
{
	$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
	$models = $engine_integration->get_available_models();

	$options = array('' => __('-- Select Model --', 'rds-ai-excerpt'));

	if (is_array($models)) {
		foreach ($models as $model) {
			if (isset($model['id']) && isset($model['name'])) {
				$options[$model['id']] = esc_html($model['name']);
			}
		}
	}

	return $options;
}

/**
 * Get selected model name
 */
function rds_ai_excerpt_get_selected_model_name()
{
	$model_id = rds_ai_excerpt_get_option('selected_model_id');
	if (empty($model_id)) {
		return __('No model selected', 'rds-ai-excerpt');
	}

	$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
	return $engine_integration->get_model_name($model_id);
}
