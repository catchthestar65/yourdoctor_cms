<?php
/**
 * 記事下に監修医師カードを差し込む。
 *
 * 設計書 §6.1 / 7.2 では子テーマ single.php に挿入する案だが、SWELL は
 * 親テーマで複雑な single ロジックを持つため、子テーマ側の上書きは
 * リグレッションリスクが高い。代わりに `the_content` フィルターで
 * 本文末尾に追記する方針を採用（テーマ非依存）。
 *
 * - 監修者が 1 名以上いるシングル投稿（post）にのみ挿入
 * - カードのHTMLは yd_render_supervisor_cards() ヘルパーが返す
 * - 専用 CSS は監修者ありシングル投稿でのみ enqueue
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Article_Supervisor {

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_filter( 'the_content', [ __CLASS__, 'append_supervisor_cards' ], 20 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * 本文末尾に監修者カード HTML を追記する。
	 *
	 * @param string $content
	 * @return string
	 */
	public static function append_supervisor_cards( $content ) {
		if ( is_admin() ) {
			return $content;
		}
		if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$cards = yd_render_supervisor_cards( get_the_ID() );
		if ( $cards ) {
			$content .= $cards;
		}
		return $content;
	}

	/**
	 * 専用 CSS を必要なときだけ読み込む。
	 */
	public static function enqueue_assets() {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		// 監修者ゼロなら何も出さないので CSS も不要
		$post_id = (int) get_queried_object_id();
		if ( empty( yd_get_post_supervisors( $post_id ) ) ) {
			return;
		}

		wp_enqueue_style(
			'yd-article-supervisor',
			YD_SUPERVISOR_URL . 'assets/css/article-supervisor.css',
			[],
			YD_SUPERVISOR_VERSION
		);
	}
}
