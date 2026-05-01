<?php
/**
 * ACF フィールドグループ定義。
 *
 * acf/init フックから呼び出すため、ACF Pro が無効な場合はノーオペとする。
 * 設計書 3.2.1 / 3.2.2 / 3.2.3 を参照。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_ACF_Fields {

	/**
	 * フィールドグループを登録する。acf/init フックから呼ぶ。
	 */
	public static function register() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		self::register_doctor_profile_group();
		self::register_post_supervisor_group();
		self::register_post_seo_group();
	}

	/**
	 * 監修医師プロフィール（yd_doctor CPT 用）。
	 */
	private static function register_doctor_profile_group() {
		acf_add_local_field_group( [
			'key'                   => 'group_yd_doctor_profile',
			'title'                 => __( '監修医師プロフィール', 'yd-supervisor' ),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
			'location'              => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => YD_CPT_Doctor::POST_TYPE,
					],
				],
			],
			'fields'                => [
				[
					'key'           => 'field_yd_honorific_prefix',
					'label'         => __( '敬称', 'yd-supervisor' ),
					'name'          => 'yd_honorific_prefix',
					'type'          => 'text',
					'instructions'  => __( '構造化データ honorificPrefix に出力されます。', 'yd-supervisor' ),
					'required'      => 1,
					'default_value' => 'Dr.',
				],
				[
					'key'          => 'field_yd_job_title',
					'label'        => __( '役職・専門医資格', 'yd-supervisor' ),
					'name'         => 'yd_job_title',
					'type'         => 'text',
					'instructions' => __( '例: 泌尿器科専門医。構造化データ jobTitle に出力されます。', 'yd-supervisor' ),
					'required'     => 1,
				],
				[
					'key'           => 'field_yd_medical_specialty',
					'label'         => __( '専門分野', 'yd-supervisor' ),
					'name'          => 'yd_medical_specialty',
					'type'          => 'select',
					'instructions'  => __( 'schema.org MedicalSpecialty に基づく分類。出力時は https://schema.org/{value} に展開されます。', 'yd-supervisor' ),
					'required'      => 1,
					'choices'       => self::medical_specialty_choices(),
					'default_value' => '',
					'allow_null'    => 1,
					'multiple'      => 0,
					'ui'            => 1,
					'return_format' => 'value',
				],
				[
					'key'      => 'field_yd_clinic_name',
					'label'    => __( '所属クリニック名', 'yd-supervisor' ),
					'name'     => 'yd_clinic_name',
					'type'     => 'text',
					'required' => 1,
				],
				[
					'key'      => 'field_yd_clinic_url',
					'label'    => __( '所属クリニック公式URL', 'yd-supervisor' ),
					'name'     => 'yd_clinic_url',
					'type'     => 'url',
					'required' => 1,
				],
				[
					'key'   => 'field_yd_alumni_of',
					'label' => __( '出身大学・大学院', 'yd-supervisor' ),
					'name'  => 'yd_alumni_of',
					'type'  => 'text',
				],
				[
					'key'          => 'field_yd_same_as_urls',
					'label'        => __( '公式URL（学会・クリニック紹介ページ等）', 'yd-supervisor' ),
					'name'         => 'yd_same_as_urls',
					'type'         => 'repeater',
					'instructions' => __( '構造化データ sameAs[] に出力されます。', 'yd-supervisor' ),
					'min'          => 0,
					'max'          => 0,
					'layout'       => 'table',
					'button_label' => __( 'URLを追加', 'yd-supervisor' ),
					'sub_fields'   => [
						[
							'key'   => 'field_yd_same_as_url_item',
							'label' => __( 'URL', 'yd-supervisor' ),
							'name'  => 'url',
							'type'  => 'url',
						],
					],
				],
				[
					'key'          => 'field_yd_career',
					'label'        => __( '経歴', 'yd-supervisor' ),
					'name'         => 'yd_career',
					'type'         => 'wysiwyg',
					'tabs'         => 'all',
					'toolbar'      => 'basic',
					'media_upload' => 0,
				],
				[
					'key'          => 'field_yd_qualifications',
					'label'        => __( '保有資格', 'yd-supervisor' ),
					'name'         => 'yd_qualifications',
					'type'         => 'textarea',
					'instructions' => __( '改行区切りで入力。', 'yd-supervisor' ),
					'rows'         => 4,
					'new_lines'    => '',
				],
				[
					'key'   => 'field_yd_aga_experience_years',
					'label' => __( 'AGA治療経験年数', 'yd-supervisor' ),
					'name'  => 'yd_aga_experience_years',
					'type'  => 'number',
					'min'   => 0,
					'step'  => 1,
				],
				[
					'key'   => 'field_yd_supervisor_comment',
					'label' => __( '監修者コメント', 'yd-supervisor' ),
					'name'  => 'yd_supervisor_comment',
					'type'  => 'textarea',
					'rows'  => 4,
				],
				[
					'key'   => 'field_yd_doctor_license_year',
					'label' => __( '医師免許取得年', 'yd-supervisor' ),
					'name'  => 'yd_doctor_license_year',
					'type'  => 'number',
					'min'   => 1900,
					'max'   => 2100,
					'step'  => 1,
				],
			],
		] );
	}

	/**
	 * 記事への監修者紐付け（post 投稿タイプ用）。設計書 3.2.3。
	 */
	private static function register_post_supervisor_group() {
		acf_add_local_field_group( [
			'key'                   => 'group_yd_post_supervisor',
			'title'                 => __( '監修医師', 'yd-supervisor' ),
			'menu_order'            => 0,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
			'location'              => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
			'fields'                => [
				[
					'key'           => 'field_yd_supervisors',
					'label'         => __( '監修医師', 'yd-supervisor' ),
					'name'          => 'yd_supervisors',
					'type'          => 'post_object',
					'instructions'  => __( 'この記事を監修した医師を選択（複数可）。構造化データ reviewedBy に出力されます。', 'yd-supervisor' ),
					'required'      => 0,
					'post_type'     => [ YD_CPT_Doctor::POST_TYPE ],
					'taxonomy'      => [],
					'multiple'      => 1,
					'allow_null'    => 1,
					'return_format' => 'id',
					'ui'            => 1,
				],
				[
					'key'          => 'field_yd_disable_medical_schema',
					'label'        => __( '医療スキーマを無効化', 'yd-supervisor' ),
					'name'         => 'yd_disable_medical_schema',
					'type'         => 'true_false',
					'instructions' => __( 'ON にするとこの記事は MedicalWebPage 化せず、AIOSEO 標準の Article のままになります（医療外コンテンツ用）。', 'yd-supervisor' ),
					'required'     => 0,
					'message'      => __( 'MedicalWebPage 出力を抑制する', 'yd-supervisor' ),
					'default_value' => 0,
					'ui'           => 1,
				],
			],
		] );
	}

	/**
	 * SEO ターゲット情報（post 投稿タイプ用、サイドバー）。設計書 §4.2。
	 *
	 * フィールド名は `register_post_meta` のキーと一致させ、ACF / REST API
	 * どちら経由でも同じデータを読み書きできるようにする。
	 */
	private static function register_post_seo_group() {
		acf_add_local_field_group( [
			'key'                   => 'group_yd_post_seo',
			'title'                 => __( 'SEOターゲット情報', 'yd-supervisor' ),
			'menu_order'            => 1,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
			'location'              => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					],
				],
			],
			'fields'                => [
				[
					'key'          => 'field_yd_target_keyword',
					'label'        => __( '主ターゲットKW', 'yd-supervisor' ),
					'name'         => YD_Post_Meta::META_TARGET_KEYWORD,
					'type'         => 'text',
					'instructions' => __( 'この記事の主ターゲットキーワード（社内 SEO 管理用）。構造化データには出力されません。', 'yd-supervisor' ),
					'required'     => 0,
					'maxlength'    => 200,
				],
			],
		] );
	}

	/**
	 * MedicalSpecialty 選択肢（設計書 3.2.2）。
	 *
	 * 値（key）は schema.org enum 値。出力時に "https://schema.org/{value}" を付与する想定。
	 *
	 * @return array<string, string>
	 */
	private static function medical_specialty_choices() {
		return [
			'Urologic'            => __( '泌尿器科', 'yd-supervisor' ),
			'Dermatologic'        => __( '皮膚科', 'yd-supervisor' ),
			'PlasticSurgery'      => __( '形成外科', 'yd-supervisor' ),
			'DermatologicSurgery' => __( '美容皮膚科', 'yd-supervisor' ),
			'InternalMedicine'    => __( '内科', 'yd-supervisor' ),
			'PrimaryCare'         => __( '一般診療', 'yd-supervisor' ),
		];
	}
}
