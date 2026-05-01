<?php
/**
 * 構造化データ（JSON-LD）の介入。
 *
 * 設計書 §5 を参照。AIOSEO の `aioseo_schema_output` フィルターで
 * グラフ配列を受け取り、以下を行う：
 *
 *   - 単一投稿（post）：監修者ありなら WebPage 系を MedicalWebPage に
 *     書き換え、reviewedBy / lastReviewed / medicalAudience / specialty
 *     を追加。`yd_disable_medical_schema` ON か監修者ゼロなら何もしない。
 *   - 監修医師シングル（yd_doctor）：Physician グラフを追加。
 *
 * フィルター仕様：AIOSEO 4.9.6.2 で `app/Common/Schema/Helpers.php` の
 * `apply_filters('aioseo_schema_output', $graphs)` を確認済み。各グラフは
 * 連想配列で、@type / @id / 各プロパティが入っている。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_Schema {

	/**
	 * 書き換え対象の WebPage 系 @type。
	 */
	const PAGE_TYPES = [ 'WebPage', 'CollectionPage', 'ItemPage' ];

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_filter( 'aioseo_schema_output', [ __CLASS__, 'modify_schema' ], 10, 1 );
	}

	/**
	 * `aioseo_schema_output` フィルターのエントリポイント。
	 *
	 * @param array $graphs グラフ配列。
	 * @return array
	 */
	public static function modify_schema( $graphs ) {
		if ( ! is_array( $graphs ) ) {
			return $graphs;
		}

		if ( is_singular( 'post' ) ) {
			return self::modify_post_schema( $graphs );
		}

		if ( is_singular( YD_CPT_Doctor::POST_TYPE ) ) {
			return self::add_physician_to_graphs( $graphs );
		}

		return $graphs;
	}

	/**
	 * 投稿（post）：WebPage を MedicalWebPage に書き換え、
	 * 医療向けプロパティを追加する。
	 *
	 * @param array $graphs
	 * @return array
	 */
	private static function modify_post_schema( $graphs ) {
		$post_id = (int) get_queried_object_id();
		if ( ! $post_id ) {
			return $graphs;
		}

		if ( yd_should_disable_medical_schema( $post_id ) ) {
			return $graphs;
		}

		$reviewed_by = yd_get_reviewed_by_schema( $post_id );
		if ( empty( $reviewed_by ) ) {
			return $graphs;
		}

		$supervisors          = yd_get_post_supervisors( $post_id );
		$first_specialty_uri  = ! empty( $supervisors ) ? self::specialty_uri( $supervisors[0]->ID ) : '';

		foreach ( $graphs as $i => $graph ) {
			if ( empty( $graph['@type'] ) ) {
				continue;
			}

			$types = (array) $graph['@type'];
			if ( ! array_intersect( $types, self::PAGE_TYPES ) ) {
				continue;
			}

			$graphs[ $i ]['@type']           = 'MedicalWebPage';
			$graphs[ $i ]['reviewedBy']      = $reviewed_by;
			$graphs[ $i ]['lastReviewed']    = get_the_modified_date( 'c', $post_id );
			$graphs[ $i ]['medicalAudience'] = [
				'@type'        => 'PeopleAudience',
				'audienceType' => 'Patient',
			];
			if ( $first_specialty_uri ) {
				$graphs[ $i ]['specialty'] = $first_specialty_uri;
			}
		}

		return $graphs;
	}

	/**
	 * 監修医師シングル：Physician グラフを末尾に追加する。
	 *
	 * @param array $graphs
	 * @return array
	 */
	private static function add_physician_to_graphs( $graphs ) {
		$doctor_id = (int) get_queried_object_id();
		$physician = yd_get_doctor_physician_schema( $doctor_id );
		if ( ! empty( $physician ) ) {
			$graphs[] = $physician;
		}
		return $graphs;
	}

	/**
	 * 医師の専門分野キー（"Urologic" 等）から schema.org URI を組み立てる。
	 *
	 * @param int $doctor_id
	 * @return string
	 */
	private static function specialty_uri( $doctor_id ) {
		$key = function_exists( 'get_field' )
			? (string) get_field( 'yd_medical_specialty', $doctor_id )
			: (string) get_post_meta( $doctor_id, 'yd_medical_specialty', true );

		if ( ! $key ) {
			return '';
		}
		return 'https://schema.org/' . $key;
	}
}
