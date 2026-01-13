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
		// Add menu under RDS AI Engine
		add_action('admin_menu', array($this, 'add_admin_menu'));

		// Register settings
		add_action('admin_init', array($this, 'register_settings'));

		// Enqueue scripts
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

		// Add settings link to plugins page
		add_filter('plugin_action_links_' . RDS_AI_EXCERPT_BASENAME, array($this, 'add_settings_link'));
	}

	/**
	 * Add settings link to plugins page
	 */
	public function add_settings_link($links)
	{
		$settings_link = '<a href="' . admin_url('admin.php?page=rds-ai-excerpt-settings') . '">' .
			__('Settings', 'rds-ai-excerpt') . '</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	/**
	 * Add admin menu under RDS AI Engine
	 */
	public function add_admin_menu()
	{
		// Check if RDS AI Engine menu exists
		$ai_engine_menu_exists = false;
		global $menu;

		foreach ($menu as $item) {
			if (isset($item[2]) && strpos($item[2], 'rds-ai-engine') !== false) {
				$ai_engine_menu_exists = true;
				break;
			}
		}

		// Add as submenu under RDS AI Engine if exists, otherwise add as standalone
		if ($ai_engine_menu_exists) {
			add_submenu_page(
				'rds-ai-engine', // Parent slug (RDS AI Engine main menu)
				__('AI Excerpt Generator Settings', 'rds-ai-excerpt'),
				__('AI Excerpt', 'rds-ai-excerpt'),
				'manage_options',
				'rds-ai-excerpt-settings',
				array($this, 'render_settings_page')
			);
		} else {
			// Fallback: Add as standalone menu item
			add_menu_page(
				__('AI Excerpt Generator', 'rds-ai-excerpt'),
				__('AI Excerpt', 'rds-ai-excerpt'),
				'manage_options',
				'rds-ai-excerpt-settings',
				array($this, 'render_settings_page'),
				'dashicons-text',
				30
			);
		}
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

		// Connection Section
		add_settings_section(
			'rds_ai_excerpt_connection_section',
			__('AI Connection', 'rds-ai-excerpt'),
			array($this, 'render_connection_section'),
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

		// Debug Section
		add_settings_section(
			'rds_ai_excerpt_debug_section',
			__('Debug & Logging', 'rds-ai-excerpt'),
			array($this, 'render_debug_section'),
			'rds-ai-excerpt-settings'
		);

		// Connection Fields
		add_settings_field(
			'selected_model_id',
			__('Select AI Model', 'rds-ai-excerpt'),
			array($this, 'render_model_selection_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_connection_section'
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
			'allowed_user_roles',
			__('Allowed User Roles', 'rds-ai-excerpt'),
			array($this, 'render_allowed_user_roles_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_security_section'
		);

		// Debug Fields
		add_settings_field(
			'enable_logging',
			__('Enable Debug Logging', 'rds-ai-excerpt'),
			array($this, 'render_enable_logging_field'),
			'rds-ai-excerpt-settings',
			'rds_ai_excerpt_debug_section'
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings($input)
	{
		$sanitized = array();

		// Connection
		$sanitized['selected_model_id'] = isset($input['selected_model_id']) ? absint($input['selected_model_id']) : '';

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
		// Check if we're on our settings page
		if (strpos($hook, 'rds-ai-excerpt-settings') === false) {
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

		// Get engine integration
		$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();

?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>

			<?php if (isset($_GET['settings-updated'])): ?>
				<div class="notice notice-success is-dismissible">
					<p><?php _e('Settings saved successfully!', 'rds-ai-excerpt'); ?></p>
				</div>
			<?php endif; ?>

			<?php if (!$engine_integration->is_ai_engine_available()): ?>
				<div class="notice notice-error">
					<p>
						<strong><?php _e('RDS AI Engine Required', 'rds-ai-excerpt'); ?>:</strong>
						<?php _e('Please install and activate RDS AI Engine plugin first.', 'rds-ai-excerpt'); ?>
						<a href="<?php echo admin_url('plugin-install.php?s=RDS+AI+Engine&tab=search&type=term'); ?>">
							<?php _e('Install RDS AI Engine', 'rds-ai-excerpt'); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<div class="rds-ai-excerpt-settings-container">
				<form method="post" action="options.php">
					<?php
					settings_fields('rds_ai_excerpt_settings_group');
					do_settings_sections('rds-ai-excerpt-settings');
					submit_button();
					?>
				</form>

				<div class="rds-ai-excerpt-sidebar" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
					<h2><?php _e('Quick Actions', 'rds-ai-excerpt'); ?></h2>

					<div id="rds-ai-connection-status" style="margin-bottom: 15px;">
						<?php
						$selected_model = rds_ai_excerpt_get_option('selected_model_id');
						if (empty($selected_model)) {
							echo '<p class="notice notice-warning" style="margin: 0; padding: 10px;">' .
								__('Please select an AI model first.', 'rds-ai-excerpt') . '</p>';
						} else {
							$model_name = $engine_integration->get_model_name($selected_model);
							echo '<p><strong>' . __('Selected Model:', 'rds-ai-excerpt') . '</strong><br>' .
								esc_html($model_name) . '</p>';
						}
						?>
					</div>

					<button type="button" id="rds-ai-test-connection" class="button button-secondary"
						style="width: 100%; margin-bottom: 10px;"
						<?php echo empty($selected_model) ? 'disabled' : ''; ?>>
						<?php _e('Test AI Connection', 'rds-ai-excerpt'); ?>
					</button>

					<div id="rds-ai-test-result" style="display: none; margin-top: 10px; padding: 10px; border-radius: 3px;"></div>

					<hr style="margin: 20px 0;">

					<h3><?php _e('Documentation', 'rds-ai-excerpt'); ?></h3>
					<ul style="margin-left: 20px;">
						<li><a href="https://github.com/raetsky-dmitry/rds-ai-excerpt-generator" target="_blank">GitHub Repository</a></li>
						<li><a href="#" id="rds-ai-show-variables">Show Prompt Variables</a></li>
						<li><a href="#" id="rds-ai-reset-defaults">Reset to Defaults</a></li>
					</ul>
				</div>
			</div>

			<div class="rds-ai-excerpt-debug-info" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
				<h2><?php _e('System Information', 'rds-ai-excerpt'); ?></h2>
				<table class="widefat" style="margin-top: 10px;">
					<tr>
						<td style="width: 200px;"><strong><?php _e('Plugin version:', 'rds-ai-excerpt'); ?></strong></td>
						<td><?php echo esc_html(RDS_AI_EXCERPT_VERSION); ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('Available AI Models:', 'rds-ai-excerpt'); ?></strong></td>
						<td>
							<?php
							$models = $engine_integration->get_available_models();
							if (empty($models)) {
								echo '<span style="color: #d63638;">' . __('No models found', 'rds-ai-excerpt') . '</span>';
							} else {
								echo count($models);
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e('Log directory:', 'rds-ai-excerpt'); ?></strong></td>
						<td>
							<?php
							$log_dir = WP_CONTENT_DIR . '/rds-ai-excerpt-logs';
							echo '<code>' . esc_html($log_dir) . '</code>';
							if (!file_exists($log_dir)) {
								echo ' <span class="dashicons dashicons-warning" style="color: #d63638;"></span> ' .
									__('Not found', 'rds-ai-excerpt');
							} else {
								echo ' <span class="dashicons dashicons-yes" style="color: #46b450;"></span>';
							}
							?>
						</td>
					</tr>
					<tr>
						<td><strong><?php _e('PHP version:', 'rds-ai-excerpt'); ?></strong></td>
						<td><?php echo PHP_VERSION; ?></td>
					</tr>
					<tr>
						<td><strong><?php _e('WordPress version:', 'rds-ai-excerpt'); ?></strong></td>
						<td><?php global $wp_version;
							echo $wp_version; ?></td>
					</tr>
				</table>
			</div>
		</div>

		<!-- Variables Modal -->
		<div id="rds-ai-variables-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
			<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 5px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
				<h2 style="margin-top: 0;"><?php _e('Available Prompt Variables', 'rds-ai-excerpt'); ?></h2>

				<div style="margin-bottom: 15px; padding: 10px; background: #fff8e5; border-left: 4px solid #dba617;">
					<strong><?php _e('Important:', 'rds-ai-excerpt'); ?></strong>
					<?php _e('The <code>{{content}}</code> variable is <strong>REQUIRED</strong>. It will be replaced with the actual post content.', 'rds-ai-excerpt'); ?>
					<?php _e('Other variables are optional.', 'rds-ai-excerpt'); ?>
				</div>

				<table class="widefat" style="margin-bottom: 15px;">
					<thead>
						<tr>
							<th style="width: 150px;"><?php _e('Variable', 'rds-ai-excerpt'); ?></th>
							<th><?php _e('Description', 'rds-ai-excerpt'); ?></th>
							<th style="width: 100px;"><?php _e('Required', 'rds-ai-excerpt'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>{{content}}</code></td>
							<td><?php _e('The post content to summarize', 'rds-ai-excerpt'); ?></td>
							<td><span style="color: #d63638; font-weight: bold;"><?php _e('YES', 'rds-ai-excerpt'); ?></span></td>
						</tr>
						<tr>
							<td><code>{{title}}</code></td>
							<td><?php _e('The post title', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
						<tr>
							<td><code>{{style}}</code></td>
							<td><?php _e('Writing style (descriptive, advertising, business, creative)', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
						<tr>
							<td><code>{{tone}}</code></td>
							<td><?php _e('Tone (neutral, formal, friendly, professional)', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
						<tr>
							<td><code>{{language}}</code></td>
							<td><?php _e('Language code (en, ru, es, fr, de, etc.)', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
						<tr>
							<td><code>{{max_length}}</code></td>
							<td><?php _e('Maximum length in words (e.g., 150)', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
						<tr>
							<td><code>{{focus_keywords}}</code></td>
							<td><?php _e('Keywords to focus on (comma-separated)', 'rds-ai-excerpt'); ?></td>
							<td><?php _e('No', 'rds-ai-excerpt'); ?></td>
						</tr>
					</tbody>
				</table>

				<h3><?php _e('Example Prompts', 'rds-ai-excerpt'); ?></h3>

				<div style="margin-bottom: 10px; padding: 10px; background: #f9f9f9; border-radius: 3px;">
					<strong><?php _e('Basic Example:', 'rds-ai-excerpt'); ?></strong>
					<pre style="margin: 5px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; overflow-x: auto;">
Generate an excerpt for this blog post:

{{content}}

Style: {{style}}
Tone: {{tone}}
Length: {{max_length}} words
Language: {{language}}</pre>
				</div>

				<div style="padding: 10px; background: #f9f9f9; border-radius: 3px;">
					<strong><?php _e('Detailed Example:', 'rds-ai-excerpt'); ?></strong>
					<pre style="margin: 5px 0; padding: 10px; background: #fff; border: 1px solid #ddd; border-radius: 3px; font-size: 12px; overflow-x: auto;">
Create a compelling excerpt for the blog post titled "{{title}}".

Content to summarize:
{{content}}

Requirements:
- Writing style: {{style}}
- Tone: {{tone}}
- Target length: {{max_length}} words
- Language: {{language}}
- Focus on these keywords: {{focus_keywords}}

The excerpt should capture the main ideas and encourage readers to continue reading the full article.</pre>
				</div>

				<p style="margin-top: 15px; text-align: center;">
					<button type="button" id="rds-ai-close-modal" class="button button-primary"><?php _e('Close', 'rds-ai-excerpt'); ?></button>
				</p>
			</div>
		</div>

		<script type="text/javascript">
			var rdsAIExcerptSettings = {
				ajaxurl: '<?php echo admin_url("admin-ajax.php"); ?>',
				nonce: '<?php echo wp_create_nonce("rds_ai_excerpt_admin_nonce"); ?>',
				strings: {
					testing: '<?php _e('Testing...', 'rds-ai-excerpt'); ?>',
					testSuccess: '<?php _e('Connection successful!', 'rds-ai-excerpt'); ?>',
					testFailed: '<?php _e('Connection failed.', 'rds-ai-excerpt'); ?>',
					testError: '<?php _e('Error', 'rds-ai-excerpt'); ?>',
					noModel: '<?php _e('Please select a model first.', 'rds-ai-excerpt'); ?>',
					resetConfirm: '<?php _e('Are you sure you want to reset all settings to defaults?', 'rds-ai-excerpt'); ?>',
					resetSuccess: '<?php _e('Settings reset successfully.', 'rds-ai-excerpt'); ?>'
				}
			};

			jQuery(document).ready(function($) {
				// Show variables modal
				$('#rds-ai-show-variables').on('click', function(e) {
					e.preventDefault();
					$('#rds-ai-variables-modal').fadeIn();
				});

				// Close modal
				$('#rds-ai-close-modal').on('click', function() {
					$('#rds-ai-variables-modal').fadeOut();
				});

				// Close modal on background click
				$('#rds-ai-variables-modal').on('click', function(e) {
					if (e.target === this) {
						$(this).fadeOut();
					}
				});

				// Reset to defaults
				$('#rds-ai-reset-defaults').on('click', function(e) {
					e.preventDefault();
					if (confirm(rdsAIExcerptSettings.strings.resetConfirm)) {
						// This would need AJAX implementation
						alert('Reset functionality would be implemented here.');
					}
				});
			});
		</script>

		<style>
			.rds-ai-excerpt-settings-container {
				display: flex;
				gap: 30px;
				flex-wrap: wrap;
			}

			.rds-ai-excerpt-settings-container form {
				flex: 1;
				min-width: 300px;
			}

			.rds-ai-excerpt-sidebar {
				flex: 0 0 300px;
				min-width: 300px;
			}

			@media (max-width: 782px) {
				.rds-ai-excerpt-settings-container {
					flex-direction: column;
				}

				.rds-ai-excerpt-sidebar {
					flex: none;
					width: 100%;
				}
			}

			.checkbox-list {
				max-height: 200px;
				overflow-y: auto;
				border: 1px solid #ddd;
				padding: 10px;
				background: #fff;
				border-radius: 3px;
			}

			.checkbox-list label {
				display: block;
				margin-bottom: 8px;
				padding: 3px 0;
			}

			.checkbox-list input[type="checkbox"] {
				margin-right: 8px;
			}
		</style>
	<?php
	}
    
    // ========== SECTION RENDERERS ==========

	/**
	 * Render connection section
	 */
	public function render_connection_section()
	{
		echo '<p>' . __('Select the AI model to use for excerpt generation.', 'rds-ai-excerpt') . '</p>';
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
		echo '<p>' . __('Configure security settings and limits.', 'rds-ai-excerpt') . '</p>';
	}

	/**
	 * Render debug section
	 */
	public function render_debug_section()
	{
		echo '<p>' . __('Debug and logging settings.', 'rds-ai-excerpt') . '</p>';
	}
    
    // ========== FIELD RENDERERS ==========

	/**
	 * Render model selection field
	 */
	public function render_model_selection_field()
	{
		$selected = rds_ai_excerpt_get_option('selected_model_id');
		$engine_integration = RDS_AI_Excerpt_Engine_Integration::get_instance();
		$models = $engine_integration->get_available_models();

	?>
		<select name="rds_ai_excerpt_settings[selected_model_id]" id="rds_ai_excerpt_selected_model_id" class="regular-text">
			<option value=""><?php _e('-- Select Model --', 'rds-ai-excerpt'); ?></option>
			<?php if (is_array($models) && !empty($models)): ?>
				<?php foreach ($models as $model): ?>
					<?php
					// Extract model data based on format (array or object)
					if (is_array($model)) {
						$model_id = isset($model['id']) ? $model['id'] : 0;
						$model_name = isset($model['name']) ? $model['name'] : __('Unknown Model', 'rds-ai-excerpt');
					} else if (is_object($model)) {
						$model_id = isset($model->id) ? $model->id : 0;
						$model_name = isset($model->name) ? $model->name : __('Unknown Model', 'rds-ai-excerpt');
					} else {
						continue;
					}

					if ($model_id): ?>
						<option value="<?php echo esc_attr($model_id); ?>"
							<?php selected($selected, $model_id); ?>>
							<?php echo esc_html($model_name); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>

		<?php if (empty($models)): ?>
			<p class="description" style="color: #d63638;">
				<span class="dashicons dashicons-warning"></span>
				<?php _e('No AI models found. Please check RDS AI Engine plugin configuration.', 'rds-ai-excerpt'); ?>
			</p>
		<?php else: ?>
			<p class="description">
				<?php _e('Select an AI model from RDS AI Engine plugin.', 'rds-ai-excerpt'); ?>
			</p>
		<?php endif; ?>
	<?php
	}

	/**
	 * Render default style field
	 */
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

	/**
	 * Render default tone field
	 */
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

	/**
	 * Render default language field
	 */
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

	/**
	 * Render default max length field
	 */
	public function render_default_max_length_field()
	{
		$value = rds_ai_excerpt_get_option('default_max_length', 150);
	?>
		<input type="number" name="rds_ai_excerpt_settings[default_max_length]"
			value="<?php echo esc_attr($value); ?>" min="50" max="500" step="10" class="small-text">
		<p class="description">
			<?php _e('Maximum length of generated excerpt in words', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	/**
	 * Render default focus keywords field
	 */
	public function render_default_focus_keywords_field()
	{
		$value = rds_ai_excerpt_get_option('default_focus_keywords', '');
	?>
		<input type="text" name="rds_ai_excerpt_settings[default_focus_keywords]"
			value="<?php echo esc_attr($value); ?>" class="regular-text"
			placeholder="<?php esc_attr_e('keyword1, keyword2, keyword3', 'rds-ai-excerpt'); ?>">
		<p class="description">
			<?php _e('Default focus keywords (comma-separated)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	/**
	 * Render system prompt field
	 */
	public function render_system_prompt_field()
	{
		$value = rds_ai_excerpt_get_option(
			'system_prompt',
			'Generate a concise and engaging excerpt for a blog post.

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
5. Output only the excerpt text'
		);
	?>
		<textarea name="rds_ai_excerpt_settings[system_prompt]"
			id="rds_ai_excerpt_system_prompt"
			rows="10" class="large-text"
			placeholder="<?php esc_attr_e('Enter your system prompt here...', 'rds-ai-excerpt'); ?>"><?php echo esc_textarea($value); ?></textarea>

		<div class="prompt-help" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">
			<h4 style="margin-top: 0;"><?php _e('Prompt Variables', 'rds-ai-excerpt'); ?></h4>
			<table style="width: 100%; border-collapse: collapse;">
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{content}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;">
						<strong><?php _e('REQUIRED', 'rds-ai-excerpt'); ?></strong> -
						<?php _e('The post content to summarize', 'rds-ai-excerpt'); ?>
					</td>
				</tr>
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{title}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><?php _e('The post title', 'rds-ai-excerpt'); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{style}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><?php _e('Writing style (descriptive, advertising, etc.)', 'rds-ai-excerpt'); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{tone}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><?php _e('Tone (neutral, formal, friendly, etc.)', 'rds-ai-excerpt'); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{language}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><?php _e('Language code (en, ru, es, etc.)', 'rds-ai-excerpt'); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><code>{{max_length}}</code></td>
					<td style="padding: 5px; border-bottom: 1px solid #ddd;"><?php _e('Maximum length in words', 'rds-ai-excerpt'); ?></td>
				</tr>
				<tr>
					<td style="padding: 5px;"><code>{{focus_keywords}}</code></td>
					<td style="padding: 5px;"><?php _e('Keywords to focus on', 'rds-ai-excerpt'); ?></td>
				</tr>
			</table>

			<p style="margin-top: 10px; margin-bottom: 0;">
				<strong><?php _e('Important:', 'rds-ai-excerpt'); ?></strong>
				<?php _e('You must include <code>{{content}}</code> variable in your prompt where you want the post content to appear.', 'rds-ai-excerpt'); ?>
				<?php _e('The content will NOT be added automatically.', 'rds-ai-excerpt'); ?>
			</p>
		</div>
	<?php
	}

	/**
	 * Render enabled post types field
	 */
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
				</label><br>
			<?php endforeach; ?>
		</div>
		<p class="description">
			<?php _e('Select post types where AI Excerpt Generator should be available.', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	/**
	 * Render max content length field
	 */
	public function render_max_content_length_field()
	{
		$value = rds_ai_excerpt_get_option('max_content_length', 4000);
	?>
		<input type="number" name="rds_ai_excerpt_settings[max_content_length]"
			value="<?php echo esc_attr($value); ?>" min="1000" max="16000" step="100" class="small-text">
		<p class="description">
			<?php _e('Maximum characters to send to AI (to manage token usage)', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	/**
	 * Render allowed user roles field
	 */
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
				</label><br>
			<?php endforeach; ?>
		</div>
		<p class="description">
			<?php _e('User roles allowed to use the AI Excerpt Generator', 'rds-ai-excerpt'); ?>
		</p>
	<?php
	}

	/**
	 * Render enable logging field
	 */
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
