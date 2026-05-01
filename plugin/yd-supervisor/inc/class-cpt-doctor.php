<?php
/**
 * Custom Post Type: yd_doctor (監修医師)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class YD_CPT_Doctor {

	const POST_TYPE = 'yd_doctor';

	/**
	 * CPT を登録する。init フックから呼ぶ。
	 */
	public static function register() {
		$labels = [
			'name'               => _x( '監修医師', 'post type general name', 'yd-supervisor' ),
			'singular_name'      => _x( '監修医師', 'post type singular name', 'yd-supervisor' ),
			'menu_name'          => _x( '監修医師', 'admin menu', 'yd-supervisor' ),
			'name_admin_bar'     => _x( '監修医師', 'add new on admin bar', 'yd-supervisor' ),
			'add_new'            => __( '新規医師を追加', 'yd-supervisor' ),
			'add_new_item'       => __( '新規監修医師を追加', 'yd-supervisor' ),
			'new_item'           => __( '新規監修医師', 'yd-supervisor' ),
			'edit_item'          => __( '監修医師を編集', 'yd-supervisor' ),
			'view_item'          => __( '監修医師を表示', 'yd-supervisor' ),
			'view_items'         => __( '監修医師を表示', 'yd-supervisor' ),
			'all_items'          => __( '監修医師一覧', 'yd-supervisor' ),
			'search_items'       => __( '監修医師を検索', 'yd-supervisor' ),
			'not_found'          => __( '監修医師が見つかりません', 'yd-supervisor' ),
			'not_found_in_trash' => __( 'ゴミ箱に監修医師はありません', 'yd-supervisor' ),
			'archives'           => __( '監修医師アーカイブ', 'yd-supervisor' ),
			'attributes'         => __( '監修医師の属性', 'yd-supervisor' ),
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'  => true,
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'doctors',
			'menu_position'      => 5,
			'menu_icon'          => 'dashicons-businessperson',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'supports'           => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			'rewrite'            => [
				'slug'       => 'doctor',
				'with_front' => false,
				'feeds'      => true,
				'pages'      => true,
			],
		];

		register_post_type( self::POST_TYPE, $args );
	}
}
