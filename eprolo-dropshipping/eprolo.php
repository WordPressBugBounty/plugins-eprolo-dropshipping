<?php
/*
   Plugin Name: EPROLO-Dropshipping
   Plugin URI: http://wordpress.org/extend/plugins/eprolo/
   Version: 2.1.1
   Author: EPROLO
   Description: EPROLO Dropshipping and aliexpress importer
   Text Domain: EPROLO
   Author URI:   https://www.eprolo.com
  */

//PHP minimum required version
$eprolo_minimalRequiredPhpVersion = '5.6';

/**
 * Prompt after PHP version error
 */
function Eprolo_noticePhpVersionWrong() {
	global $eprolo_minimalRequiredPhpVersion;
	echo '<div class="updated fade">EPROLO requires a newer version of PHP to be running </div>';
}

/**
 * Check version
 */
function eprolo_PhpVersionCheck() {
	global $eprolo_minimalRequiredPhpVersion;
	if ( version_compare( phpversion(), $eprolo_minimalRequiredPhpVersion ) < 0 ) {
		add_action( 'admin_notices', 'Eprolo_noticePhpVersionWrong' );
		return false;
	}
	return true;
}

/**
 *  Initialize the internationalization of this plugin (i18n). Different voices, none, default English
 *
 * @return void
 */
function eprolo_i18n_init() {
	$pluginDir = dirname( plugin_basename( __FILE__ ) );
	load_plugin_textdomain( 'eprolo', false, $pluginDir . '/languages/' );
}


// Adding method
add_action( 'plugins_loadedi', 'eprolo_i18n_init' );


//Check PHP version
if ( !eprolo_PhpVersionCheck() ) {
	// Only load and run the init function if we know PHP version can parse it
	return;
}

include_once 'Eprolo_init.php';
eprolo_init( __FILE__ );

//Define external AJAX interface
require_once 'Eprolo_AJAX.php';
// 添加配置选项
function eprolo_add_settings() {
    register_setting('eprolo_options', 'eprolo_enable_tracking', [
        'default' => '1'
    ]);
}
add_action('admin_init', 'eprolo_add_settings');
include_once plugin_dir_path(__FILE__) . 'Eprolo_tracking_api.php';
// 修改跟踪文件加载逻辑
if (get_option('eprolo_enable_tracking', '1') === '1') {
    include_once plugin_dir_path(__FILE__) . 'Eprolo_tracking.php';
}
function eprolo_disconnect_init() {
	$aPlugin = new Eprolo_AJAX();
	$aPlugin->eprolo_disconnect();
}
function eprolo_connect_key_init() {
	 $aPlugin = new Eprolo_AJAX();
	$aPlugin->eprolo_connect_key();
}
function eprolo_reflsh_init() {
	$aPlugin = new Eprolo_AJAX();
	$aPlugin->eprolo_reflsh();
}
function eprolo_aftership_init() {
	$aPlugin = new Eprolo_AJAX();
	$aPlugin->handle_get_all_orders();
}
// Interface Join Action
add_action('wp_ajax_aftership_get_all_orders','eprolo_aftership_init');
add_action( 'wp_ajax_eprolo_disconnect', 'eprolo_disconnect_init' );
add_action( 'wp_ajax_eprolo_connect_key', 'eprolo_connect_key_init' );
add_action( 'wp_ajax_eprolo_reflsh', 'eprolo_reflsh_init' );
