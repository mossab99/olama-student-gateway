<?php
/**
 * Plugin Name: OLAMA Student Gateway
 * Description: Family-first, read-only gateway for student and family information produced by OLAMA services.
 * Version: 0.2.0
 * Author: OLAMA
 * Text Domain: olama-student-gateway
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OLAMA_STUDENT_GATEWAY_VERSION', '0.2.0');
define('OLAMA_STUDENT_GATEWAY_FILE', __FILE__);
define('OLAMA_STUDENT_GATEWAY_PATH', plugin_dir_path(__FILE__));
define('OLAMA_STUDENT_GATEWAY_URL', plugin_dir_url(__FILE__));

require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/class-provider-interface.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/class-provider-registry.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/class-access-context.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-core-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-weekly-plan-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-exams-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-transportation-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-stores-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/providers/class-filter-provider.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/class-shortcode.php';
require_once OLAMA_STUDENT_GATEWAY_PATH . 'includes/class-plugin.php';

add_action('plugins_loaded', array('Olama_Student_Gateway_Plugin', 'instance'), 40);
