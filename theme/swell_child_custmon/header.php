<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> <?php SWELL_Theme::root_attrs(); ?>>
<head>
<meta charset="utf-8">
<meta name="format-detection" content="telephone=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, viewport-fit=cover">
<?php
	wp_head();
	$SETTING = SWELL_Theme::get_setting();
?>
</head>
<body>
<?php if ( function_exists( 'wp_body_open' ) ) wp_body_open(); ?>
<div id="body_wrap" <?php body_class(); ?> <?php SWELL_Theme::body_attrs(); ?>>

<!-- ★ SWELLのSPメニューを維持（JSの toggleMenu が依存） -->
<?php
	$cache_key = ! empty( $SETTING['cache_spmenu'] ) ? 'spmenu' : '';
	SWELL_Theme::get_parts( 'parts/header/sp_menu', null, $cache_key );
?>

<!-- ★ 親サイト準拠カスタムヘッダー -->
<header id="header" class="l-header yd-header">
  <div class="yd-header__inner">
    <a href="https://your-doctor.jp/" class="yd-header__logo">
      <img src="https://your-doctor.jp/wp-content/themes/your-doctor-jp/images/share/logo.svg"
           alt="Your Doctor|医療総合情報サイト" class="yd-header__logo-img">
    </a>

    <nav class="yd-header__nav">
      <ul class="yd-header__menu">
        <li><a href="https://your-doctor.jp/concept/">コンセプト</a></li>
        <li><a href="https://your-doctor.jp/guidebook/">医療ガイドブック</a></li>
        <li><a href="https://your-doctor.jp/medical-column/">医療コラム</a></li>
        <li><a href="https://your-doctor.jp/interview/">ドクターの素顔</a></li>
        <li class="yd-has-dropdown">
          <span>病院・医師を探す</span>
          <ul class="yd-dropdown">
            <li><a href="https://your-doctor.jp/search-clinics/">医院・クリニックを探す</a></li>
            <li><a href="https://your-doctor.jp/search-doctors/">医師を探す</a></li>
          </ul>
        </li>
        <li class="yd-has-dropdown">
          <span>病気を調べる</span>
          <ul class="yd-dropdown">
            <li><a href="https://your-doctor.jp/search-body/">身体の部位から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-symptoms/">症状から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-disease/">病名から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-disease-type/">病気の種類で調べる</a></li>
          </ul>
        </li>
      </ul>
    </nav>

    <!-- SPボタン群（親サイト準拠） -->
    <div class="yd-header__sp-btns">
      <button class="yd-sp-btn yd-sp-btn--search" data-onclick="toggleSearch" aria-label="検索">
        <span class="yd-sp-btn__text">SEARCH</span>
        <svg class="yd-sp-btn__icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </button>
      <button class="yd-sp-btn yd-sp-btn--menu" data-onclick="toggleMenu" aria-label="メニューを開く">
        <span class="yd-sp-btn__text">MENU</span>
        <svg class="yd-sp-btn__icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </div>
</header>

<?php
	// Barba用 wrapper
	if ( SWELL_Theme::is_use( 'pjax' ) ) {
		echo '<div data-barba="container" data-barba-namespace="home">';
	}

	// タイトル(コンテンツ上)
	if ( SWELL_Theme::is_show_ttltop() ) SWELL_Theme::get_parts( 'parts/top_title_area' );

	// ぱんくず
	if ( 'top' === $SETTING['pos_breadcrumb'] ) SWELL_Theme::get_parts( 'parts/breadcrumb' );
?>
<div id="content" class="l-content l-container" <?php SWELL_Theme::content_attrs(); ?>>
<?php
	// ピックアップバナー
	if ( SWELL_Theme::is_show_pickup_banner() ) {
		$cache_key = ! empty( $SETTING['cache_top'] ) ? 'pickup_banner' : '';
		SWELL_Theme::get_parts( 'parts/top/pickup_banner', null, $cache_key );
	}
