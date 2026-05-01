<?php
/**
 * register_post_meta() でカスタムメタを公開する。
 *
 * 設計書 §4 を参照。`post` の `yd_target_keyword` を REST 経由で
 * 読み書き可能にする（社内SEO管理用、構造化データには出さない）。
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
