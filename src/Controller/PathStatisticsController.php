<?php
/**
 * Path statistics controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\PathPostType;
use QuestUno\Service\ExportService;
use QuestUno\Service\PathService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the single-Path aggregate statistics screen.
 */
final class PathStatisticsController {

	/** @var ExportService */
	private $export_service;

	/** @var PathService */
	private $path_service;

	/**
	 * Creates a Path statistics controller.
	 *
	 * @param ExportService $export_service Export service.
	 * @param PathService   $path_service   Path service.
	 */
	public function __construct( ExportService $export_service, PathService $path_service ) {
		$this->export_service = $export_service;
		$this->path_service   = $path_service;
	}

	/**
	 * Registers the hidden single-Path statistics page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			null,
			__( 'Path Statistics', 'questuno' ),
			__( 'Path Statistics', 'questuno' ),
			'edit_posts',
			'questuno-path-statistics',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders aggregate statistics for the requested Path.
	 *
	 * @return void
	 */
	public function render_page(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only Path identifier used only to render aggregate statistics; this request does not change data.
		$path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$path    = 0 === $path_id ? null : $this->path_service->get_path( $path_id );

		if ( null === $path || ! current_user_can( 'edit_post', (int) $path->get_post_id() ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}
		?>
		<div class="wrap">
			<?php /* translators: %s: Path name. */ ?>
			<h1><?php echo esc_html( sprintf( __( 'Path Statistics: %s', 'questuno' ), $path->get_name() ) ); ?></h1>
			<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . PathPostType::POST_TYPE ) ); ?>"><?php esc_html_e( 'Back to Paths', 'questuno' ); ?></a></p>
			<?php $statistics = $this->export_service->get_path_statistics( $path_id ); ?>
			<table class="widefat striped">
				<thead><tr><th scope="col"><?php esc_html_e( 'Metric', 'questuno' ); ?></th><th scope="col"><?php esc_html_e( 'Count', 'questuno' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $this->get_metric_labels() as $key => $label ) : ?>
						<tr><td><?php echo esc_html( $label ); ?></td><td><?php echo esc_html( (string) ( $statistics[ $key ] ?? 0 ) ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Gets display labels for Path aggregate metrics.
	 *
	 * @return array<string,string>
	 */
	private function get_metric_labels(): array {
		return array(
			'participations_total'       => __( 'Participations', 'questuno' ),
			'participations_in_progress' => __( 'Participations in progress', 'questuno' ),
			'participations_finished'    => __( 'Participations finished', 'questuno' ),
			'participations_completed'   => __( 'Participations completed', 'questuno' ),
			'participations_cancelled'   => __( 'Participations cancelled', 'questuno' ),
			'events_total'               => __( 'Total scans', 'questuno' ),
			'events_accepted'            => __( 'Accepted scans', 'questuno' ),
			'events_duplicate'           => __( 'Duplicate scans', 'questuno' ),
			'events_invalid'             => __( 'Invalid scans', 'questuno' ),
		);
	}
}
