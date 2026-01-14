<?php

/**
 * Handles loading of scripts and styles
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Asset_Loader
{

	/**
	 * Initialize asset loader
	 */
	public function init()
	{
		// Load assets for classic editor
		add_action('admin_enqueue_scripts', array($this, 'enqueue_classic_editor_assets'));
	}

	/**
	 * Enqueue classic editor assets
	 */
	public function enqueue_classic_editor_assets($hook)
	{
		// Only load on post edit pages
		if (!in_array($hook, array('post.php', 'post-new.php'))) {
			return;
		}

		// Check if we're using Gutenberg - более надежная проверка
		$screen = get_current_screen();
		if (!$screen) {
			return;
		}

		// Метод 1: Современный способ
		if (method_exists($screen, 'is_block_editor') && $screen->is_block_editor()) {
			return; // Не загружаем для Gutenberg
		}

		// Метод 2: Проверка по классам body (fallback)
		add_action('admin_footer', function () {
			echo '<script>
        if (document.body.classList.contains("block-editor-page") || 
            document.querySelector(".edit-post-layout")) {
            // Скрываем метабокс если вдруг загрузился
            document.getElementById("rds-ai-excerpt-generator")?.remove();
        }
        </script>';
		});

		// Проверяем остальные условия...
		if (!$screen || !$screen->post_type) {
			return;
		}

		$enabled_post_types = rds_ai_excerpt_get_option('enabled_post_types', array('post'));
		if (!in_array($screen->post_type, $enabled_post_types)) {
			return;
		}

		// Check user role permissions
		$allowed_roles = rds_ai_excerpt_get_option('allowed_user_roles', array('administrator', 'editor', 'author'));
		$user = wp_get_current_user();
		$has_permission = false;

		foreach ($allowed_roles as $role) {
			if (in_array($role, $user->roles)) {
				$has_permission = true;
				break;
			}
		}

		if (!$has_permission) {
			return;
		}

		// Get current post ID
		global $post;
		$post_id = $post->ID ?? 0;

		// Enqueue styles
		wp_enqueue_style(
			'rds-ai-excerpt-editor',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/css/editor.css',
			array(),
			RDS_AI_EXCERPT_VERSION
		);

		// Enqueue script
		wp_enqueue_script(
			'rds-ai-excerpt-classic-editor',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/js/classic-editor.js',
			array('jquery'),
			RDS_AI_EXCERPT_VERSION,
			true
		);

		// Localize script ТОЛЬКО для классического редактора
		wp_localize_script('rds-ai-excerpt-classic-editor', 'rdsAIExcerptWidget', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('rds_ai_excerpt_nonce'),
			'postId'  => $post_id,
			'isGutenberg' => false, // Явно указываем, что это не Gutenberg
			'defaults' => array(
				'style'      => rds_ai_excerpt_get_option('default_style', 'descriptive'),
				'tone'       => rds_ai_excerpt_get_option('default_tone', 'neutral'),
				'language'   => rds_ai_excerpt_get_option('default_language', 'en'),
				'maxLength'  => rds_ai_excerpt_get_option('default_max_length', 150),
				'focusKeywords' => rds_ai_excerpt_get_option('default_focus_keywords', ''),
			),
			'strings' => array(
				'generating'      => __('Generating excerpt...', 'rds-ai-excerpt'),
				'success'         => __('Excerpt generated successfully!', 'rds-ai-excerpt'),
				'applied'         => __('Excerpt applied successfully!', 'rds-ai-excerpt'),
				'copied'          => __('Copied to clipboard!', 'rds-ai-excerpt'),
				'error'           => __('Error:', 'rds-ai-excerpt'),
			)
		));
	}
}
