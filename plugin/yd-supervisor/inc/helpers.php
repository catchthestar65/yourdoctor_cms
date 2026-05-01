<?php
/**
 * テンプレートタグ・ヘルパー関数。
 *
 * 設計書 3.4 / §5 を参照。
 * Phase 2: yd_get_post_supervisors / yd_should_disable_medical_schema
 * Phase 3: yd_get_doctor_reviewed_posts
 * Phase 5: yd_get_doctor_person_schema / yd_get_reviewed_by_schema
 *          / yd_get_doctor_physician_schema
 * 記事側カード描画（Phase 6）は順次追加。
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

/**
 * 1人の監修医師から Person 型の構造化データ配列を組み立てる。
 *
 * `reviewedBy` 用と Physician 構築の共通基盤。設計書 5.2.1 を参照。
 *
 * @param int $doctor_id
 * @return array Person 型 schema 連想配列。データ不足時は空配列。
 */
function yd_get_doctor_person_schema( $doctor_id ) {
	$doctor_id = (int) $doctor_id;
	$doctor    = get_post( $doctor_id );
	if ( ! $doctor || YD_CPT_Doctor::POST_TYPE !== $doctor->post_type ) {
		return [];
	}

	$get = function ( $name ) use ( $doctor_id ) {
		if ( function_exists( 'get_field' ) ) {
			return get_field( $name, $doctor_id );
		}
		return get_post_meta( $doctor_id, $name, true );
	};

	$schema = [
		'@type' => 'Person',
		'name'  => get_the_title( $doctor_id ),
		'url'   => get_permalink( $doctor_id ),
	];

	$honorific = (string) $get( 'yd_honorific_prefix' );
	if ( $honorific ) {
		$schema['honorificPrefix'] = $honorific;
	}

	$job_title = (string) $get( 'yd_job_title' );
	if ( $job_title ) {
		$schema['jobTitle'] = $job_title;
	}

	$specialty_key = (string) $get( 'yd_medical_specialty' );
	if ( $specialty_key ) {
		$schema['medicalSpecialty'] = 'https://schema.org/' . $specialty_key;
	}

	$clinic_name = (string) $get( 'yd_clinic_name' );
	$clinic_url  = (string) $get( 'yd_clinic_url' );
	if ( $clinic_name ) {
		$member_of = [
			'@type' => 'MedicalOrganization',
			'name'  => $clinic_name,
		];
		if ( $clinic_url ) {
			$member_of['url'] = $clinic_url;
		}
		$schema['memberOf'] = $member_of;
	}

	$alumni = (string) $get( 'yd_alumni_of' );
	if ( $alumni ) {
		$schema['alumniOf'] = $alumni;
	}

	$same_as = $get( 'yd_same_as_urls' );
	if ( is_array( $same_as ) ) {
		$urls = [];
		foreach ( $same_as as $row ) {
			if ( is_array( $row ) && isset( $row['url'] ) ) {
				$url = (string) $row['url'];
			} elseif ( is_string( $row ) ) {
				$url = $row;
			} else {
				continue;
			}
			if ( $url ) {
				$urls[] = $url;
			}
		}
		if ( ! empty( $urls ) ) {
			$schema['sameAs'] = $urls;
		}
	}

	$image = get_the_post_thumbnail_url( $doctor_id, 'medium_large' );
	if ( $image ) {
		$schema['image'] = $image;
	}

	return $schema;
}

/**
 * 記事に紐付いた監修医師を schema.org reviewedBy 用に整形する。
 *
 * 1 人の場合は単一オブジェクト、複数の場合は配列を返す（schema.org 仕様）。
 *
 * @param int|null $post_id
 * @return array|array<int,array> 空配列なら出力対象なし。
 */
function yd_get_reviewed_by_schema( $post_id = null ) {
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return [];
	}

	$supervisors = yd_get_post_supervisors( $post_id );
	if ( empty( $supervisors ) ) {
		return [];
	}

	$persons = [];
	foreach ( $supervisors as $doctor ) {
		$person = yd_get_doctor_person_schema( $doctor->ID );
		if ( ! empty( $person ) ) {
			$persons[] = $person;
		}
	}

	if ( empty( $persons ) ) {
		return [];
	}
	if ( 1 === count( $persons ) ) {
		return $persons[0];
	}
	return $persons;
}

/**
 * 監修医師シングルページに出力する Physician 型構造化データ。
 *
 * Person 型データの @type を Physician に書き換え、@id を付与する。
 * 設計書 5.3 を参照。
 *
 * @param int|null $doctor_id
 * @return array Physician schema 連想配列。データ不足時は空配列。
 */
function yd_get_doctor_physician_schema( $doctor_id = null ) {
	$doctor_id = $doctor_id ? (int) $doctor_id : (int) get_the_ID();
	$person    = yd_get_doctor_person_schema( $doctor_id );
	if ( empty( $person ) ) {
		return [];
	}

	$person['@type'] = 'Physician';
	$person['@id']   = get_permalink( $doctor_id ) . '#physician';
	return $person;
}
