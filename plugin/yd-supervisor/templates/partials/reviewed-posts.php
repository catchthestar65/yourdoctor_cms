<?php
/**
 * 監修医師シングルページ「この医師が監修した記事」一覧。
 *
 * 期待される変数：
 *   $doctor_id (int)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doctor_id = isset( $doctor_id ) ? (int) $doctor_id : (int) get_the_ID();
if ( ! $doctor_id ) {
	return;
}

$paged    = max( 1, (int) get_query_var( 'paged' ) );
$reviewed = yd_get_doctor_reviewed_posts(
	$doctor_id,
	[
		'posts_per_page' => 12,
		'paged'          => $paged,
	]
);

if ( ! $reviewed->have_posts() ) {
	wp_reset_postdata();
	return;
}

$doctor_name = get_the_title( $doctor_id );
?>
<section class="yd-doctor-reviewed-posts">
	<h2 class="yd-doctor-reviewed-posts__title">
		<?php
		printf(
			/* translators: %s: doctor name */
			esc_html__( '%s 医師が監修した記事', 'yd-supervisor' ),
			esc_html( $doctor_name )
		);
		?>
	</h2>

	<ul class="yd-doctor-reviewed-posts__list">
		<?php while ( $reviewed->have_posts() ) : $reviewed->the_post(); ?>
			<li class="yd-doctor-reviewed-posts__item">
				<a class="yd-doctor-reviewed-posts__link" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="yd-doctor-reviewed-posts__thumb">
							<?php the_post_thumbnail( 'medium' ); ?>
						</div>
					<?php endif; ?>
					<h3 class="yd-doctor-reviewed-posts__post-title"><?php the_title(); ?></h3>
				</a>
			</li>
		<?php endwhile; ?>
	</ul>

	<?php
	$page_count = (int) $reviewed->max_num_pages;
	if ( $page_count > 1 ) :
		$big = 999999999;
		?>
		<nav class="yd-doctor-reviewed-posts__pagination">
			<?php
			echo paginate_links(
				[
					'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'    => '?paged=%#%',
					'current'   => $paged,
					'total'     => $page_count,
					'prev_text' => __( '前へ', 'yd-supervisor' ),
					'next_text' => __( '次へ', 'yd-supervisor' ),
				]
			);
			?>
		</nav>
	<?php endif; ?>
</section>
<?php
wp_reset_postdata();
