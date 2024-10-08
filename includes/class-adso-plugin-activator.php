<?php

/**
 * Fired during plugin activation
 *
 * @link       https://adso.co.nz
 * @since      1.0.0
 *
 * @package    Adso_Plugin
 * @subpackage Adso_Plugin/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Adso_Plugin
 * @subpackage Adso_Plugin/includes
 * @author     Timothy Lopez <timothy@adso.co.nz>
 */
class Adso_Plugin_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Please install and activate WooCommerce before activating this plugin.', 'adso-plugin' ), 'Plugin dependency check', array( 'back_link' => true ) );
		}
	}

}
