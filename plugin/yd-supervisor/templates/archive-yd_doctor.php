<?php
/**
 * 監修医師アーカイブ `/media/doctor/`
 *
 * SWELL 親テーマの header / footer 内に配置する。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="l-main yd-doctors-archive-wrap">
	<header class="yd-doctors-archive__header">
		<h1 class="yd-doctors-archive__title"><?php esc_html_e( '監修医師一覧', 'yd-supervisor' ); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="yd-doctors-archive__grid">
			<?php while ( have_posts() ) : the_post();
				$doctor_id = (int) get_the_ID();
				include YD_SUPERVISOR_DIR . 'templates/partials/doctor-card.php';
			endwhile; ?>
		</div>

		<nav class="yd-doctors-archive__pagination">
			<?php
			the_posts_pagination( [
				'mid_size'  => 2,
				'prev_text' => __( '前へ', 'yd-supervisor' ),
				'next_text' => __( '次へ', 'yd-supervisor' ),
			] );
			?>
		</nav>
	<?php else : ?>
		<p class="yd-doctors-archive__empty">
			<?php esc_html_e( '監修医師はまだ登録されていません。', 'yd-supervisor' ); ?>
		</p>
	<?php endif; ?>
</main>
<?php
get_footer();
