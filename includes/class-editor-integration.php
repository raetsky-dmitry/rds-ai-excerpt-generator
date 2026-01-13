<?php

/**
 * Handles integration with post editor (Gutenberg and Classic)
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Editor_Integration
{

	private $current_post_type = null;

	/**
	 * Initialize editor integration
	 */
	public function init()
	{
		// Check if we should load for current post type
		add_action('current_screen', array($this, 'check_current_screen'));

		// Add meta box for classic editor
		add_action('add_meta_boxes', array($this, 'add_classic_meta_box'));

		// Register Gutenberg plugin
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_gutenberg_assets'));
	}

	/**
	 * Check if we should load integration for current screen
	 */
	public function check_current_screen()
	{
		$screen = get_current_screen();

		if (!$screen || !$screen->post_type) {
			return;
		}

		// Get enabled post types
		$enabled_post_types = rds_ai_excerpt_get_option('enabled_post_types', array('post'));

		// Check if current post type is enabled
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

		// We're on an enabled post type with proper permissions
		$this->current_post_type = $screen->post_type;
	}

	/**
	 * Add meta box for classic editor
	 */
	public function add_classic_meta_box()
	{
		if (!isset($this->current_post_type)) {
			return;
		}

		add_meta_box(
			'rds-ai-excerpt-generator',
			__('AI Excerpt Generator', 'rds-ai-excerpt'),
			array($this, 'render_classic_meta_box'),
			$this->current_post_type,
			'side',
			'high'
		);
	}

	/**
	 * Render classic editor meta box
	 */
	public function render_classic_meta_box($post)
	{
		$post_id = $post->ID ?? 0;
?>
		<div id="rds-ai-excerpt-classic-widget">
			<div class="rds-ai-excerpt-loading" style="display: none;">
				<p><?php _e('Generating excerpt...', 'rds-ai-excerpt'); ?></p>
			</div>

			<div class="rds-ai-excerpt-error" style="display: none;">
				<p class="error-message"></p>
			</div>

			<div class="rds-ai-excerpt-params">
				<p>
					<label for="rds-ai-excerpt-style"><?php _e('Style:', 'rds-ai-excerpt'); ?></label>
					<select id="rds-ai-excerpt-style" class="widefat">
						<option value="descriptive"><?php _e('Descriptive', 'rds-ai-excerpt'); ?></option>
						<option value="advertising"><?php _e('Advertising', 'rds-ai-excerpt'); ?></option>
						<option value="business"><?php _e('Business', 'rds-ai-excerpt'); ?></option>
						<option value="creative"><?php _e('Creative', 'rds-ai-excerpt'); ?></option>
					</select>
				</p>

				<p>
					<label for="rds-ai-excerpt-tone"><?php _e('Tone:', 'rds-ai-excerpt'); ?></label>
					<select id="rds-ai-excerpt-tone" class="widefat">
						<option value="neutral"><?php _e('Neutral', 'rds-ai-excerpt'); ?></option>
						<option value="formal"><?php _e('Formal', 'rds-ai-excerpt'); ?></option>
						<option value="friendly"><?php _e('Friendly', 'rds-ai-excerpt'); ?></option>
						<option value="professional"><?php _e('Professional', 'rds-ai-excerpt'); ?></option>
					</select>
				</p>

				<p>
					<label for="rds-ai-excerpt-language"><?php _e('Language:', 'rds-ai-excerpt'); ?></label>
					<select id="rds-ai-excerpt-language" class="widefat">
						<option value="en">English</option>
						<option value="ru">Русский</option>
						<option value="es">Español</option>
						<option value="fr">Français</option>
						<option value="de">Deutsch</option>
					</select>
				</p>

				<p>
					<label for="rds-ai-excerpt-max-length"><?php _e('Max Length (words):', 'rds-ai-excerpt'); ?></label>
					<input type="number" id="rds-ai-excerpt-max-length" class="widefat" value="150" min="50" max="500">
				</p>

				<p>
					<label for="rds-ai-excerpt-focus-keywords"><?php _e('Focus Keywords (comma-separated):', 'rds-ai-excerpt'); ?></label>
					<input type="text" id="rds-ai-excerpt-focus-keywords" class="widefat" placeholder="keyword1, keyword2, keyword3">
				</p>
			</div>

			<div class="rds-ai-excerpt-actions">
				<button type="button" id="rds-ai-excerpt-generate" class="button button-primary">
					<?php _e('Generate Excerpt', 'rds-ai-excerpt'); ?>
				</button>
			</div>

			<div class="rds-ai-excerpt-result" style="display: none;">
				<hr>
				<h4><?php _e('Generated Excerpt:', 'rds-ai-excerpt'); ?></h4>
				<textarea id="rds-ai-excerpt-output" class="widefat" rows="4" readonly></textarea>
				<div class="rds-ai-excerpt-result-actions">
					<button type="button" id="rds-ai-excerpt-apply" class="button button-secondary">
						<?php _e('Apply to Excerpt', 'rds-ai-excerpt'); ?>
					</button>
					<button type="button" id="rds-ai-excerpt-copy" class="button">
						<?php _e('Copy', 'rds-ai-excerpt'); ?>
					</button>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			jQuery(document).ready(function($) {
				// Widget should already be initialized by classic-editor.js
				// Just set default values if widget data is available
				if (window.rdsAIExcerptWidget && window.rdsAIExcerptWidget.defaults) {
					$('#rds-ai-excerpt-style').val(window.rdsAIExcerptWidget.defaults.style || 'descriptive');
					$('#rds-ai-excerpt-tone').val(window.rdsAIExcerptWidget.defaults.tone || 'neutral');
					$('#rds-ai-excerpt-language').val(window.rdsAIExcerptWidget.defaults.language || 'en');
					$('#rds-ai-excerpt-max-length').val(window.rdsAIExcerptWidget.defaults.maxLength || 150);
				}
			});
		</script>


<?php
	}

	/**
	 * Enqueue Gutenberg assets
	 */
	public function enqueue_gutenberg_assets()
	{
		if (!isset($this->current_post_type)) {
			return;
		}

		wp_enqueue_script(
			'rds-ai-excerpt-gutenberg',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/js/editor-widget.js',
			array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'),
			RDS_AI_EXCERPT_VERSION
		);

		wp_enqueue_style(
			'rds-ai-excerpt-editor',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/css/editor.css',
			array(),
			RDS_AI_EXCERPT_VERSION
		);

		// Get current post ID
		global $post;
		$post_id = $post->ID ?? 0;

		// Localize script
		wp_localize_script('rds-ai-excerpt-gutenberg', 'rdsAIExcerptWidget', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce'   => wp_create_nonce('rds_ai_excerpt_nonce'),
			'postId'  => $post_id,
			'defaults' => array(
				'style'      => rds_ai_excerpt_get_option('default_style', 'descriptive'),
				'tone'       => rds_ai_excerpt_get_option('default_tone', 'neutral'),
				'language'   => rds_ai_excerpt_get_option('default_language', 'en'),
				'maxLength'  => rds_ai_excerpt_get_option('default_max_length', 150),
				'focusKeywords' => rds_ai_excerpt_get_option('default_focus_keywords', ''),
			),
			'strings' => array(
				'title'           => __('AI Excerpt Generator', 'rds-ai-excerpt'),
				'generate'        => __('Generate Excerpt', 'rds-ai-excerpt'),
				'generating'      => __('Generating excerpt...', 'rds-ai-excerpt'),
				'apply'           => __('Apply to Excerpt', 'rds-ai-excerpt'),
				'copy'            => __('Copy', 'rds-ai-excerpt'),
				'style'           => __('Style:', 'rds-ai-excerpt'),
				'tone'            => __('Tone:', 'rds-ai-excerpt'),
				'language'        => __('Language:', 'rds-ai-excerpt'),
				'maxLength'       => __('Max Length (words):', 'rds-ai-excerpt'),
				'focusKeywords'   => __('Focus Keywords:', 'rds-ai-excerpt'),
				'generatedExcerpt' => __('Generated Excerpt:', 'rds-ai-excerpt'),
				'success'         => __('Excerpt generated successfully!', 'rds-ai-excerpt'),
				'applied'         => __('Excerpt applied successfully!', 'rds-ai-excerpt'),
				'copied'          => __('Copied to clipboard!', 'rds-ai-excerpt'),
				'error'           => __('Error:', 'rds-ai-excerpt'),
			),
			'styles' => array(
				'descriptive' => __('Descriptive', 'rds-ai-excerpt'),
				'advertising' => __('Advertising', 'rds-ai-excerpt'),
				'business'    => __('Business', 'rds-ai-excerpt'),
				'creative'    => __('Creative', 'rds-ai-excerpt'),
			),
			'tones' => array(
				'neutral'     => __('Neutral', 'rds-ai-excerpt'),
				'formal'      => __('Formal', 'rds-ai-excerpt'),
				'friendly'    => __('Friendly', 'rds-ai-excerpt'),
				'professional' => __('Professional', 'rds-ai-excerpt'),
			),
			'languages' => array(
				'en' => 'English',
				'ru' => 'Русский',
				'es' => 'Español',
				'fr' => 'Français',
				'de' => 'Deutsch',
			)
		));

		// Set script translations
		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('rds-ai-excerpt-gutenberg', 'rds-ai-excerpt');
		}
	}
}
