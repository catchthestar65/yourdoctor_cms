<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 子テーマのスタイルシート読み込み
 */
add_action( 'wp_enqueue_scripts', function() {
    // Google Fonts
    wp_enqueue_style(
        'yd-google-fonts',
        'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;600;700&family=Shippori+Mincho+B1:wght@700&display=swap',
        [],
        null
    );

    // 子テーマCSS
    wp_enqueue_style(
        'yd-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [],
        filemtime( get_stylesheet_directory() . '/style.css' )
    );
}, 99 );


/**
 * SVGアップロード許可
 */
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
    if ( substr( $filename, -4 ) === '.svg' ) {
        $data['ext']  = 'svg';
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 4 );


/**
 * CSS変数の強制上書き
 */
add_action( 'wp_head', function() {
    echo '<style>
      :root {
        --swl-h2-margin--x: 0px;
        --color_bg: #F5F4F0;
        --color_text: #61564F;
        --color_link: #61564F;
        --color_main: #61564F;
        --color_header_bg: #FFFFFF;
        --color_header_text: #61564F;
        --swl-font_family: "Noto Sans JP", 游ゴシック体, "Yu Gothic", YuGothic,
                           "Hiragino Kaku Gothic Pro", "ヒラギノ角ゴ Pro W3",
                           メイリオ, Meiryo, sans-serif;
      }
    </style>' . "\n";
}, 999 );
