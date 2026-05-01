<?php
/**
 * 監修医師シングル `/media/doctor/{slug}/`
 *
 * SWELL 親テーマの header / footer 内に配置する。
 * 子テーマで上書きしたい場合は子テーマ側に同名ファイルを置く。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="main" class="l-main yd-doctor-single-wrap">
	<?php while ( have_posts() ) : the_post(); ?>
		<?php $doctor_id = (int) get_the_ID(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'yd-doctor-single' ); ?>>
			<?php
			require YD_SUPERVISOR_DIR . 'templates/partials/doctor-profile.php';
			require YD_SUPERVISOR_DIR . 'templates/partials/reviewed-posts.php';
			?>
		</article>
	<?php endwhile; ?>
</main>
<?php
get_footer();
