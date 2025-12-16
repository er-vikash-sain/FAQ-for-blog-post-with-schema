<?php
/**
 * Uninstall handler for FAQ plugin.
 *
 * @package FAQ_Plugin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_display     = 'faq_plugin_display_position';
$option_delete_data = 'faq_plugin_delete_data_on_uninstall';
$meta_key           = 'faq_plugin_faqs';

$should_delete = get_option( $option_delete_data, 0 );

if ( $should_delete ) {
	delete_option( $option_display );
	delete_option( $option_delete_data );
	delete_post_meta_by_key( $meta_key );
}
