<?php
/**
 * 監修医師プロフィール詳細（single-yd_doctor.php から include）。
 *
 * 期待される変数：
 *   $doctor_id (int) ... 表示対象の yd_doctor 投稿ID
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$doctor_id = isset( $doctor_id ) ? (int) $doctor_id : (int) get_the_ID();
$doctor    = get_post( $doctor_id );
if ( ! $doctor || YD_CPT_Doctor::POST_TYPE !== $doctor->post_type ) {
	return;
}

// ACF が無効でも落ちないように get_field をラップ
$get = function ( $name ) use ( $doctor_id ) {
	if ( function_exists( 'get_field' ) ) {
		return get_field( $name, $doctor_id );
	}
	return get_post_meta( $doctor_id, $name, true );
};

$honorific      = (string) $get( 'yd_honorific_prefix' );
$job_title      = (string) $get( 'yd_job_title' );
$clinic_name    = (string) $get( 'yd_clinic_name' );
$clinic_url     = (string) $get( 'yd_clinic_url' );
$alumni_of      = (string) $get( 'yd_alumni_of' );
$same_as_urls   = $get( 'yd_same_as_urls' );
$career         = (string) $get( 'yd_career' );
$qualifications = (string) $get( 'yd_qualifications' );
$exp_years      = $get( 'yd_aga_experience_years' );
$comment        = (string) $get( 'yd_supervisor_comment' );
$license_year   = $get( 'yd_doctor_license_year' );

$display_name = $doctor->post_title;
$image_url    = get_the_post_thumbnail_url( $doctor_id, 'medium_large' );
?>

<header class="yd-doctor-profile">
	<?php if ( $image_url ) : ?>
		<div class="yd-doctor-profile__photo">
			<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $display_name ); ?>" loading="lazy" />
		</div>
	<?php endif; ?>

	<div class="yd-doctor-profile__name-block">
		<h1 class="yd-doctor-profile__name">
			<?php if ( $honorific ) : ?>
				<span class="yd-doctor-profile__honorific"><?php echo esc_html( $honorific ); ?></span>
			<?php endif; ?>
			<span class="yd-doctor-profile__realname"><?php echo esc_html( $display_name ); ?></span>
		</h1>

		<?php if ( $job_title ) : ?>
			<p class="yd-doctor-profile__job"><?php echo esc_html( $job_title ); ?></p>
		<?php endif; ?>

		<?php if ( $clinic_name ) : ?>
			<p class="yd-doctor-profile__clinic">
				<?php if ( $clinic_url ) : ?>
					<a href="<?php echo esc_url( $clinic_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $clinic_name ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $clinic_name ); ?>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
</header>

<?php if ( has_excerpt( $doctor_id ) ) : ?>
	<div class="yd-doctor-profile__excerpt">
		<?php echo wp_kses_post( wpautop( get_the_excerpt( $doctor_id ) ) ); ?>
	</div>
<?php endif; ?>

<?php
$has_details = $career || $qualifications || $alumni_of || ( $exp_years !== '' && $exp_years !== null && $exp_years !== false ) || $license_year;
?>
<?php if ( $has_details ) : ?>
	<dl class="yd-doctor-profile__details">
		<?php if ( $career ) : ?>
			<dt><?php esc_html_e( '経歴', 'yd-supervisor' ); ?></dt>
			<dd class="yd-doctor-profile__details-rich"><?php echo wp_kses_post( $career ); ?></dd>
		<?php endif; ?>
		<?php if ( $qualifications ) : ?>
			<dt><?php esc_html_e( '保有資格', 'yd-supervisor' ); ?></dt>
			<dd><?php echo nl2br( esc_html( $qualifications ) ); ?></dd>
		<?php endif; ?>
		<?php if ( $alumni_of ) : ?>
			<dt><?php esc_html_e( '出身大学・大学院', 'yd-supervisor' ); ?></dt>
			<dd><?php echo esc_html( $alumni_of ); ?></dd>
		<?php endif; ?>
		<?php if ( $exp_years !== '' && $exp_years !== null && $exp_years !== false ) : ?>
			<dt><?php esc_html_e( 'AGA治療経験', 'yd-supervisor' ); ?></dt>
			<dd><?php printf( esc_html__( '%d年', 'yd-supervisor' ), (int) $exp_years ); ?></dd>
		<?php endif; ?>
		<?php if ( $license_year ) : ?>
			<dt><?php esc_html_e( '医師免許取得年', 'yd-supervisor' ); ?></dt>
			<dd><?php printf( esc_html__( '%d年', 'yd-supervisor' ), (int) $license_year ); ?></dd>
		<?php endif; ?>
	</dl>
<?php endif; ?>

<?php if ( $comment ) : ?>
	<section class="yd-doctor-profile__comment">
		<h2 class="yd-doctor-profile__comment-title"><?php esc_html_e( '監修者からのコメント', 'yd-supervisor' ); ?></h2>
		<p><?php echo nl2br( esc_html( $comment ) ); ?></p>
	</section>
<?php endif; ?>

<?php
$content = get_the_content( null, false, $doctor_id );
if ( trim( wp_strip_all_tags( $content ) ) !== '' ) :
?>
	<section class="yd-doctor-profile__content">
		<?php echo apply_filters( 'the_content', $content ); ?>
	</section>
<?php endif; ?>

<?php
if ( ! empty( $same_as_urls ) && is_array( $same_as_urls ) ) :
?>
	<section class="yd-doctor-profile__sameas">
		<h2 class="yd-doctor-profile__sameas-title"><?php esc_html_e( '関連リンク', 'yd-supervisor' ); ?></h2>
		<ul>
			<?php
			foreach ( $same_as_urls as $row ) {
				$url = '';
				if ( is_array( $row ) && isset( $row['url'] ) ) {
					$url = (string) $row['url'];
				} elseif ( is_string( $row ) ) {
					$url = $row;
				}
				if ( ! $url ) {
					continue;
				}
				printf(
					'<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
					esc_url( $url ),
					esc_html( $url )
				);
			}
			?>
		</ul>
	</section>
<?php endif; ?>
