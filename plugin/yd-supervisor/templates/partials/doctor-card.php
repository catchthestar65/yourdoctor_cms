<?php
/**
 * 監修医師カード（一覧用）
 *
 * 期待される変数：
 *   $doctor_id (int)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doctor_id = isset( $doctor_id ) ? (int) $doctor_id : (int) get_the_ID();
$doctor    = get_post( $doctor_id );
if ( ! $doctor ) {
	return;
}

$get = function ( $name ) use ( $doctor_id ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $name, $doctor_id );
	}
	return get_post_meta( $doctor_id, $name, true );
};

$honorific = (string) $get( 'yd_honorific_prefix' );
$job_title = (string) $get( 'yd_job_title' );
$clinic    = (string) $get( 'yd_clinic_name' );

$image_url = get_the_post_thumbnail_url( $doctor_id, 'medium' );
$permalink = get_permalink( $doctor_id );
$name      = $doctor->post_title;
?>
<a class="yd-doctor-card" href="<?php echo esc_url( $permalink ); ?>">
	<?php if ( $image_url ) : ?>
		<div class="yd-doctor-card__photo">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy" />
		</div>
	<?php else : ?>
		<div class="yd-doctor-card__photo yd-doctor-card__photo--placeholder" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="yd-doctor-card__body">
		<h2 class="yd-doctor-card__name">
			<?php if ( $honorific ) : ?>
				<span class="yd-doctor-card__honorific"><?php echo esc_html( $honorific ); ?></span>
			<?php endif; ?>
			<span class="yd-doctor-card__realname"><?php echo esc_html( $name ); ?></span>
		</h2>

		<?php if ( $job_title ) : ?>
			<p class="yd-doctor-card__job"><?php echo esc_html( $job_title ); ?></p>
		<?php endif; ?>

		<?php if ( $clinic ) : ?>
			<p class="yd-doctor-card__clinic"><?php echo esc_html( $clinic ); ?></p>
		<?php endif; ?>
	</div>
</a>
