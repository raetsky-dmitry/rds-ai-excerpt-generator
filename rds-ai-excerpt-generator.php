<?php

/**
 * Plugin Name: RDS AI Excerpt Generator
 * Plugin URI: https://github.com/your-repo/rds-ai-excerpt-generator
 * Description: Automatically generate post excerpts using AI based on post content.
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
	);

	foreach ($includes as $file) {
		$file_path = RDS_AI_EXCERPT_PLUGIN_DIR . 'includes/' . $file . '.php';
		if (file_exists($file_path)) {
			require_once $file_path;
		}
	}
}

/**
 * Initialize the plugin
 */
function rds_ai_excerpt_init()
{
	// Load classes first
	rds_ai_excerpt_load_classes();

	// Load text domain for translations
	load_plugin_textdomain(
		'rds-ai-excerpt',
		false,
		dirname(RDS_AI_EXCERPT_BASENAME) . '/languages'
	);

	// Initialize main classes
	if (is_admin()) {
		$admin_settings = new RDS_AI_Excerpt_Admin_Settings();
		$admin_settings->init();

		$editor_integration = new RDS_AI_Excerpt_Editor_Integration();
		$editor_integration->init();

		$asset_loader = new RDS_AI_Excerpt_Asset_Loader();
		$asset_loader->init();
	}

	// AJAX handler (available in both admin and frontend for potential future features)
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
	// Получаем все настройки
	$options = get_option('rds_ai_excerpt_settings', array());

	// Если опции не существуют или не массив, возвращаем значение по умолчанию
	if (!is_array($options)) {
		return $default;
	}

	// Проверяем наличие опции
	if (isset($options[$option_name])) {
		$value = $options[$option_name];

		// Для числовых значений убеждаемся, что они числа
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
	if (!defined('RDS_AI_EXCERPT_DEBUG') || !RDS_AI_EXCERPT_DEBUG) {
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
 * Debug function to check plugin options
 */
function rds_ai_excerpt_debug_options()
{
	if (!current_user_can('manage_options')) {
		return;
	}

	$options = get_option('rds_ai_excerpt_settings', array());
	echo '<pre>';
	echo 'Options array structure:<br>';
	print_r($options);
	echo '</pre>';

	echo '<pre>';
	echo 'Specific option check:<br>';
	echo 'max_content_length: ' . rds_ai_excerpt_get_option('max_content_length', 'NOT FOUND') . '<br>';
	echo 'Type: ' . gettype(rds_ai_excerpt_get_option('max_content_length')) . '<br>';
	echo '</pre>';
}
// Для тестирования можно временно добавить этот хук:
// add_action('admin_notices', 'rds_ai_excerpt_debug_options');
