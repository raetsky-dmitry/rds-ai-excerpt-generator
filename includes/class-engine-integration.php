<?php

/**
 * Integration with RDS AI Engine plugin
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Engine_Integration
{

	private static $instance = null;
	private $ai_engine = null;
	private $model_manager = null;

	/**
	 * Get singleton instance
	 */
	public static function get_instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize integration
	 */
	private function init()
	{
		// Check if RDS AI Engine is available
		if ($this->is_ai_engine_available()) {
			$this->ai_engine = RDS_AIE_Main::get_instance();

			// Get model manager if available
			if (method_exists($this->ai_engine, 'get_model_manager')) {
				$this->model_manager = $this->ai_engine->get_model_manager();
			}
		}
	}

	/**
	 * Check if RDS AI Engine plugin is active and available
	 */
	public function is_ai_engine_available()
	{
		// Check if class exists
		if (!class_exists('RDS_AIE_Main')) {
			return false;
		}

		// Check if main class has get_instance method
		if (!method_exists('RDS_AIE_Main', 'get_instance')) {
			return false;
		}

		return true;
	}

	/**
	 * Check if model manager is available
	 */
	public function is_model_manager_available()
	{
		return $this->model_manager !== null;
	}

	/**
	 * Get available models from AI Engine
	 */
	public function get_available_models()
	{
		if (!$this->is_model_manager_available()) {
			return array();
		}

		try {
			// Use get_all() method as shown in example
			if (method_exists($this->model_manager, 'get_all')) {
				$models = $this->model_manager->get_all();

				// Convert to array format if needed
				if (is_array($models)) {
					return $models;
				}

				// If it's an object collection, convert to array
				if (is_object($models)) {
					$models_array = array();
					foreach ($models as $model) {
						$models_array[] = $this->model_to_array($model);
					}
					return $models_array;
				}
			}
		} catch (Exception $e) {
			error_log('RDS AI Excerpt: Failed to get models: ' . $e->getMessage());
		}

		return array();
	}

	/**
	 * Convert model object to array
	 */
	private function model_to_array($model)
	{
		if (is_array($model)) {
			return $model;
		}

		if (is_object($model)) {
			return array(
				'id'   => isset($model->id) ? $model->id : 0,
				'name' => isset($model->name) ? $model->name : __('Unknown Model', 'rds-ai-excerpt'),
				'provider' => isset($model->provider) ? $model->provider : '',
				'model_name' => isset($model->model_name) ? $model->model_name : '',
			);
		}

		return array(
			'id'   => 0,
			'name' => __('Invalid Model', 'rds-ai-excerpt')
		);
	}

	/**
	 * Generate excerpt using AI Engine
	 */
	public function generate_excerpt($content, $title, $params)
	{
		if (!$this->ai_engine) {
			return new WP_Error(
				'ai_engine_unavailable',
				__('RDS AI Engine is not available. Please install and activate the RDS AI Engine plugin.', 'rds-ai-excerpt')
			);
		}

		// Get selected model ID
		$model_id = rds_ai_excerpt_get_option('selected_model_id');
		if (empty($model_id)) {
			return new WP_Error(
				'no_model_selected',
				__('No AI model selected. Please select a model in plugin settings.', 'rds-ai-excerpt')
			);
		}

		// Build prompt
		$system_prompt = rds_ai_excerpt_get_option('system_prompt', $this->get_default_prompt());
		$prompt = $this->build_prompt($content, $title, $params, $system_prompt);

		// Check if {{content}} variable was used in the original system prompt
		if (strpos($system_prompt, '{{content}}') === false) {
			return new WP_Error(
				'missing_content_variable',
				__('The {{content}} variable is missing from your system prompt. Please include it to send the post content to AI.', 'rds-ai-excerpt')
			);
		}

		// Log request if enabled
		if (rds_ai_excerpt_get_option('enable_logging')) {
			rds_ai_excerpt_log(
				sprintf(
					'Generating excerpt with model ID: %d, prompt length: %d',
					$model_id,
					strlen($prompt)
				),
				'info'
			);
		}

		try {
			// Prepare request parameters for AI Engine
			$request_params = array(
				'model_id' => $model_id,
				'message' => $prompt,
				// AI Engine likely expects these parameters
				'temperature' => 0.7,
				'max_tokens' => $params['max_length'] ? $params['max_length'] * 4 : 600,
			);

			// Add session tracking
			$post_id = isset($params['post_id']) ? $params['post_id'] : 0;
			if ($post_id) {
				$request_params['session_id'] = 'excerpt_post_' . $post_id;
				$request_params['plugin_id'] = 'rds_ai_excerpt_generator';
			}

			// Call AI Engine's chat_completion method
			// Check if method exists and call it
			if (method_exists($this->ai_engine, 'chat_completion')) {
				$response = $this->ai_engine->chat_completion($request_params);
			} else {
				// Try alternative method names
				if (method_exists($this->ai_engine, 'generate_chat_completion')) {
					$response = $this->ai_engine->generate_chat_completion($request_params);
				} else if (method_exists($this->ai_engine, 'send_message')) {
					$response = $this->ai_engine->send_message($request_params);
				} else {
					return new WP_Error(
						'method_not_found',
						__('AI Engine chat method not found.', 'rds-ai-excerpt')
					);
				}
			}

			// Clean up response
			$excerpt = $this->clean_excerpt($response);

			// Log success
			if (rds_ai_excerpt_get_option('enable_logging')) {
				rds_ai_excerpt_log(
					sprintf(
						'Excerpt generated successfully. Length: %d chars',
						strlen($excerpt)
					),
					'info'
				);
			}

			return $excerpt;
		} catch (Exception $e) {
			error_log('RDS AI Excerpt: AI Engine error: ' . $e->getMessage());

			// Log error
			if (rds_ai_excerpt_get_option('enable_logging')) {
				rds_ai_excerpt_log(
					'AI Engine Exception: ' . $e->getMessage(),
					'error'
				);
			}

			return new WP_Error(
				'ai_engine_error',
				sprintf(__('AI Engine Error: %s', 'rds-ai-excerpt'), $e->getMessage())
			);
		}
	}

	/**
	 * Test connection to AI Engine
	 */
	public function test_connection($model_id = null)
	{
		if (!$this->ai_engine) {
			return new WP_Error(
				'ai_engine_unavailable',
				__('RDS AI Engine is not available.', 'rds-ai-excerpt')
			);
		}

		// Use provided model ID or get from settings
		if (!$model_id) {
			$model_id = rds_ai_excerpt_get_option('selected_model_id');
		}

		if (empty($model_id)) {
			return new WP_Error(
				'no_model',
				__('No model selected for testing.', 'rds-ai-excerpt')
			);
		}

		try {
			// Simple test request
			$test_params = array(
				'model_id' => $model_id,
				'message' => 'Say "test"',
				'max_tokens' => 5,
				'temperature' => 0.1, // Low temperature for predictable response
			);

			// Determine which method to call
			if (method_exists($this->ai_engine, 'chat_completion')) {
				$response = $this->ai_engine->chat_completion($test_params);
			} else if (method_exists($this->ai_engine, 'generate_chat_completion')) {
				$response = $this->ai_engine->generate_chat_completion($test_params);
			} else if (method_exists($this->ai_engine, 'send_message')) {
				$response = $this->ai_engine->send_message($test_params);
			} else {
				return new WP_Error(
					'method_not_found',
					__('AI Engine chat method not found.', 'rds-ai-excerpt')
				);
			}

			// Check if we got a response
			if (!empty($response)) {
				// Even if doesn't contain "test", if we got a response, connection works
				return true;
			}

			return new WP_Error(
				'empty_response',
				__('Received empty response from AI Engine.', 'rds-ai-excerpt')
			);
		} catch (Exception $e) {
			error_log('RDS AI Excerpt: Connection test error: ' . $e->getMessage());
			return new WP_Error(
				'test_failed',
				sprintf(__('Connection test failed: %s', 'rds-ai-excerpt'), $e->getMessage())
			);
		}
	}

	/**
	 * Build prompt from template
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

		// Check if {{content}} variable was used in the prompt
		$content_used = strpos($system_prompt, '{{content}}') !== false;

		// If {{content}} wasn't used in the prompt, log a warning (but don't add content automatically)
		if (!$content_used && rds_ai_excerpt_get_option('enable_logging')) {
			rds_ai_excerpt_log(
				'Warning: {{content}} variable not found in system prompt. Post content will not be sent to AI.',
				'warning'
			);
		}

		// Clear instructions - add only if not already present
		if (
			strpos($prompt, 'Generate only the excerpt') === false &&
			strpos($prompt, 'Output only the excerpt') === false
		) {
			$prompt .= "\n\nGenerate only the excerpt text, without any explanations, introductions, or formatting.";
		}

		return $prompt;
	}

	/**
	 * Get default prompt
	 */
	private function get_default_prompt()
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
	 * Clean up excerpt
	 */
	private function clean_excerpt($excerpt)
	{
		if (!is_string($excerpt)) {
			if (is_object($excerpt) && isset($excerpt->content)) {
				$excerpt = $excerpt->content;
			} else if (is_array($excerpt) && isset($excerpt['content'])) {
				$excerpt = $excerpt['content'];
			} else {
				return is_string($excerpt) ? $excerpt : '';
			}
		}

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
		$excerpt = preg_replace('/\s+/', ' ', $excerpt);

		return $excerpt;
	}

	/**
	 * Get model name by ID
	 */
	public function get_model_name($model_id)
	{
		$models = $this->get_available_models();

		foreach ($models as $model) {
			// Handle both array and object formats
			$current_id = is_array($model) ? (isset($model['id']) ? $model['id'] : null) : (is_object($model) ? (isset($model->id) ? $model->id : null) : null);

			if ($current_id && $current_id == $model_id) {
				if (is_array($model)) {
					return isset($model['name']) ? $model['name'] : __('Unknown Model', 'rds-ai-excerpt');
				} else if (is_object($model)) {
					return isset($model->name) ? $model->name : __('Unknown Model', 'rds-ai-excerpt');
				}
			}
		}

		return __('Model not found', 'rds-ai-excerpt');
	}
}
