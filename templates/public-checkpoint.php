<?php
/**
 * Public Checkpoint template.
 *
 * @package QuestUno
 */

defined( 'ABSPATH' ) || exit;

$questuno_context = get_query_var( 'questuno_public_ui_context', array() );
$questuno_context = is_array( $questuno_context ) ? $questuno_context : array();

$questuno_page_title         = isset( $questuno_context['page_title'] ) ? (string) $questuno_context['page_title'] : __( 'Checkpoint', 'questuno' );
$questuno_validation_outcome = isset( $questuno_context['validation_outcome'] ) ? (string) $questuno_context['validation_outcome'] : '';
$questuno_message            = isset( $questuno_context['message'] ) ? (string) $questuno_context['message'] : '';
$questuno_participation      = $questuno_context['participation'] ?? null;
$questuno_participation_status_label = isset( $questuno_context['participation_status_label'] ) ? (string) $questuno_context['participation_status_label'] : __( 'Not started', 'questuno' );
$questuno_violation_messages = isset( $questuno_context['violation_messages'] ) && is_array( $questuno_context['violation_messages'] ) ? $questuno_context['violation_messages'] : array();
$questuno_render_content     = ! empty( $questuno_context['render_content'] );
$questuno_banner_message     = isset( $questuno_context['banner_message'] ) ? (string) $questuno_context['banner_message'] : '';
$questuno_banner_modifier    = isset( $questuno_context['banner_modifier'] ) ? (string) $questuno_context['banner_modifier'] : '';
$questuno_path_name          = isset( $questuno_context['path_name'] ) ? (string) $questuno_context['path_name'] : '';
$questuno_progress_label     = isset( $questuno_context['progress_label'] ) ? (string) $questuno_context['progress_label'] : '';
$questuno_my_paths_url       = isset( $questuno_context['my_paths_url'] ) ? (string) $questuno_context['my_paths_url'] : '';
$questuno_render_navigation  = ! empty( $questuno_context['render_navigation'] );
$questuno_checkpoint_content = isset( $questuno_context['checkpoint_content'] ) ? (string) $questuno_context['checkpoint_content'] : '';
$questuno_is_path_in_progress = $questuno_participation instanceof \QuestUno\Model\Participation
	&& \QuestUno\Model\ParticipationStatus::IN_PROGRESS === $questuno_participation->get_status()
	&& '' !== $questuno_path_name;
$questuno_path_status_label = $questuno_is_path_in_progress
	? sprintf(
		/* translators: %s: Path name. */
		__( 'Path “%s” in progress', 'questuno' ),
		$questuno_path_name
	)
	: $questuno_participation_status_label;

get_header();
?>
<main id="primary" class="site-main questuno-public-checkpoint">
	<div class="questuno-public-checkpoint__content">
		<h1><?php echo esc_html( $questuno_page_title ); ?></h1>

		<?php if ( '' !== $questuno_banner_message || '' !== $questuno_progress_label || null !== $questuno_participation ) : ?>
			<section class="questuno-public-ui__status-card" aria-label="<?php esc_attr_e( 'Path progress', 'questuno' ); ?>">
				<?php if ( '' !== $questuno_banner_message ) : ?>
					<div class="questuno-public-ui__notice questuno-public-ui__notice--<?php echo esc_attr( $questuno_banner_modifier ); ?>">
						<p><?php echo esc_html( $questuno_banner_message ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $questuno_path_name && ! $questuno_is_path_in_progress ) : ?>
					<p class="questuno-public-ui__progress-title"><?php echo esc_html( $questuno_path_name ); ?></p>
				<?php endif; ?>

				<?php if ( '' !== $questuno_progress_label ) : ?>
					<p class="questuno-public-ui__progress-value"><?php echo esc_html( $questuno_progress_label ); ?></p>
				<?php endif; ?>

				<?php if ( null !== $questuno_participation ) : ?>
					<p class="questuno-public-ui__participation-status"><?php echo esc_html( $questuno_path_status_label ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<?php if ( '' === $questuno_banner_message && '' !== $questuno_validation_outcome ) : ?>
			<p class="questuno-public-ui__validation-outcome"><?php echo esc_html( $questuno_validation_outcome ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $questuno_message ) : ?>
			<p class="questuno-public-ui__message"><?php echo esc_html( $questuno_message ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $questuno_violation_messages ) ) : ?>
			<section class="questuno-public-checkpoint__violations">
				<h2><?php esc_html_e( 'Validation details', 'questuno' ); ?></h2>
				<ul>
					<?php foreach ( $questuno_violation_messages as $questuno_violation_message ) : ?>
						<li><?php echo esc_html( (string) $questuno_violation_message ); ?></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $questuno_render_content && '' !== $questuno_checkpoint_content ) : ?>
			<section class="questuno-public-checkpoint__body">
				<?php echo wp_kses_post( $questuno_checkpoint_content ); ?>
			</section>
		<?php endif; ?>

		<?php if ( $questuno_render_navigation && '' !== $questuno_my_paths_url ) : ?>
			<nav class="questuno-public-ui__navigation" aria-label="<?php esc_attr_e( 'Player navigation', 'questuno' ); ?>">
				<a class="questuno-public-ui__my-paths-link" href="<?php echo esc_url( $questuno_my_paths_url ); ?>">
					<?php esc_html_e( 'My paths', 'questuno' ); ?>
				</a>
			</nav>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
