<?php

/**
 * Manages post type settings and restrictions
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Post_Type_Manager
{

	/**
	 * Initialize post type manager
	 */
	public function init()
	{
		// Register settings for custom post types
		add_filter('rds_ai_excerpt_post_type_options', array($this, 'get_post_type_options'));
	}

	/**
	 * Get available post type options for settings
	 */
	public function get_post_type_options($options)
	{
		$post_types = get_post_types(array(
			'public' => true,
		), 'objects');

		$post_type_options = array();

		foreach ($post_types as $post_type) {
			// Skip attachments
			if ($post_type->name === 'attachment') {
				continue;
			}

			$post_type_options[$post_type->name] = $post_type->labels->singular_name;
		}

		return $post_type_options;
	}

	/**
	 * Check if plugin is enabled for specific post type
	 */
	public static function is_enabled_for_post_type($post_type)
	{
		$enabled_post_types = rds_ai_excerpt_get_option('enabled_post_types', array('post'));
		return in_array($post_type, $enabled_post_types);
	}

	/**
	 * Check if current user has permission to use the plugin
	 */
	public static function current_user_can_use()
	{
		$allowed_roles = rds_ai_excerpt_get_option('allowed_user_roles', array('administrator', 'editor', 'author'));
		$user = wp_get_current_user();

		foreach ($allowed_roles as $role) {
			if (in_array($role, $user->roles)) {
				return true;
			}
		}

		return false;
	}
}
