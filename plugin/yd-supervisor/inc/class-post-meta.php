<?php
/**
 * register_post_meta() でカスタムメタを REST API に公開する。
 *
 * 設計書 §4: `post` の `yd_target_keyword` を REST 経由で読み書きする。
 *
 * `yd_supervisors`（ACF post_object multiple）の REST 公開は
 * `register_post_meta` だと ACF が保存するシリアライズ配列との型整合で
 * 抜けることがあるため、`inc/class-rest-fields.php` の
 * register_rest_field 経由でトップレベル `yd_supervisors` として扱う。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Post_Meta {

	const META_TARGET_KEYWORD = 'yd_target_keyword';

	/**
	 * メタを登録する。init フックから呼ぶ。
	 */
	public static function register() {
		register_post_meta(
			'post',
			self::META_TARGET_KEYWORD,
			[
				'type'              => 'string',
				'description'       => '主ターゲットキーワード（SEO 内部管理用）',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}
}
