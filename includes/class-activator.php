<?php

/**
 * Handles activation and deactivation tasks
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Activator
{

	/**
	 * Activation tasks with requirement checks
	 */
	public static function activate()
	{
		// Check requirements
		$errors = self::check_requirements();

		if (!empty($errors)) {
			// Deactivate plugin
			deactivate_plugins(plugin_basename(__FILE__));

			// Show error message
			wp_die(
				'<h1>' . __('Plugin Activation Error', 'rds-ai-excerpt') . '</h1>' .
					'<p>' . __('RDS AI Excerpt Generator cannot be activated:', 'rds-ai-excerpt') . '</p>' .
					'<ul><li>' . implode('</li><li>', $errors) . '</li></ul>' .
					'<p><a href="' . admin_url('plugins.php') . '">' . __('Return to plugins page', 'rds-ai-excerpt') . '</a></p>'
			);
		}

		// Set default options (остальной код остается)
		$default_options = array(
			// Connection
			'selected_model_id' => '',

			// Generation Defaults
			'default_style' => 'descriptive',
			'default_tone' => 'neutral',
			'default_language' => 'en',
			'default_max_length' => 150,
			'default_focus_keywords' => '',

			// System Prompt
			'system_prompt' => self::get_default_system_prompt(),

			// Post Types
			'enabled_post_types' => array('post'),

			// Security & Limits
			'max_content_length' => 4000,
			'allowed_user_roles' => array('administrator', 'editor', 'author'),

			// Debug
			'enable_logging' => false,
		);

		// If options don't exist, set defaults
		if (false === get_option('rds_ai_excerpt_settings')) {
			add_option('rds_ai_excerpt_settings', $default_options);
		}

		// Set plugin version
		update_option('rds_ai_excerpt_version', '1.0.0');
	}

	/**
	 * Get default system prompt
	 */
	private static function get_default_system_prompt()
	{
		return 'Generate a concise and engaging excerpt for a blog post.

Post Content:
{{content}}

Title: {{title}}
Writing Style: {{style}}
Tone: {{tone}}
Target Length: {{max_length}} words
Language: {{language}}
Keywords to focus on: {{focus_keywords}}

Requirements:
1. Capture the essence and main points
2. Make it compelling to read the full article
3. Use natural, flowing language
4. Do not use markdown, quotes, or special formatting
5. Output only the excerpt text';
	}

	/**
	 * Deactivation tasks
	 */
	public static function deactivate()
	{
		// Clean up scheduled events if any
		wp_clear_scheduled_hook('rds_ai_excerpt_daily_cleanup');

		// Log deactivation
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('RDS AI Excerpt Generator deactivated');
		}
	}

	/**
	 * Uninstall tasks (called from uninstall.php)
	 */
	public static function uninstall()
	{
		// Delete options
		delete_option('rds_ai_excerpt_settings');
		delete_option('rds_ai_excerpt_version');

		// Delete log directory
		$log_dir = WP_CONTENT_DIR . '/rds-ai-excerpt-logs';
		if (file_exists($log_dir)) {
			self::delete_directory($log_dir);
		}
	}

	/**
	 * Recursively delete directory
	 */
	private static function delete_directory($dir)
	{
		if (!file_exists($dir)) {
			return true;
		}

		if (!is_dir($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			if (!self::delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}
		}

		return rmdir($dir);
	}

	/**
	 * Check plugin requirements
	 */
	public static function check_requirements()
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
}
