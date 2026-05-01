<?php
/**
 * テンプレートファイルのロード制御。
 *
 * 設計書 3.6 を参照。
 * - 子テーマに `single-yd_doctor.php` / `archive-yd_doctor.php` があればそちらを優先
 * - 無ければプラグイン同梱のテンプレートを採用
 * - yd_doctor 関連ページでのみ専用CSSを enqueue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Template_Loader {

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_filter( 'template_include', [ __CLASS__, 'maybe_load_plugin_template' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * yd_doctor シングル/アーカイブで、テーマに専用テンプレートが無ければ
	 * プラグイン内のテンプレートを採用する。
	 *
	 * @param string $template
	 * @return string
	 */
	public static function maybe_load_plugin_template( $template ) {
		if ( is_singular( YD_CPT_Doctor::POST_TYPE ) ) {
			if ( basename( (string) $template ) === 'single-yd_doctor.php' ) {
				return $template;
			}
			$plugin_template = YD_SUPERVISOR_DIR . 'templates/single-yd_doctor.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		if ( is_post_type_archive( YD_CPT_Doctor::POST_TYPE ) ) {
			if ( basename( (string) $template ) === 'archive-yd_doctor.php' ) {
				return $template;
			}
			$plugin_template = YD_SUPERVISOR_DIR . 'templates/archive-yd_doctor.php';
			if ( file_exists( $plugin_template ) ) {
				return $plugin_template;
			}
		}

		return $template;
	}

	/**
	 * 監修医師ページでのみ doctor-pages.css を読み込む。
	 */
	public static function enqueue_assets() {
		if ( ! is_singular( YD_CPT_Doctor::POST_TYPE ) && ! is_post_type_archive( YD_CPT_Doctor::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_style(
			'yd-doctor-pages',
			YD_SUPERVISOR_URL . 'assets/css/doctor-pages.css',
			[],
			YD_SUPERVISOR_VERSION
		);
	}
}
