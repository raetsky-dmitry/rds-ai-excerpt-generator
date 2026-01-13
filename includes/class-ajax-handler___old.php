<?php

/**
 * Handles AJAX requests for AI excerpt generation
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
		add_action('wp_ajax_rds_ai_test_api_connection', array($this, 'handle_test_api_connection'));
	}

	/**
	 * Handle excerpt generation request
	 */
	public function handle_generate_excerpt()
	{
		// Verify nonce
		if (!check_ajax_referer('rds_ai_excerpt_nonce', 'nonce', false)) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Security check failed.', 'rds-ai-excerpt')
			));
		}

		// Check capabilities
		if (!current_user_can('edit_posts')) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Insufficient permissions.', 'rds-ai-excerpt')
			));
		}

		// Get post ID
		$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
		if (!$post_id) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Invalid post ID.', 'rds-ai-excerpt')
			));
		}

		// Check if user can edit this post
		if (!current_user_can('edit_post', $post_id)) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('You cannot edit this post.', 'rds-ai-excerpt')
			));
		}

		// Get parameters
		$params = array(
			'style'          => isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '',
			'tone'           => isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : '',
			'language'       => isset($_POST['language']) ? sanitize_text_field($_POST['language']) : '',
			'max_length'     => isset($_POST['max_length']) ? absint($_POST['max_length']) : 0,
			'focus_keywords' => isset($_POST['focus_keywords']) ? sanitize_text_field($_POST['focus_keywords']) : '',
		);

		// Get post content
		$post = get_post($post_id);
		if (!$post) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Post not found.', 'rds-ai-excerpt')
			));
		}

		// Prepare content
		$content = $this->prepare_content($post);

		// Get post title
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

		// Generate excerpt using AI
		$result = $this->generate_ai_excerpt($content, $post_title, $params);

		if (is_wp_error($result)) {
			$response = array(
				'success' => false,
				'error'   => $result->get_error_message()
			);
		} else {
			$response = array(
				'success' => true,
				'excerpt' => $result
			);
		}

		// Send response
		wp_send_json($response);
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
		if (strlen($content) > $max_length) {
			$content = substr($content, 0, $max_length) . '...';
		}

		return $content;
	}

	/**
	 * Generate excerpt using AI API
	 */
	private function generate_ai_excerpt($content, $title, $params)
	{
		// Get API settings
		$api_settings = array(
			'base_url' => rds_ai_excerpt_get_option('api_base_url'),
			'model'    => rds_ai_excerpt_get_option('api_model'),
			'api_key'  => rds_ai_excerpt_get_option('api_key'),
		);

		// Check API settings
		if (empty($api_settings['api_key'])) {
			return new WP_Error('no_api_key', __('API key is not configured.', 'rds-ai-excerpt'));
		}

		if (empty($api_settings['base_url'])) {
			return new WP_Error('no_base_url', __('API base URL is not configured.', 'rds-ai-excerpt'));
		}

		// Build prompt
		$system_prompt = rds_ai_excerpt_get_option('system_prompt');
		$prompt = $this->build_prompt($content, $title, $params, $system_prompt);

		// Prepare request data for OpenRouter API
		$request_data = array(
			'model' => $api_settings['model'],
			'messages' => array(
				array(
					'role' => 'system',
					'content' => 'You are a helpful assistant that generates concise and engaging post excerpts.'
				),
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			'max_tokens' => $params['max_length'] ? min($params['max_length'] * 4, 1000) : 600,
			'temperature' => 0.7,
		);

		// For OpenRouter, we might need to specify provider
		if (strpos($api_settings['base_url'], 'openrouter.ai') !== false) {
			// OpenRouter-specific adjustments if needed
			$request_data['max_tokens'] = min($params['max_length'] * 4, 2000);
		}

		// Make API request
		$response = wp_remote_post(
			$api_settings['base_url'] . '/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_settings['api_key'],
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => get_site_url(), // Required for OpenRouter
					'X-Title'       => get_bloginfo('name'), // Optional for OpenRouter
				),
				'body'    => json_encode($request_data),
				'timeout' => rds_ai_excerpt_get_option('request_timeout', 30),
			)
		);

		// Log API call
		if (rds_ai_excerpt_get_option('enable_logging')) {
			rds_ai_excerpt_log(
				'API Request: ' . json_encode(array(
					'url' => $api_settings['base_url'],
					'model' => $api_settings['model'],
					'prompt_length' => strlen($prompt)
				)),
				'info'
			);
		}

		// Check for errors
		if (is_wp_error($response)) {
			if (rds_ai_excerpt_get_option('enable_logging')) {
				rds_ai_excerpt_log('API Error: ' . $response->get_error_message(), 'error');
			}
			return new WP_Error('api_error', sprintf(__('API Error: %s', 'rds-ai-excerpt'), $response->get_error_message()));
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body, true);

		if (rds_ai_excerpt_get_option('enable_logging')) {
			rds_ai_excerpt_log('API Response Code: ' . $response_code, 'info');
			rds_ai_excerpt_log('API Response Body: ' . substr($response_body, 0, 500), 'info');
		}

		// Check HTTP status
		if ($response_code !== 200) {
			$error_message = __('API returned an error.', 'rds-ai-excerpt');
			if (isset($response_data['error']['message'])) {
				$error_message = $response_data['error']['message'];
			} elseif (isset($response_data['error'])) {
				$error_message = is_string($response_data['error']) ? $response_data['error'] : json_encode($response_data['error']);
			}
			return new WP_Error('api_error', sprintf(__('API Error (%d): %s', 'rds-ai-excerpt'), $response_code, $error_message));
		}

		// Extract excerpt from response
		if (isset($response_data['choices'][0]['message']['content'])) {
			$excerpt = trim($response_data['choices'][0]['message']['content']);

			// Remove any markdown formatting or quotes
			$excerpt = preg_replace('/^["\']|["\']$/', '', $excerpt); // Remove surrounding quotes
			$excerpt = strip_tags($excerpt); // Remove any HTML tags

			// Trim and clean up
			$excerpt = trim($excerpt);

			return $excerpt;
		}

		return new WP_Error('no_excerpt', __('Could not extract excerpt from API response.', 'rds-ai-excerpt'));
	}

	/**
	 * Build prompt from template and parameters
	 */
	private function build_prompt($content, $title, $params, $system_prompt)
	{
		// Prepare variables
		$variables = array(
			'{{content}}'       => $content,
			'{{title}}'         => $title,
			'{{style}}'         => $params['style'] ?: rds_ai_excerpt_get_option('default_style'),
			'{{tone}}'          => $params['tone'] ?: rds_ai_excerpt_get_option('default_tone'),
			'{{language}}'      => $params['language'] ?: rds_ai_excerpt_get_option('default_language'),
			'{{max_length}}'    => $params['max_length'] ?: rds_ai_excerpt_get_option('default_max_length'),
			'{{focus_keywords}}' => $params['focus_keywords'] ?: rds_ai_excerpt_get_option('default_focus_keywords'),
		);

		// Replace variables in prompt
		$prompt = $system_prompt;
		foreach ($variables as $key => $value) {
			if ($value) {
				$prompt = str_replace($key, $value, $prompt);
			}
		}

		// Add content at the end
		$prompt .= "\n\nContent to summarize:\n" . $content;

		// Add instructions for format
		$prompt .= "\n\nPlease provide only the excerpt text, without any explanations, introductions, or markdown formatting.";

		return $prompt;
	}

	/**
	 * Handle API connection test
	 */
	public function handle_test_api_connection()
	{
		// Verify nonce
		if (!check_ajax_referer('rds_ai_excerpt_admin_nonce', 'nonce', false)) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Security check failed.', 'rds-ai-excerpt')
			));
		}

		// Check capabilities
		if (!current_user_can('manage_options')) {
			wp_send_json(array(
				'success' => false,
				'error'   => __('Insufficient permissions.', 'rds-ai-excerpt')
			));
		}

		// Test API connection
		$result = $this->test_api_connection();

		if (is_wp_error($result)) {
			wp_send_json(array(
				'success' => false,
				'error'   => $result->get_error_message()
			));
		} else {
			wp_send_json(array(
				'success' => true,
				'message' => __('API connection successful!', 'rds-ai-excerpt')
			));
		}
	}

	/**
	 * Test API connection
	 */
	private function test_api_connection()
	{
		$api_settings = array(
			'base_url' => rds_ai_excerpt_get_option('api_base_url'),
			'model'    => rds_ai_excerpt_get_option('api_model'),
			'api_key'  => rds_ai_excerpt_get_option('api_key'),
		);

		if (empty($api_settings['api_key'])) {
			return new WP_Error('no_api_key', __('API key is not configured.', 'rds-ai-excerpt'));
		}

		// Test with a simple models endpoint
		$test_url = rtrim($api_settings['base_url'], '/') . '/models';

		$response = wp_remote_get($test_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_settings['api_key'],
				'Content-Type'  => 'application/json',
				'HTTP-Referer'  => get_site_url(),
			),
			'timeout' => 10,
		));

		if (is_wp_error($response)) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code === 200) {
			return true;
		}

		// Try alternative test for OpenRouter
		if ($response_code === 404 || $response_code === 405) {
			// Try a simple chat completion test
			$test_url = rtrim($api_settings['base_url'], '/') . '/chat/completions';
			$test_response = wp_remote_post($test_url, array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_settings['api_key'],
					'Content-Type'  => 'application/json',
					'HTTP-Referer'  => get_site_url(),
				),
				'body' => json_encode(array(
					'model' => $api_settings['model'],
					'messages' => array(
						array(
							'role' => 'user',
							'content' => 'Say "test"'
						)
					),
					'max_tokens' => 5
				)),
				'timeout' => 10,
			));

			if (!is_wp_error($test_response)) {
				$test_code = wp_remote_retrieve_response_code($test_response);
				if ($test_code === 200) {
					return true;
				}
			}
		}

		return new WP_Error(
			'api_test_failed',
			sprintf(__('API test failed with status code: %d', 'rds-ai-excerpt'), $response_code)
		);
	}
}
