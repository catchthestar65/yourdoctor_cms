<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// サイドバー表示判定（SWELLの元の処理）
if ( SWELL_Theme::is_show_sidebar() ) {
	get_sidebar();
}
?>
</div><!-- /#content.l-content -->
<?php
$SETTING = SWELL_Theme::get_setting();

// Barba.js wrapper 閉じ（SWELLの元の処理）
if ( SWELL_Theme::is_use( 'pjax' ) ) echo '</div>';

// フッター前ウィジェット（SWELLの元の処理 ※元ファイルにあったが欠落していた）
if ( is_active_sidebar( 'before_footer' ) ) :
	echo '<div id="before_footer_widget" class="w-beforeFooter">';
	if ( ! SWELL_Theme::is_use( 'ajax_footer' ) ) :
		SWELL_Theme::get_parts( 'parts/footer/before_footer' );
	endif;
	echo '</div>';
endif;

// パンくず（SWELLの元の処理）
if ( 'top' !== $SETTING['pos_breadcrumb'] ) :
	SWELL_Theme::get_parts( 'parts/breadcrumb' );
endif;
?>

<!-- ★ Your Doctor カスタムフッター -->
<footer id="footer" class="l-footer yd-footer">
  <div class="yd-footer__inner">
    <div class="yd-footer__top">
      <div class="yd-footer__logo-area">
        <a href="https://your-doctor.jp/">
          <img src="https://your-doctor.jp/wp-content/themes/your-doctor-jp/images/share/logo_w.svg"
               alt="Your Doctor" class="yd-footer__logo-img">
        </a>
        <p class="yd-footer__desc">
          『Your Doctor - ユア・ドクター - 』は、最新医療や医療情報などを発信する、「健康的で自分らしく生きたい」人のための医療情報メディアです。<br><br>
          医療現場の最前線で活躍する医師たちからの情報や、オリジナル特集、医療求人など、活気ある日本社会を後押しできる情報をお届けします。
        </p>
      </div>
      <div class="yd-footer__links">
        <div class="yd-footer__link-col">
          <h3 class="yd-footer__link-heading">Articles<span>コラム記事</span></h3>
          <ul>
            <li><a href="https://your-doctor.jp/guidebook/">医療ガイドブック</a></li>
            <li><a href="https://your-doctor.jp/medical-column/">医療コラム</a></li>
            <li><a href="https://your-doctor.jp/trends/">ランキング</a></li>
          </ul>
        </div>
        <div class="yd-footer__link-col">
          <h3 class="yd-footer__link-heading">Original<span>オリジナル特集</span></h3>
          <ul>
            <li><a href="https://your-doctor.jp/interview/">ドクターの素顔</a></li>
          </ul>
        </div>
        <div class="yd-footer__link-col">
          <h3 class="yd-footer__link-heading">Clinics<span>病院・医師を探す</span></h3>
          <ul>
            <li><a href="https://your-doctor.jp/search-clinics/">病院・クリニックを探す</a></li>
            <li><a href="https://your-doctor.jp/search-doctors/">医師を探す</a></li>
          </ul>
        </div>
        <div class="yd-footer__link-col">
          <h3 class="yd-footer__link-heading">Search<span>病気を調べる</span></h3>
          <ul>
            <li><a href="https://your-doctor.jp/search-body/">身体の部位から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-symptoms/">症状から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-disease/">病名から調べる</a></li>
            <li><a href="https://your-doctor.jp/search-disease-type/">病気の種類で調べる</a></li>
          </ul>
        </div>
      </div>
    </div>
    <div class="yd-footer__bottom">
      <ul class="yd-footer__bottom-links">
        <li><a href="https://your-doctor.jp/contact/">お問い合わせ</a></li>
        <li><a href="https://your-doctor.jp/bigaku/">月刊美楽</a></li>
        <li><a href="https://your-doctor.jp/bifurakubi/">美風楽日</a></li>
        <li><a href="https://your-doctor.jp/company/">運営会社</a></li>
        <li><a href="https://your-doctor.jp/concept/">コンセプト</a></li>
        <li><a href="https://eucalia.jp/policy/privacy/">個人情報保護方針</a></li>
      </ul>
      <p class="yd-footer__copyright">&copy; 2025 Your Doctor</p>
    </div>
  </div>
</footer>

<?php
// SWELLの元の処理を維持（固定ボタン、モーダル等）
if ( has_nav_menu( 'fix_bottom_menu' ) ) :
	$cache_key = ! empty( $SETTING['cache_bottom_menu'] ) ? 'fix_bottom_menu' : '';
	SWELL_Theme::get_parts( 'parts/footer/fix_menu', null, $cache_key );
endif;

// 固定ボタン（トップへ戻る等）
SWELL_Theme::get_parts( 'parts/footer/fix_btns' );

// モーダル（検索、目次）
SWELL_Theme::get_parts( 'parts/footer/modals' );
?>
</div><!--/ #body_wrap -->
<?php
wp_footer();
if ( ! empty( $SETTING['foot_code'] ) ) {
	echo $SETTING['foot_code']; // phpcs:ignore
}
?>
</body></html>
