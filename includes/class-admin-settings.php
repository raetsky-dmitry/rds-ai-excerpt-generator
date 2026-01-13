<?php

/**
 * Handles admin settings page
 */

if (!defined('ABSPATH')) {
	exit;
}

class RDS_AI_Excerpt_Admin_Settings
{

	/**
	 * Initialize
	 */
	public function init()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		add_options_page(
			__('RDS AI Excerpt Generator', 'rds-ai-excerpt'),
			__('AI Excerpt', 'rds-ai-excerpt'),
			'manage_options',
			'rds-ai-excerpt-settings',
			array($this, 'render_settings_page')
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings()
	{
		register_setting(
			'rds_ai_excerpt_settings_group',
			'rds_ai_excerpt_settings',
			array($this, 'sanitize_settings')
		);

		// API Settings Section
		add_settings_section(
			'rds_ai_excerpt_api_section',
			__('API Settings', 'rds-ai-excerpt'),
			array($this, 'render_api_section'),
			'rds-ai-excerpt-settings'
		);

		// Generation Defaults Section
		add_settings_section(
			'rds_ai_excerpt_generation_section',
			__('Generation Defaults', 'rds-ai-excerpt'),
			array($this, 'render_generation_section'),
			'rds-ai-excerpt-settings'
		);

		// Post Types Section
		add_settings_section(
			'rds_ai_excerpt_post_types_section',
			__('Post Type Settings', 'rds-ai-excerpt'),
			array($this, 'render_post_types_section'),
			'rds-ai-excerpt-settings'
		);

		// Security & Limits Section
		add_settings_section(
			'rds_ai_excerpt_security_section',
			__('Security & Limits', 'rds-ai-excerpt'),
			array($this, 'render_security_section'),
			'rds-ai-excerpt-settings'
		);

		// API Settings Fields
		add_settings_field(
			'api_base_url',
			__('API Base URL', 'rds-ai-excerpt'),
			array($this, 'render_api_base_url_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_api_section'
		);

		add_settings_field(
			'api_model',
			__('API Model', 'rds-ai-excerpt'),
			array($this, 'render_api_model_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_api_section'
		);

		add_settings_field(
			'api_key',
			__('API Key', 'rds-ai-excerpt'),
			array($this, 'render_api_key_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_api_section'
		);

		// Generation Defaults Fields
		add_settings_field(
			'default_style',
			__('Default Style', 'rds-ai-excerpt'),
			array($this, 'render_default_style_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		add_settings_field(
			'default_tone',
			__('Default Tone', 'rds-ai-excerpt'),
			array($this, 'render_default_tone_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		add_settings_field(
			'default_language',
			__('Default Language', 'rds-ai-excerpt'),
			array($this, 'render_default_language_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		add_settings_field(
			'default_max_length',
			__('Default Max Length (words)', 'rds-ai-excerpt'),
			array($this, 'render_default_max_length_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		add_settings_field(
			'default_focus_keywords',
			__('Default Focus Keywords', 'rds-ai-excerpt'),
			array($this, 'render_default_focus_keywords_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		add_settings_field(
			'system_prompt',
			__('System Prompt', 'rds-ai-excerpt'),
			array($this, 'render_system_prompt_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_generation_section'
		);

		// Post Types Fields
		add_settings_field(
			'enabled_post_types',
			__('Enabled Post Types', 'rds-ai-excerpt'),
			array($this, 'render_enabled_post_types_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_post_types_section'
		);

		// Security & Limits Fields
		add_settings_field(
			'max_content_length',
			__('Max Content Length (characters)', 'rds-ai-excerpt'),
			array($this, 'render_max_content_length_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_security_section'
		);

		add_settings_field(
			'request_timeout',
			__('Request Timeout (seconds)', 'rds-ai-excerpt'),
			array($this, 'render_request_timeout_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_security_section'
		);

		add_settings_field(
			'allowed_user_roles',
			__('Allowed User Roles', 'rds-ai-excerpt'),
			array($this, 'render_allowed_user_roles_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_security_section'
		);

		add_settings_field(
			'enable_logging',
			__('Enable Debug Logging', 'rds-ai-excerpt'),
			array($this, 'render_enable_logging_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_security_section'
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings($input)
	{
		$sanitized = array();

		// API Settings
		$sanitized['api_base_url'] = esc_url_raw($input['api_base_url']);
		$sanitized['api_model'] = sanitize_text_field($input['api_model']);
		$sanitized['api_key'] = sanitize_text_field($input['api_key']);

		// Generation Defaults
		$sanitized['default_style'] = sanitize_text_field($input['default_style']);
		$sanitized['default_tone'] = sanitize_text_field($input['default_tone']);
		$sanitized['default_language'] = sanitize_text_field($input['default_language']);
		$sanitized['default_max_length'] = absint($input['default_max_length']);
		$sanitized['default_focus_keywords'] = sanitize_text_field($input['default_focus_keywords']);

		// System Prompt
		$sanitized['system_prompt'] = wp_kses_post($input['system_prompt']);

		// Post Types
		$sanitized['enabled_post_types'] = array();
		if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
			foreach ($input['enabled_post_types'] as $post_type) {
				$sanitized['enabled_post_types'][] = sanitize_key($post_type);
			}
		}

		// Security & Limits
		$sanitized['max_content_length'] = absint($input['max_content_length']);
		$sanitized['request_timeout'] = absint($input['request_timeout']);
		$sanitized['allowed_user_roles'] = array();
		if (isset($input['allowed_user_roles']) && is_array($input['allowed_user_roles'])) {
			foreach ($input['allowed_user_roles'] as $role) {
				$sanitized['allowed_user_roles'][] = sanitize_key($role);
			}
		}

		// Debug
		$sanitized['enable_logging'] = isset($input['enable_logging']) ? (bool) $input['enable_logging'] : false;

		return $sanitized;
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts($hook)
	{
		if ($hook !== 'settings_page_rds-ai-excerpt-settings') {
			return;
		}

		wp_enqueue_style(
			'rds-ai-excerpt-admin',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			RDS_AI_EXCERPT_VERSION
		);

		wp_enqueue_script(
			'rds-ai-excerpt-admin',
			RDS_AI_EXCERPT_PLUGIN_URL . 'assets/js/admin-settings.js',
			array('jquery'),
			RDS_AI_EXCERPT_VERSION,
			true
		);
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page()
	{
		if (!current_user_can('manage_options')) {
			return;
		}

?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

			<?php if (isset($_GET['settings-updated'])): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e('Settings saved successfully!', 'rds-ai-excerpt'); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php
				settings_fields('rds_ai_excerpt_settings_group');
				do_settings_sections('rds-ai-excerpt-settings');
				submit_button();
				?>
			</form>

			<div class="rds-ai-excerpt-api-test-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
				<h2><?php _e('API Connection Test', 'rds-ai-excerpt'); ?></h2>
				<p><?php _e('Test your API connection with the current settings:', 'rds-ai-excerpt'); ?></p>
				<button type="button" id="rds-ai-test-api" class="button button-secondary">
					<?php _e('Test API Connection', 'rds-ai-excerpt'); ?>
				</button>
				<div class="api-test-result" style="display: none; margin-top: 10px; padding: 10px;"></div>
			</div>

			<div class="rds-ai-excerpt-debug-section" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
				<h2><?php _e('Debug Information', 'rds-ai-excerpt'); ?></h2>
				<p><?php _e('Plugin version:', 'rds-ai-excerpt'); ?> <?php echo esc_html(RDS_AI_EXCERPT_VERSION); ?></p>
				<p><?php _e('Log directory:', 'rds-ai-excerpt'); ?>
					<?php
					$log_dir = WP_CONTENT_DIR . '/rds-ai-excerpt-logs';
					echo esc_html($log_dir);
					if (!file_exists($log_dir)) {
						echo ' <span class="dashicons dashicons-warning"></span> ' . __('Not found', 'rds-ai-excerpt');
					}
					?>
				</p>
			</div>
		</div>

		<script type="text/javascript">
			var rdsAISettings = {
				ajaxurl: '<?php echo admin_url("admin-ajax.php"); ?>',
				nonce: '<?php echo wp_create_nonce("rds_ai_excerpt_admin_nonce"); ?>',
				strings: {
					testing: '<?php _e('Testing...', 'rds-ai-excerpt'); ?>',
					testSuccess: '<?php _e('API connection successful!', 'rds-ai-excerpt'); ?>',
					testFailed: '<?php _e('API connection failed.', 'rds-ai-excerpt'); ?>',
					testError: '<?php _e('Error', 'rds-ai-excerpt'); ?>',
					noApiKey: '<?php _e('Please enter an API key first.', 'rds-ai-excerpt'); ?>',
					noBaseUrl: '<?php _e('Please enter a base URL first.', 'rds-ai-excerpt'); ?>',
					showKey: '<?php _e('Show', 'rds-ai-excerpt'); ?>',
					hideKey: '<?php _e('Hide', 'rds-ai-excerpt'); ?>',
					characters: '<?php _e('characters', 'rds-ai-excerpt'); ?>'
				}
			};
		</script>
	<?php
	}

	/**
	 * Render API section
	 */
	public function render_api_section()
	{
		echo '<p>' . __('Configure your AI API connection settings.', 'rds-ai-excerpt') . '</p>';
	}

	/**
	 * Render generation section
	 */
	public function render_generation_section()
	{
		echo '<p>' . __('Set default values for excerpt generation.', 'rds-ai-excerpt') . '</p>';
	}

	/**
	 * Render post types section
	 */
	public function render_post_types_section()
	{
		echo '<p>' . __('Select which post types should have the AI Excerpt Generator available.', 'rds-ai-excerpt') . '</p>';
	}

	/**
	 * Render security section
	 */
	public function render_security_section()
	{
		echo '<p>' . __('Configure security settings and limits for API requests.', 'rds-ai-excerpt') . '</p>';
	}

	// API Settings Fields
	public function render_api_base_url_field()
	{
		$value = rds_ai_excerpt_get_option('api_base_url', 'https://api.openai.com/v1');
	?>
		<input type="url" id="rds_ai_excerpt_settings_api_base_url"
			name="rds_ai_excerpt_settings[api_base_url]"
			value="<?php echo esc_attr($value); ?>" class="regular-text">
		<p class="description">
			<?php _e('Base URL for your AI API (e.g., https://api.openai.com/v1)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_api_model_field()
	{
		$value = rds_ai_excerpt_get_option('api_model', 'gpt-3.5-turbo');
	?>
		<input type="text" name="rds_ai_excerpt_settings[api_model]"
			value="<?php echo esc_attr($value); ?>" class="regular-text">
		<p class="description">
			<?php _e('Model name (e.g., gpt-3.5-turbo, gpt-4)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_api_key_field()
	{
		$value = rds_ai_excerpt_get_option('api_key', '');
	?>
		<input type="password" id="rds_ai_excerpt_settings_api_key"
			name="rds_ai_excerpt_settings[api_key]"
			value="<?php echo esc_attr($value); ?>" class="regular-text">
		<button type="button" id="rds-ai-toggle-api-key" class="button button-small">
			<?php _e('Show', 'rds-ai-excerpt'); ?>
		</button>
		<p class="description">
			<?php _e('Your API key for authentication', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	// Generation Defaults Fields
	public function render_default_style_field()
	{
		$value = rds_ai_excerpt_get_option('default_style', 'descriptive');
		$styles = array(
			'descriptive' => __('Descriptive', 'rds-ai-excerpt'),
			'advertising' => __('Advertising', 'rds-ai-excerpt'),
			'business'    => __('Business', 'rds-ai-excerpt'),
			'creative'    => __('Creative', 'rds-ai-excerpt'),
		);
	?>
		<select name="rds_ai_excerpt_settings[default_style]">
			<?php foreach ($styles as $key => $label): ?>
				<option value="<?php echo esc_attr($key); ?>"
					<?php selected($value, $key); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	public function render_default_tone_field()
	{
		$value = rds_ai_excerpt_get_option('default_tone', 'neutral');
		$tones = array(
			'neutral'     => __('Neutral', 'rds-ai-excerpt'),
			'formal'      => __('Formal', 'rds-ai-excerpt'),
			'friendly'    => __('Friendly', 'rds-ai-excerpt'),
			'professional' => __('Professional', 'rds-ai-excerpt'),
		);
	?>
		<select name="rds_ai_excerpt_settings[default_tone]">
			<?php foreach ($tones as $key => $label): ?>
				<option value="<?php echo esc_attr($key); ?>"
					<?php selected($value, $key); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	public function render_default_language_field()
	{
		$value = rds_ai_excerpt_get_option('default_language', 'en');
		$languages = array(
			'en' => 'English',
			'ru' => 'Русский',
			'es' => 'Español',
			'fr' => 'Français',
			'de' => 'Deutsch',
			'it' => 'Italiano',
			'pt' => 'Português',
			'zh' => '中文',
			'ja' => '日本語',
			'ko' => '한국어',
		);
	?>
		<select name="rds_ai_excerpt_settings[default_language]">
			<?php foreach ($languages as $key => $label): ?>
				<option value="<?php echo esc_attr($key); ?>"
					<?php selected($value, $key); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
	<?php
	}

	public function render_default_max_length_field()
	{
		$value = rds_ai_excerpt_get_option('default_max_length', 150);
	?>
		<input type="number" name="rds_ai_excerpt_settings[default_max_length]"
			value="<?php echo esc_attr($value); ?>" min="50" max="500" step="10">
		<p class="description">
			<?php _e('Maximum length of generated excerpt in words', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_default_focus_keywords_field()
	{
		$value = rds_ai_excerpt_get_option('default_focus_keywords', '');
	?>
		<input type="text" name="rds_ai_excerpt_settings[default_focus_keywords]"
			value="<?php echo esc_attr($value); ?>" class="regular-text">
		<p class="description">
			<?php _e('Default focus keywords (comma-separated)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_system_prompt_field()
	{
		$value = rds_ai_excerpt_get_option(
			'system_prompt',
			'Generate a concise and engaging excerpt for a blog post titled "{{title}}". ' .
				'Use a {{tone}} tone and {{style}} writing style. ' .
				'The excerpt should be approximately {{max_length}} words. ' .
				'Write in {{language}} language. ' .
				'Focus on these keywords if relevant: {{focus_keywords}}. ' .
				'The excerpt should capture the main idea and encourage readers to read the full article. ' .
				'Do not use markdown, quotes, or any formatting - just plain text.'
		);
	?>
		<textarea name="rds_ai_excerpt_settings[system_prompt]"
			id="rds_ai_excerpt_settings_system_prompt"
			rows="6" class="large-text"><?php echo esc_textarea($value); ?></textarea>
		<p class="description">
			<?php _e('System prompt for AI. Available variables: {{content}}, {{title}}, {{style}}, {{tone}}, {{language}}, {{max_length}}, {{focus_keywords}}', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	// Post Types Field
	public function render_enabled_post_types_field()
	{
		$value = rds_ai_excerpt_get_option('enabled_post_types', array('post'));
		$post_types = get_post_types(array('public' => true), 'objects');

		// Remove attachment
		unset($post_types['attachment']);
	?>
		<div class="checkbox-list">
			<?php foreach ($post_types as $post_type): ?>
				<label>
					<input type="checkbox"
						name="rds_ai_excerpt_settings[enabled_post_types][]"
						value="<?php echo esc_attr($post_type->name); ?>"
						<?php checked(in_array($post_type->name, $value)); ?>>
					<?php echo esc_html($post_type->labels->singular_name); ?>
				</label>
			<?php endforeach; ?>
		</div>
	<?php
	}

	// Security & Limits Fields
	public function render_max_content_length_field()
	{
		$value = rds_ai_excerpt_get_option('max_content_length', 4000);
	?>
		<input type="number" name="rds_ai_excerpt_settings[max_content_length]"
			value="<?php echo esc_attr($value); ?>" min="1000" max="16000" step="100">
		<p class="description">
			<?php _e('Maximum characters to send to API (to manage token usage)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_request_timeout_field()
	{
		$value = rds_ai_excerpt_get_option('request_timeout', 30);
	?>
		<input type="number" name="rds_ai_excerpt_settings[request_timeout]"
			value="<?php echo esc_attr($value); ?>" min="10" max="120" step="5">
		<p class="description">
			<?php _e('Timeout for API requests in seconds', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_allowed_user_roles_field()
	{
		$value = rds_ai_excerpt_get_option('allowed_user_roles', array('administrator', 'editor', 'author'));
		$roles = get_editable_roles();
	?>
		<div class="checkbox-list">
			<?php foreach ($roles as $role_name => $role_info): ?>
				<label>
					<input type="checkbox"
						name="rds_ai_excerpt_settings[allowed_user_roles][]"
						value="<?php echo esc_attr($role_name); ?>"
						<?php checked(in_array($role_name, $value)); ?>>
					<?php echo esc_html(translate_user_role($role_info['name'])); ?>
				</label>
			<?php endforeach; ?>
		</div>
		<p class="description">
			<?php _e('User roles allowed to use the AI Excerpt Generator', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	public function render_enable_logging_field()
	{
		$value = rds_ai_excerpt_get_option('enable_logging', false);
	?>
		<label>
			<input type="checkbox"
				name="rds_ai_excerpt_settings[enable_logging]"
				value="1" <?php checked($value); ?>>
			<?php _e('Enable debug logging to file', 'rds-ai-excerpt'); ?>
		</label>
		<p class="description">
			<?php
			printf(
				__('Logs are saved to: %s', 'rds-ai-excerpt'),
				'<code>' . WP_CONTENT_DIR . '/rds-ai-excerpt-logs/debug.log</code>'
			);
			?>
		</p>
<?php
	}
}
