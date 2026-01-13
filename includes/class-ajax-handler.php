<?php

/**
 * Handles AJAX requests for AI excerpt generation
 * Now integrated with RDS AI Engine
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_AJAX_Handler
{

	/**
	 * Initialize AJAX handlers
	 */
	public function init()
	{
		add_action('wp_ajax_rds_ai_generate_excerpt', array($this, 'handle_generate_excerpt'));
		add_action('wp_ajax_rds_ai_test_connection', array($this, 'handle_test_connection'));
	}

	/**
	 * Handle excerpt generation request
	 */
	public function handle_generate_excerpt()
	{
		try {
			// Verify nonce
			if (!check_ajax_referer('rds_ai_excerpt_nonce', 'nonce', false)) {
				throw new Exception(__('Security check failed.', 'rds-ai-excerpt'));
			}

			// Check capabilities
			if (!current_user_can('edit_posts')) {
				throw new Exception(__('Insufficient permissions.', 'rds-ai-excerpt'));
			}

			// Get post ID
			$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
			if (!$post_id) {
				throw new Exception(__('Invalid post ID.', 'rds-ai-excerpt'));
			}

			// Get parameters
			$params = array(
				'style'          => isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '',
				'tone'           => isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : '',
				'language'       => isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '',
				'max_length'     => isset($_POST['max_length']) ? absint($_POST['max_length']) : 0,
				'focus_keywords' => isset($_POST['focus_keywords']) ? sanitize_text_field($_POST['focus_keywords']) : '',
			);

			// Add post_id to params for session tracking
			$params['post_id'] = $post_id;

			// Get post content
			$post = get_post($post_id);
			if (!$post) {
				throw new Exception(__('Post not found.', 'rds-ai-excerpt'));
			}

			// Prepare content
			$content = $this->prepare_content($post);
			$post_title = $post->post_title;

			// Log request if enabled
			if (rds_ai_excerpt_get_option('enable_logging')) {
				rds_ai_excerpt_log(
					sprintf(
						'Excerpt generation request for post #%d. Params: %s',
						$post_id,
						json_encode($params)
					),
					'info'
				);
			}

			// Generate excerpt using AI Engine
			$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
			$result = $engine_integration->generate_excerpt($content, $post_title, $params);

			if (is_wp_error($result)) {
				throw new Exception($result->get_error_message());
			}

			wp_send_json(array(
				'success' => true,
				'excerpt' => $result
			));
		} catch (Exception $e) {
			error_log('RDS AI Excerpt Error: ' . $e->getMessage());
			wp_send_json(array(
				'success' => false,
				'error'   => $e->getMessage()
			));
		}
	}

	/**
	 * Handle connection test request
	 */
	public function handle_test_connection()
	{
		// Start output buffering to catch any errors
		ob_start();

		try {
			// Verify nonce
			$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
			if (!wp_verify_nonce($nonce, 'rds_ai_excerpt_admin_nonce')) {
				throw new Exception(__('Security check failed.', 'rds-ai-excerpt'));
			}

			// Check capabilities
			if (!current_user_can('manage_options')) {
				throw new Exception(__('Insufficient permissions.', 'rds-ai-excerpt'));
			}

			// Get model ID
			$model_id = isset($_POST['model_id']) ? absint($_POST['model_id']) : 0;
			if (!$model_id) {
				throw new Exception(__('No model selected.', 'rds-ai-excerpt'));
			}

			// Log test request
			error_log('RDS AI Excerpt: Testing connection for model ID: ' . $model_id);

			// Test connection
			$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
			$result = $engine_integration->test_connection($model_id);

			if (is_wp_error($result)) {
				error_log('RDS AI Excerpt: Connection test failed: ' . $result->get_error_message());
				throw new Exception($result->get_error_message());
			}

			error_log('RDS AI Excerpt: Connection test successful for model ID: ' . $model_id);

			wp_send_json(array(
				'success' => true,
				'message' => __('AI connection successful!', 'rds-ai-excerpt')
			));
		} catch (Exception $e) {
			// Clear any output
			ob_end_clean();

			error_log('RDS AI Excerpt: Connection test exception: ' . $e->getMessage());

			wp_send_json(array(
				'success' => false,
				'error'   => $e->getMessage()
			));
		}
	}

	/**
	 * Prepare post content for AI processing
	 */
	private function prepare_content($post)
	{
		$content = $post->post_content;

		// Remove shortcodes
		$content = strip_shortcodes($content);

		// Remove HTML tags but keep line breaks
		$content = wp_strip_all_tags($content, true);

		// Limit content length
		$max_length = rds_ai_excerpt_get_option('max_content_length', 4000);

		// Ensure max_length is valid
		$max_length = absint($max_length);
		if ($max_length < 100) {
			$max_length = 4000;
		}

		if (strlen($content) > $max_length) {
			// Try to cut at sentence boundary
			$truncated = substr($content, 0, $max_length);
			$last_sentence = preg_match('/[.!?][^.!?]*$/', $truncated, $matches, PREG_OFFSET_CAPTURE);

			if ($last_sentence && $matches[0][1] > $max_length * 0.8) {
				$content = substr($truncated, 0, $matches[0][1] + 1) . '...';
			} else {
				// Cut at word boundary
				$last_space = strrpos($truncated, ' ');
				if ($last_space !== false && $last_space > $max_length * 0.8) {
					$content = substr($truncated, 0, $last_space) . '...';
				} else {
					$content = $truncated . '...';
				}
			}
		}

		return $content;
	}
}
