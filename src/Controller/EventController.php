<?php
/**
 * Event controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\Model\EventResult;
use QuestUno\Model\EventType;
use QuestUno\Service\CheckpointService;
use QuestUno\Service\EventService;
use QuestUno\Service\PathService;
use QuestUno\Service\ParticipationService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the global Event administration listing.
 */
final class EventController {

	/** @var EventService */
	private $event_service;

	/** @var PathService */
	private $path_service;

	/** @var ParticipationService */
	private $participation_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/**
	 * Creates an Event controller.
	 *
	 * @param EventService         $event_service         Event service.
	 * @param PathService          $path_service          Path service.
	 * @param ParticipationService $participation_service Participation service.
	 * @param CheckpointService    $checkpoint_service    Checkpoint service.
	 */
	public function __construct( EventService $event_service, PathService $path_service, ParticipationService $participation_service, CheckpointService $checkpoint_service ) {
		$this->event_service         = $event_service;
		$this->path_service          = $path_service;
		$this->participation_service = $participation_service;
		$this->checkpoint_service    = $checkpoint_service;
	}

	/**
	 * Registers the Events admin page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'questuno',
			__( 'Events', 'questuno' ),
			__( 'Events', 'questuno' ),
			'manage_options',
			'questuno-events',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the global Event listing.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$paths       = $this->path_service->get_paths();
		$users       = get_users(
			array(
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		$checkpoints = $this->checkpoint_service->get_checkpoints();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters used only to render the Event listing; this request does not change data.
		$path_id       = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$user_id       = isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0;
		$checkpoint_id = isset( $_GET['checkpoint_id'] ) ? absint( wp_unslash( $_GET['checkpoint_id'] ) ) : 0;
		$result        = isset( $_GET['result'] ) ? sanitize_key( wp_unslash( $_GET['result'] ) ) : '';
		$date_from     = isset( $_GET['date_from'] ) ? $this->validate_date( sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) ) : '';
		$date_to       = isset( $_GET['date_to'] ) ? $this->validate_date( sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! array_key_exists( $result, $this->get_result_labels() ) ) {
			$result = '';
		}

		$events            = $this->event_service->get_events_by_filters( $path_id, $user_id, $checkpoint_id, $result, $date_from, $date_to );
		$path_names        = $this->get_path_names( $paths );
		$user_labels       = $this->get_user_labels( $users );
		$participations_by_id = $this->get_participations_by_id();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Events', 'questuno' ); ?></h1>

			<form method="get">
				<input type="hidden" name="page" value="questuno-events" />
				<label for="questuno-events-path"><?php esc_html_e( 'Path', 'questuno' ); ?></label>
				<select id="questuno-events-path" name="path_id">
					<option value="0"><?php esc_html_e( 'All Paths', 'questuno' ); ?></option>
					<?php foreach ( $paths as $path ) : ?>
						<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>><?php echo esc_html( $path->get_name() ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="questuno-events-user"><?php esc_html_e( 'User', 'questuno' ); ?></label>
				<select id="questuno-events-user" name="user_id">
					<option value="0"><?php esc_html_e( 'All Users', 'questuno' ); ?></option>
					<?php foreach ( $users as $user ) : ?>
						<option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $user_id, $user->ID ); ?>><?php echo esc_html( $user_labels[ $user->ID ] ?? (string) $user->ID ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="questuno-events-checkpoint"><?php esc_html_e( 'Checkpoint', 'questuno' ); ?></label>
				<select id="questuno-events-checkpoint" name="checkpoint_id">
					<option value="0"><?php esc_html_e( 'All Checkpoints', 'questuno' ); ?></option>
					<?php foreach ( $checkpoints as $checkpoint ) : ?>
						<?php $current_checkpoint_id = (int) $checkpoint->get_post_id(); ?>
						<option value="<?php echo esc_attr( (string) $current_checkpoint_id ); ?>" <?php selected( $checkpoint_id, $current_checkpoint_id ); ?>><?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( $current_checkpoint_id ) ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="questuno-events-result"><?php esc_html_e( 'Result', 'questuno' ); ?></label>
				<select id="questuno-events-result" name="result">
					<option value=""><?php esc_html_e( 'All Results', 'questuno' ); ?></option>
					<?php foreach ( $this->get_result_labels() as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $result, $value ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>

				<label for="questuno-events-date-from"><?php esc_html_e( 'From', 'questuno' ); ?></label>
				<input id="questuno-events-date-from" type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
				<label for="questuno-events-date-to"><?php esc_html_e( 'To', 'questuno' ); ?></label>
				<input id="questuno-events-date-to" type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
				<?php submit_button( __( 'Filter', 'questuno' ), 'secondary', '', false ); ?>
			</form>

			<table class="widefat striped">
				<thead><tr>
					<th scope="col"><?php esc_html_e( 'Date', 'questuno' ); ?></th>
					<th scope="col"><?php esc_html_e( 'User', 'questuno' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Path', 'questuno' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Checkpoint', 'questuno' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Type', 'questuno' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Result', 'questuno' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $events ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No Events recorded yet.', 'questuno' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $events as $event ) : ?>
							<?php $participation = $participations_by_id[ (int) $event->get_participation_id() ] ?? null; ?>
							<tr>
								<td><?php echo esc_html( $this->format_datetime( $event->get_created_at() ) ); ?></td>
								<td><?php echo esc_html( null === $participation ? '' : ( $user_labels[ $participation->get_user_id() ] ?? (string) $participation->get_user_id() ) ); ?></td>
								<td><?php echo esc_html( null === $participation ? '' : ( $path_names[ $participation->get_path_id() ] ?? '' ) ); ?></td>
								<td><?php echo esc_html( null === $event->get_checkpoint_id() ? '' : $this->checkpoint_service->get_checkpoint_title( (int) $event->get_checkpoint_id() ) ); ?></td>
								<td><?php echo esc_html( $this->get_type_labels()[ (string) $event->get_event_type() ] ?? (string) $event->get_event_type() ); ?></td>
								<td><?php echo esc_html( $this->get_result_labels()[ (string) $event->get_result() ] ?? (string) $event->get_result() ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/** @param array<int,\QuestUno\Model\Path> $paths @return array<int,string> */
	private function get_path_names( array $paths ): array {
		$names = array();
		foreach ( $paths as $path ) {
			$names[ (int) $path->get_id() ] = (string) $path->get_name();
		}
		return $names;
	}

	/** @param array<int,\WP_User> $users @return array<int,string> */
	private function get_user_labels( array $users ): array {
		$labels = array();
		foreach ( $users as $user ) {
			$labels[ $user->ID ] = $user->display_name . ' (' . $user->user_email . ')';
		}
		return $labels;
	}

	/** @return array<int,\QuestUno\Model\Participation> */
	private function get_participations_by_id(): array {
		$participations = array();
		foreach ( $this->participation_service->get_participations() as $participation ) {
			$participations[ (int) $participation->get_id() ] = $participation;
		}
		return $participations;
	}

	/** @return array<string,string> */
	private function get_result_labels(): array {
		return array(
			EventResult::ACCEPTED                => __( 'Accepted', 'questuno' ),
			EventResult::DUPLICATE               => __( 'Duplicate', 'questuno' ),
			EventResult::BEFORE_FAILED           => __( 'Before Failed', 'questuno' ),
			EventResult::AFTER_FAILED            => __( 'After Failed', 'questuno' ),
			EventResult::PATH_CLOSED             => __( 'Path Closed', 'questuno' ),
			EventResult::PARTICIPATION_CANCELLED => __( 'Participation Cancelled', 'questuno' ),
		);
	}

	/** @return array<string,string> */
	private function get_type_labels(): array {
		return array( EventType::QR_SCAN => __( 'QR Code scan', 'questuno' ) );
	}

	/** @param string $date Date. @return string */
	private function validate_date( string $date ): string {
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';
	}

	/** @param string|null $datetime Stored datetime. @return string */
	private function format_datetime( ?string $datetime ): string {
		if ( null === $datetime || '' === $datetime ) {
			return '';
		}
		$timestamp = strtotime( $datetime );
		return false === $timestamp ? '' : wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}
}
