<?php
/**
 * Plugin Name: QuestUno
 * Description: Creates interactive experiences based on QR Code checkpoints.
 * Version: 1.0.2
 * Requires at least: 6.7
 * Requires PHP: 8.2
 * Author: mjfan80
 * License: GPL-2.0-or-later
 * Text Domain: questuno
 * Domain Path: /languages
 *
 * @package QuestUno
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( QuestUno\Activation::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( QuestUno\Deactivation::class, 'deactivate' ) );

$questuno_plugin = new QuestUno\Plugin();
$questuno_plugin->register_hooks();
