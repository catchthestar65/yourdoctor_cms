<?php
/**
 * テンプレートタグ・ヘルパー関数。
 *
 * 設計書 3.4 を参照。
 * Phase 2: yd_get_post_supervisors / yd_should_disable_medical_schema
 * Phase 3: yd_get_doctor_reviewed_posts
 * 構造化データ用（Phase 5）、記事側カード描画（Phase 6）は順次追加。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 記事に紐付いた監修医師（yd_doctor）の WP_Post 配列を返す。
 *
 * ACF post_object（multiple, return_format=id）が `yd_supervisors` メタに保存した
 * ID 配列を取り出し、yd_doctor 投稿のみフィルタした上で WP_Post で返す。
 *
 * @param int|null $post_id 対象記事ID。null の場合はループ内の現在記事。
 * @return WP_Post[] 監修医師の投稿オブジェクト配列。紐付け無し時は空配列。
 */
function yd_get_post_supervisors( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return [];
	}

	$ids = get_post_meta( $post_id, 'yd_supervisors', true );
	if ( empty( $ids ) || ! is_array( $ids ) ) {
		return [];
	}

	$supervisors = [];
	foreach ( $ids as $id ) {
		$doctor = get_post( (int) $id );
		if ( $doctor instanceof WP_Post && YD_CPT_Doctor::POST_TYPE === $doctor->post_type ) {
			$supervisors[] = $doctor;
		}
	}

	return $supervisors;
}

/**
 * 監修医師が監修した記事一覧を取得する。
 *
 * `yd_supervisors`（ACF post_object multiple）に対象 doctor_id が含まれる
 * post を検索する。ACF はシリアライズ配列で保存しており、保存値の型
 * （int / string）に揺れがあるため LIKE クエリ 2 種を OR で発行する。
 *
 * 設計書 3.3.3 を参照。
 *
 * @param int   $doctor_id
 * @param array $args 追加 / 上書き WP_Query 引数。
 * @return WP_Query
 */
function yd_get_doctor_reviewed_posts( $doctor_id, $args = [] ) {
	$doctor_id = (int) $doctor_id;
	if ( ! $doctor_id ) {
		return new WP_Query();
	}

	$defaults = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'meta_query'     => [
			'relation' => 'OR',
			[
				'key'     => 'yd_supervisors',
				'value'   => '"' . $doctor_id . '"',
				'compare' => 'LIKE',
			],
			[
				'key'     => 'yd_supervisors',
				'value'   => 'i:' . $doctor_id . ';',
				'compare' => 'LIKE',
			],
		],
	];

	$args = wp_parse_args( $args, $defaults );
	return new WP_Query( $args );
}

/**
 * 記事の医療スキーマ出力を抑制すべきかを判定する。
 *
 * 以下のいずれかを満たす場合に true を返す（= MedicalWebPage 化しない）：
 * 1. ACF フィールド `yd_disable_medical_schema` が真
 * 2. 監修者が 1 人もアサインされていない
 *
 * Phase 5 の AIOSEO フィルター介入で利用する。
 *
 * @param int|null $post_id 対象記事ID。null の場合はループ内の現在記事。
 * @return bool true なら標準の Article のまま、false なら MedicalWebPage 化対象。
 */
function yd_should_disable_medical_schema( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return true;
	}

	if ( (bool) get_post_meta( $post_id, 'yd_disable_medical_schema', true ) ) {
		return true;
	}

	if ( empty( yd_get_post_supervisors( $post_id ) ) ) {
		return true;
	}

	return false;
}
