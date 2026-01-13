<?php

/**
 * Uninstall script for RDS AI Excerpt Generator
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// Include activator class
require_once plugin_dir_path(__FILE__) . 'includes/class-activator.php';

// Run uninstall tasks
RDS_AI_Excerpt_Activator::uninstall();
