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
	 * Activation tasks
	 */
	public static function activate()
	{
		// Set default options
		$default_options = array(
			// API Settings
			'api_base_url' => 'https://api.openai.com/v1',
			'api_model' => 'gpt-3.5-turbo',
			'api_key' => '',

			// Generation Defaults
			'default_style' => 'descriptive',
			'default_tone' => 'neutral',
			'default_language' => 'en',
			'default_max_length' => 150,
			'default_focus_keywords' => '',

			// System Prompt
			'system_prompt' => 'Generate a concise excerpt for a blog post based on the following content. The excerpt should be engaging and accurately represent the main points of the article. Use a {{tone}} tone and {{style}} style. Maximum length: {{max_length}} words. Language: {{language}}.',

			// Post Types
			'enabled_post_types' => array('post'),

			// Security & Limits
			'max_content_length' => 4000,
			'request_timeout' => 30,
			'allowed_user_roles' => array('administrator', 'editor', 'author'),

			// Debug
			'enable_logging' => false,
		);

		// If options don't exist, set defaults
		if (false === get_option('rds_ai_excerpt_settings')) {
			add_option('rds_ai_excerpt_settings', $default_options);
		}

		// Create log directory
		$log_dir = WP_CONTENT_DIR . '/rds-ai-excerpt-logs';
		if (!file_exists($log_dir)) {
			wp_mkdir_p($log_dir);
		}

		// Set plugin version
		update_option('rds_ai_excerpt_version', RDS_AI_EXCERPT_VERSION);

		// Log activation
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('RDS AI Excerpt Generator activated');
		}
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
}
