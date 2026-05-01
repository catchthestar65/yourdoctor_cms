<?php
/**
 * REST API への独自フィールド追加。
 *
 * `register_rest_field` を使い、post の REST レスポンスに
 * トップレベル `yd_supervisors` を追加する。`register_post_meta` 経由だと
 * ACF post_object が保存するシリアライズ配列との型整合で抜けるため、
 * 明示的にカスタム getter / setter を定義する。
 *
 * - GET: 監修医師（yd_doctor）の投稿ID 配列を返す。yd_doctor 以外は除外
 * - POST/PATCH: 整数の配列を受け取り、検証・正規化のうえ ACF 経由で保存
 * - 権限: 当該投稿の `edit_post` を必須化
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Rest_Fields {

	const FIELD = 'yd_supervisors';

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_action( 'rest_api_init', [ __CLASS__, 'register' ] );
	}

	/**
	 * REST フィールドを post 投稿タイプに登録する。
	 */
	public static function register() {
		register_rest_field(
			'post',
			self::FIELD,
			[
				'get_callback'    => [ __CLASS__, 'get_supervisors' ],
				'update_callback' => [ __CLASS__, 'update_supervisors' ],
				'schema'          => [
					'description' => __( '監修医師（yd_doctor）の投稿ID配列', 'yd-supervisor' ),
					'type'        => 'array',
					'items'       => [ 'type' => 'integer' ],
					'context'     => [ 'view', 'edit' ],
				],
			]
		);
	}

	/**
	 * GET: 保存済み yd_supervisors を整数配列で返す。
	 *
	 * @param array $post_array  REST が組み立てた post 配列。
	 * @return array<int,int>
	 */
	public static function get_supervisors( $post_array ) {
		$post_id = isset( $post_array['id'] ) ? (int) $post_array['id'] : 0;
		if ( ! $post_id ) {
			return [];
		}

		// ACF 経由なら format_value=false で生のID配列が返る
		if ( function_exists( 'get_field' ) ) {
			$raw = get_field( self::FIELD, $post_id, false );
		} else {
			$raw = get_post_meta( $post_id, self::FIELD, true );
		}

		if ( empty( $raw ) || ! is_array( $raw ) ) {
			return [];
		}

		$ids = [];
		foreach ( $raw as $v ) {
			$id = absint( $v );
			if ( ! $id ) {
				continue;
			}
			$doctor = get_post( $id );
			if ( ! $doctor || YD_CPT_Doctor::POST_TYPE !== $doctor->post_type ) {
				continue;
			}
			if ( ! in_array( $id, $ids, true ) ) {
				$ids[] = $id;
			}
		}
		return $ids;
	}

	/**
	 * POST/PATCH: 配列を検証して update_field で保存する。
	 *
	 * @param mixed   $value        リクエストボディから渡された値。
	 * @param WP_Post $post_object  対象 post。
	 * @return true|WP_Error
	 */
	public static function update_supervisors( $value, $post_object ) {
		if ( ! ( $post_object instanceof WP_Post ) ) {
			return new WP_Error(
				'rest_invalid_post',
				__( '対象の投稿が見つかりません。', 'yd-supervisor' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! current_user_can( 'edit_post', $post_object->ID ) ) {
			return new WP_Error(
				'rest_cannot_update',
				__( 'この投稿を編集する権限がありません。', 'yd-supervisor' ),
				[ 'status' => 403 ]
			);
		}

		// null や空文字は「クリア」として空配列扱い
		if ( null === $value || '' === $value ) {
			$value = [];
		}

		if ( ! is_array( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'yd_supervisors は配列で指定してください。', 'yd-supervisor' ),
				[ 'status' => 400 ]
			);
		}

		$cleaned = [];
		foreach ( $value as $v ) {
			$id = absint( $v );
			if ( ! $id ) {
				continue;
			}
			$doctor = get_post( $id );
			if ( ! $doctor || YD_CPT_Doctor::POST_TYPE !== $doctor->post_type ) {
				continue;
			}
			if ( ! in_array( $id, $cleaned, true ) ) {
				$cleaned[] = $id;
			}
		}

		// ACF が有効なら update_field（_yd_supervisors の field key 参照も維持）。
		// 無ければ素の update_post_meta にフォールバック。
		if ( function_exists( 'update_field' ) ) {
			update_field( self::FIELD, $cleaned, $post_object->ID );
		} else {
			update_post_meta( $post_object->ID, self::FIELD, $cleaned );
		}

		return true;
	}
}
