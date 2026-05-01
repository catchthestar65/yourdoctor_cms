<?php
/**
 * register_post_meta() でカスタムメタを REST API に公開する。
 *
 * - 設計書 §4: `post` の `yd_target_keyword` を REST 経由で読み書き
 * - 設計書 3.5.3: `post` の `yd_supervisors`（監修者IDの配列）を
 *   REST 経由で読み書き可能にし、外部システム（前中氏）から
 *   記事入稿時に監修者をアサインできるようにする。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Post_Meta {

	const META_TARGET_KEYWORD = 'yd_target_keyword';
	const META_SUPERVISORS    = 'yd_supervisors';

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

		register_post_meta(
			'post',
			self::META_SUPERVISORS,
			[
				'type'              => 'array',
				'description'       => '監修医師（yd_doctor）の投稿ID配列',
				'single'            => true,
				'default'           => [],
				'show_in_rest'      => [
					'schema' => [
						'type'  => 'array',
						'items' => [ 'type' => 'integer' ],
					],
				],
				'sanitize_callback' => [ __CLASS__, 'sanitize_supervisor_ids' ],
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}

	/**
	 * yd_supervisors メタの整数化と存在チェック。
	 *
	 * @param mixed $value
	 * @return array<int,int>
	 */
	public static function sanitize_supervisor_ids( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}
		$ids = [];
		foreach ( $value as $v ) {
			$id = absint( $v );
			if ( ! $id ) {
				continue;
			}
			$post = get_post( $id );
			if ( ! $post || YD_CPT_Doctor::POST_TYPE !== $post->post_type ) {
				continue;
			}
			$ids[] = $id;
		}
		return array_values( array_unique( $ids ) );
	}
}
