<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Hide_Cart_Functions
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only delete data if the user has opted in.
if ( (int) get_option( 'hwcf_delete_on_deactivation', 0 ) === 1 ) {
	delete_option( 'hwcf_delete_on_deactivation' );
	delete_option( 'hwcf_notice_dismiss' );
	delete_option( 'hwcf_version_1_0_0_installed' );
	delete_option( 'hwcf_settings_data' );
	delete_option( 'hwcf_settings_ids_increament' );
	delete_option( 'hwcf_cripple_bots' );
	delete_option( 'hwcf_disable_purchases' );
}
