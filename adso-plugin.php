<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://adso.co.nz
 * @since             1.0.0
 * @package           Adso_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       ADSO-Plugin
 * Plugin URI:        https://adso.co.nz/plugin
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            Timothy Lopez
 * Author URI:        https://adso.co.nz/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       adso-plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Define the version that can be used to ensure that the plugin files are up to date.
 */
define( 'ADSO_PLUGIN_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-adso-plugin-activator.php
 */
function activate_adso_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adso-plugin-activator.php';
	Adso_Plugin_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-adso-plugin-deactivator.php
 */
function deactivate_adso_plugin() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-adso-plugin-deactivator.php';
	Adso_Plugin_Deactivator::deactivate();
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'activate_adso_plugin' );
register_deactivation_hook( __FILE__, 'deactivate_adso_plugin' );

/**
 * Include the core plugin class and settings class that defines all hooks for the plugin.
 * This ensures that all core functionality is registered with WordPress.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-adso-plugin.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-adso-plugin-settings.php';

/**
 * Begins execution of the plugin.
 * Instantiates the main plugin class, and then calls its run method,
 * which sets up all the hooks and filters used by the plugin.
 */
function run_adso_plugin() {
	$plugin = new Adso_Plugin();
	$plugin->run();
}

// Kick off the plugin by calling the run function.
run_adso_plugin();
