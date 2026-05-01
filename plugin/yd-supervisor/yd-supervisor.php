<?php
/**
 * Plugin Name: YD Supervisor
 * Plugin URI:  https://your-doctor.jp/media/
 * Description: 監修医師カスタム投稿タイプ・ターゲットKW記録・構造化データ自動出力（AIOSEO連携）。
 * Version:     0.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author:      株式会社YUKATAN
 * Author URI:  https://yukatan.co.jp/
 * Text Domain: yd-supervisor
 * Domain Path: /languages
 * License:     Proprietary
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YD_SUPERVISOR_VERSION', '0.4.0' );
define( 'YD_SUPERVISOR_FILE', __FILE__ );
define( 'YD_SUPERVISOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'YD_SUPERVISOR_URL', plugin_dir_url( __FILE__ ) );

require_once YD_SUPERVISOR_DIR . 'inc/class-cpt-doctor.php';
require_once YD_SUPERVISOR_DIR . 'inc/class-post-meta.php';
require_once YD_SUPERVISOR_DIR . 'inc/class-acf-fields.php';
require_once YD_SUPERVISOR_DIR . 'inc/class-template-loader.php';
require_once YD_SUPERVISOR_DIR . 'inc/helpers.php';

add_action( 'init', [ 'YD_CPT_Doctor', 'register' ] );
add_action( 'init', [ 'YD_Post_Meta', 'register' ] );
add_action( 'acf/init', [ 'YD_ACF_Fields', 'register' ] );
YD_Template_Loader::init();

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain(
		'yd-supervisor',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
} );

/**
 * ACF Pro が無効な場合は管理画面に警告を出す。
 */
add_action( 'admin_notices', function () {
	if ( function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>';
	echo esc_html__( 'YD Supervisor: Advanced Custom Fields PRO が有効化されていません。監修医師のカスタムフィールドが登録されません。', 'yd-supervisor' );
	echo '</p></div>';
} );

register_activation_hook( __FILE__, function () {
	YD_CPT_Doctor::register();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
