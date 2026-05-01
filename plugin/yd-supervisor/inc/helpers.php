<?php
/**
 * テンプレートタグ・ヘルパー関数。
 *
 * 設計書 3.4 を参照。Phase 2 では監修者リレーション取得関数のみ実装。
 * シングルテンプレート用ヘルパー（Phase 3）、構造化データ用（Phase 5）は順次追加。
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
