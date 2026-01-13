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

			// Get post content
			$post = get_post($post_id);
			if (!$post) {
				throw new Exception(__('Post not found.', 'rds-ai-excerpt'));
			}

			// Prepare content
			$content = $this->prepare_content($post);
			$post_title = $post->post_title;

			// Generate excerpt using AI
			$result = $this->generate_ai_excerpt($content, $post_title, $params);

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
	 * Prepare post content for AI processing
	 */
	private function prepare_content($post)
	{
		$content = $post->post_content;

		// Remove shortcodes
		$content = strip_shortcodes($content);

		// Remove HTML tags but keep line breaks
		$content = wp_strip_all_tags($content, true);

		// Получаем максимальную длину с дополнительными проверками
		$options = get_option('rds_ai_excerpt_settings', array());
		$max_length = 4000; // Значение по умолчанию

		if (is_array($options) && isset($options['max_content_length'])) {
			$max_length_value = $options['max_content_length'];

			// Проверяем и преобразуем значение
			if (is_numeric($max_length_value)) {
				$max_length = (int) $max_length_value;
			} elseif (is_string($max_length_value) && is_numeric(trim($max_length_value))) {
				$max_length = (int) trim($max_length_value);
			}
		}

		// Убедимся, что значение разумное
		if ($max_length < 100 || $max_length > 16000) {
			$max_length = 4000;
		}

		// Безопасная обрезка
		$content_length = strlen($content);
		if ($content_length > $max_length) {
			// Ищем границу слова
			$truncated = substr($content, 0, $max_length);
			$last_space = strrpos($truncated, ' ');

			if ($last_space !== false) {
				$content = substr($truncated, 0, $last_space) . '...';
			} else {
				$content = $truncated . '...';
			}
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
		$system_prompt = rds_ai_excerpt_get_option('system_prompt', $this->get_default_prompt());
		$prompt = $this->build_prompt($content, $title, $params, $system_prompt);

		error_log('=== RDS AI Excerpt: API Request Details ===');
		error_log('Model: ' . $api_settings['model']);
		error_log('Prompt length: ' . strlen($prompt));
		error_log('Max tokens requested: ' . ($params['max_length'] ? min($params['max_length'] * 4, 1000) : 600));

		// For OpenRouter API
		$request_data = array(
			'model' => $api_settings['model'],
			'messages' => array(
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			// 'max_tokens' => $params['max_length'] ? min($params['max_length'] * 4, 1000) : 600,
			'temperature' => 0.7,
		);

		$api_url = rtrim($api_settings['base_url'], '/') . '/chat/completions';

		// Подготовка headers
		$headers = array(
			'Authorization' => 'Bearer ' . $api_settings['api_key'],
			'Content-Type'  => 'application/json',
		);

		// Add OpenRouter specific headers
		if (strpos($api_settings['base_url'], 'openrouter.ai') !== false) {
			$headers['HTTP-Referer'] = get_site_url();
			$headers['X-Title'] = get_bloginfo('name');
		}

		// Log the request
		error_log('API URL: ' . $api_url);
		error_log('Request headers: ' . print_r($headers, true));
		error_log('Request data (first 500 chars of prompt): ' . substr($prompt, 0, 500));

		// Make API request
		$response = wp_remote_post($api_url, array(
			'headers' => $headers,
			'body'    => json_encode($request_data),
			'timeout' => 30,
		));

		// Check for errors
		if (is_wp_error($response)) {
			error_log('RDS AI Excerpt: WP_Error: ' . $response->get_error_message());
			return new WP_Error('api_error', sprintf(__('API Error: %s', 'rds-ai-excerpt'), $response->get_error_message()));
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		error_log('=== RDS AI Excerpt: API Response ===');
		error_log('Response code: ' . $response_code);
		error_log('Full response body:');
		error_log($response_body);

		// Check HTTP status
		if ($response_code !== 200) {
			$error_message = __('API returned an error.', 'rds-ai-excerpt');
			$response_data = json_decode($response_body, true);

			if (isset($response_data['error']['message'])) {
				$error_message = $response_data['error']['message'];
			} elseif (isset($response_data['error'])) {
				$error_message = is_string($response_data['error']) ? $response_data['error'] : json_encode($response_data['error']);
			} elseif (!empty($response_body)) {
				$error_message = __('API Error: ', 'rds-ai-excerpt') . substr($response_body, 0, 500);
			}

			error_log('RDS AI Excerpt: API error: ' . $error_message);
			return new WP_Error('api_error', sprintf(__('API Error (%d): %s', 'rds-ai-excerpt'), $response_code, $error_message));
		}

		// Parse response
		$response_data = json_decode($response_body, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			error_log('RDS AI Excerpt: JSON parse error: ' . json_last_error_msg());
			error_log('RDS AI Excerpt: Raw response: ' . $response_body);
			return new WP_Error('json_error', __('Failed to parse API response.', 'rds-ai-excerpt'));
		}

		// Log full response structure
		error_log('Response data structure:');
		error_log(print_r($response_data, true));

		// Extract excerpt from response
		if (isset($response_data['choices'][0]['message']['content'])) {
			$excerpt = trim($response_data['choices'][0]['message']['content']);

			error_log('=== RDS AI Excerpt: Raw AI Response ===');
			error_log('Raw excerpt length: ' . strlen($excerpt));
			error_log('Raw excerpt: ' . $excerpt);

			// Clean up the excerpt
			$excerpt = $this->clean_excerpt($excerpt);

			error_log('=== RDS AI Excerpt: Cleaned Excerpt ===');
			error_log('Cleaned excerpt length: ' . strlen($excerpt));
			error_log('Cleaned excerpt: ' . $excerpt);

			return $excerpt;
		}

		error_log('RDS AI Excerpt: No excerpt in response. Available keys: ' . implode(', ', array_keys($response_data)));
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

		// Add content
		$prompt .= "\n\nContent to summarize:\n" . $content;

		// Add clear instructions
		$prompt .= "\n\nGenerate only the excerpt text, without any explanations, introductions, or formatting.";

		return $prompt;
	}

	/**
	 * Get default prompt
	 */
	private function get_default_prompt()
	{
		return 'Generate a concise and engaging excerpt for a blog post.

Post Title: {{title}}
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
	 * Clean up excerpt
	 */
	private function clean_excerpt($excerpt)
	{
		// Remove surrounding quotes
		$excerpt = preg_replace('/^["\']|["\']$/', '', $excerpt);

		// Remove common prefixes
		$excerpt = preg_replace('/^(Excerpt|Отрывок|Резюме|Summary|Abstract)[:\s\-]*/i', '', $excerpt);

		// Remove markdown if present
		$excerpt = preg_replace('/\*\*(.*?)\*\*/', '$1', $excerpt);
		$excerpt = preg_replace('/\*(.*?)\*/', '$1', $excerpt);
		$excerpt = preg_replace('/_(.*?)_/', '$1', $excerpt);

		// Trim and clean
		$excerpt = trim($excerpt);
		$excerpt = preg_replace('/\s+/', ' ', $excerpt); // Multiple spaces to single

		return $excerpt;
	}

	/**
	 * Handle API connection test
	 */
	public function handle_test_api_connection()
	{
		try {
			// Verify nonce
			if (!check_ajax_referer('rds_ai_excerpt_admin_nonce', 'nonce', false)) {
				throw new Exception(__('Security check failed.', 'rds-ai-excerpt'));
			}

			// Check capabilities
			if (!current_user_can('manage_options')) {
				throw new Exception(__('Insufficient permissions.', 'rds-ai-excerpt'));
			}

			// Test API connection
			$result = $this->test_api_connection();

			if (is_wp_error($result)) {
				throw new Exception($result->get_error_message());
			}

			wp_send_json(array(
				'success' => true,
				'message' => __('API connection successful!', 'rds-ai-excerpt')
			));
		} catch (Exception $e) {
			wp_send_json(array(
				'success' => false,
				'error'   => $e->getMessage()
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

		// Simple test request
		$api_url = rtrim($api_settings['base_url'], '/') . '/chat/completions';

		$headers = array(
			'Authorization' => 'Bearer ' . $api_settings['api_key'],
			'Content-Type'  => 'application/json',
		);

		if (strpos($api_settings['base_url'], 'openrouter.ai') !== false) {
			$headers['HTTP-Referer'] = get_site_url();
		}

		$response = wp_remote_post($api_url, array(
			'headers' => $headers,
			'body' => json_encode(array(
				'model' => $api_settings['model'],
				'messages' => array(
					array(
						'role' => 'user',
						'content' => 'Say just the word "test"'
					)
				),
				'max_tokens' => 5
			)),
			'timeout' => 10,
		));

		if (is_wp_error($response)) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code($response);

		if ($response_code === 200) {
			return true;
		}

		return new WP_Error(
			'api_test_failed',
			sprintf(__('API test failed with status code: %d', 'rds-ai-excerpt'), $response_code)
		);
	}
}
