<?php
/**
 * Path controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\PathPostType;
use QuestUno\Model\Path;
use QuestUno\Service\CheckpointService;
use QuestUno\Service\PathConfigurationValidator;
use QuestUno\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class PathController {

	/** @var PathService */
	private $path_service;

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var PathConfigurationValidator */
	private $configuration_validator;

	/** @var bool */
	private $is_correcting_post_status = false;

	/** @var bool */
	private $is_transitioning_post_status = false;

	/**
	 * Creates a Path controller.
	 *
	 * @param PathService                $path_service             Path service.
	 * @param CheckpointService          $checkpoint_service       Checkpoint service.
	 * @param PathConfigurationValidator $configuration_validator  Path configuration validator.
	 */
	public function __construct( PathService $path_service, CheckpointService $checkpoint_service, PathConfigurationValidator $configuration_validator ) {
		$this->path_service             = $path_service;
		$this->checkpoint_service       = $checkpoint_service;
		$this->configuration_validator  = $configuration_validator;
	}

	/**
	 * Registers Path metaboxes.
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		add_meta_box(
			'questuno-path-checkpoints',
			__( 'Path Checkpoints', 'questuno' ),
			array( $this, 'render_checkpoints_metabox' ),
			PathPostType::POST_TYPE,
			'side'
		);

		add_meta_box(
			'questuno-path-configuration-check',
			__( 'Configuration check', 'questuno' ),
			array( $this, 'render_configuration_check_metabox' ),
			PathPostType::POST_TYPE,
			'normal',
			'default'
		);
	}

	/**
	 * Renders the start and finish Checkpoint metabox.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return void
	 */
	public function render_checkpoints_metabox( \WP_Post $post ): void {
		$path = $this->path_service->get_path_by_post_id( $post->ID );
		$path_id = null === $path || null === $path->get_id()
			? 0
			: (int) $path->get_id();
		$start_checkpoint_id = null === $path || null === $path->get_start_checkpoint_id()
			? 0
			: (int) $path->get_start_checkpoint_id();
		$finish_checkpoint_id = null === $path || null === $path->get_finish_checkpoint_id()
			? 0
			: (int) $path->get_finish_checkpoint_id();
		$opening_date = null === $path ? '' : (string) $path->get_opening_date();
		$closing_date = null === $path ? '' : (string) $path->get_closing_date();
		$checkpoints = 0 === $path_id
			? array()
			: $this->checkpoint_service->get_checkpoints_by_path( $path_id );

		wp_nonce_field( 'questuno_path_checkpoints', 'questuno_path_checkpoints_nonce' );
		?>
		<p>
			<label for="questuno-start-checkpoint-id"><?php esc_html_e( 'Start Checkpoint', 'questuno' ); ?></label>
			<select id="questuno-start-checkpoint-id" name="questuno_start_checkpoint_id">
				<option value="0"><?php esc_html_e( 'Select a Checkpoint', 'questuno' ); ?></option>
				<?php foreach ( $checkpoints as $checkpoint ) : ?>
					<?php $checkpoint_id = (int) $checkpoint->get_post_id(); ?>
					<option value="<?php echo esc_attr( (string) $checkpoint_id ); ?>" <?php selected( $start_checkpoint_id, $checkpoint_id ); ?>>
						<?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( $checkpoint_id ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="questuno-opening-date"><?php esc_html_e( 'Opening date', 'questuno' ); ?></label>
			<input id="questuno-opening-date" name="questuno_opening_date" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $opening_date ) ); ?>" />
		</p>
		<p>
			<label for="questuno-closing-date"><?php esc_html_e( 'Closing date', 'questuno' ); ?></label>
			<input id="questuno-closing-date" name="questuno_closing_date" type="datetime-local" value="<?php echo esc_attr( $this->format_datetime_input( $closing_date ) ); ?>" />
		</p>
		<p>
			<label for="questuno-finish-checkpoint-id"><?php esc_html_e( 'Finish Checkpoint', 'questuno' ); ?></label>
			<select id="questuno-finish-checkpoint-id" name="questuno_finish_checkpoint_id">
				<option value="0"><?php esc_html_e( 'Select a Checkpoint', 'questuno' ); ?></option>
				<?php foreach ( $checkpoints as $checkpoint ) : ?>
					<?php $checkpoint_id = (int) $checkpoint->get_post_id(); ?>
					<option value="<?php echo esc_attr( (string) $checkpoint_id ); ?>" <?php selected( $finish_checkpoint_id, $checkpoint_id ); ?>>
						<?php echo esc_html( $this->checkpoint_service->get_checkpoint_title( $checkpoint_id ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php if ( empty( $checkpoints ) ) : ?>
			<p class="description">
				<?php esc_html_e( 'Save this Path and assign Checkpoints to it before selecting start and finish Checkpoints.', 'questuno' ); ?>
			</p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the publishability diagnostics for a saved Path.
	 *
	 * @param \WP_Post $post WordPress post object.
	 * @return void
	 */
	public function render_configuration_check_metabox( \WP_Post $post ): void {
		$path = $this->path_service->get_path_by_post_id( $post->ID );

		if ( null === $path || null === $path->get_id() ) {
			?>
			<p><?php esc_html_e( 'Save this Path before running its configuration check.', 'questuno' ); ?></p>
			<?php
			return;
		}

		$diagnostics = $this->configuration_validator->get_diagnostics( $path );
		?>
		<p>
			<?php if ( $diagnostics['publishable'] ) : ?>
				<strong><?php esc_html_e( 'This Path configuration can be published.', 'questuno' ); ?></strong>
			<?php else : ?>
				<strong><?php esc_html_e( 'This Path configuration cannot be published.', 'questuno' ); ?></strong>
			<?php endif; ?>
		</p>

		<?php $this->render_diagnostic_list( __( 'Checks passed', 'questuno' ), $diagnostics['checks'], 'notice-success' ); ?>
		<?php $this->render_diagnostic_list( __( 'Blocking errors', 'questuno' ), $diagnostics['errors'], 'notice-error' ); ?>
		<?php $this->render_diagnostic_list( __( 'Warnings', 'questuno' ), $diagnostics['warnings'], 'notice-warning' ); ?>
		<?php
	}

	/**
	 * Saves Path technical data after post save.
	 *
	 * @param int      $post_id Post identifier.
	 * @param \WP_Post $post    WordPress post object.
	 * @return void
	 */
	public function save( int $post_id, \WP_Post $post ): void {
		if ( $this->is_transitioning_post_status || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$stored_path = $this->path_service->get_path_by_post_id( $post_id );
		$path = new Path();
		$path->set_post_id( $post_id );
		$path->set_name( $post->post_title );
		$path->set_description( $post->post_content );
		$path->set_status( $post->post_status );

		if ( null !== $stored_path ) {
			$path->set_id( $stored_path->get_id() );
			$path->set_start_checkpoint_id( $stored_path->get_start_checkpoint_id() );
			$path->set_finish_checkpoint_id( $stored_path->get_finish_checkpoint_id() );
			$path->set_opening_date( $stored_path->get_opening_date() );
			$path->set_closing_date( $stored_path->get_closing_date() );
		}

		$has_valid_checkpoints_nonce = isset( $_POST['questuno_path_checkpoints_nonce'] )
			&& wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['questuno_path_checkpoints_nonce'] ) ),
				'questuno_path_checkpoints'
			);

		if ( $has_valid_checkpoints_nonce && null !== $stored_path && null !== $stored_path->get_id() ) {
			$checkpoint_ids        = $this->get_checkpoint_ids_by_path( (int) $stored_path->get_id() );
			$start_checkpoint_id  = isset( $_POST['questuno_start_checkpoint_id'] ) ? absint( wp_unslash( $_POST['questuno_start_checkpoint_id'] ) ) : 0;
			$finish_checkpoint_id = isset( $_POST['questuno_finish_checkpoint_id'] ) ? absint( wp_unslash( $_POST['questuno_finish_checkpoint_id'] ) ) : 0;

			if ( $start_checkpoint_id === $finish_checkpoint_id ) {
				$finish_checkpoint_id = 0;
			}

			$path->set_start_checkpoint_id(
				isset( $checkpoint_ids[ $start_checkpoint_id ] ) ? $start_checkpoint_id : null
			);
			$path->set_finish_checkpoint_id(
				isset( $checkpoint_ids[ $finish_checkpoint_id ] ) ? $finish_checkpoint_id : null
			);
			$opening_date = isset( $_POST['questuno_opening_date'] )
				? sanitize_text_field( wp_unslash( $_POST['questuno_opening_date'] ) )
				: '';
			$closing_date = isset( $_POST['questuno_closing_date'] )
				? sanitize_text_field( wp_unslash( $_POST['questuno_closing_date'] ) )
				: '';
			$path->set_opening_date( $this->get_datetime_input( $opening_date ) );
			$path->set_closing_date( $this->get_datetime_input( $closing_date ) );
		}

		$this->path_service->save_path( $path );

		if ( 'publish' === $post->post_status && ! $this->is_correcting_post_status ) {
			$errors = $this->configuration_validator->validate( $path );

			if ( ! empty( $errors ) ) {
				$this->is_correcting_post_status = true;
				set_transient( 'questuno_path_configuration_errors_' . $post_id, $errors, MINUTE_IN_SECONDS );
				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_status' => 'draft',
					)
				);
				$this->is_correcting_post_status = false;
			}
		}
	}

	/**
	 * Adds archive actions to Path rows in the WordPress administration list.
	 *
	 * @param array<string,string> $actions Row actions.
	 * @param \WP_Post             $post    Current post.
	 * @return array<string,string>
	 */
	public function add_row_actions( array $actions, \WP_Post $post ): array {
		if ( PathPostType::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		if ( 'publish' === $post->post_status ) {
			$actions['questuno_archive'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=questuno_archive_path&post_id=' . $post->ID ), 'questuno_archive_path_' . $post->ID ) ),
				esc_html__( 'Archive', 'questuno' )
			);
		}

		if ( PathPostType::ARCHIVED_STATUS === $post->post_status ) {
			$actions['questuno_restore'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=questuno_restore_path&post_id=' . $post->ID ), 'questuno_restore_path_' . $post->ID ) ),
				esc_html__( 'Restore to Draft', 'questuno' )
			);
		}

		$actions['questuno_statistics'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=questuno-path-statistics&path_id=' . $post->ID ) ),
			esc_html__( 'Statistics', 'questuno' )
		);

		return $actions;
	}

	/**
	 * Adds the configuration diagnostic column to the Path list.
	 *
	 * @param array<string,string> $columns List columns.
	 * @return array<string,string>
	 */
	public function add_list_columns( array $columns ): array {
		$columns['questuno_configuration'] = __( 'Configuration', 'questuno' );

		return $columns;
	}

	/**
	 * Renders the configuration diagnostic summary in a Path list column.
	 *
	 * @param string $column_name Column identifier.
	 * @param int    $post_id     Path post identifier.
	 * @return void
	 */
	public function render_list_column( string $column_name, int $post_id ): void {
		if ( 'questuno_configuration' !== $column_name || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}

		$path = $this->path_service->get_path_by_post_id( $post_id );

		if ( null === $path ) {
			return;
		}

		$diagnostics  = $this->configuration_validator->get_diagnostics( $path );
		$error_count  = count( $diagnostics['errors'] );
		$warning_count = count( $diagnostics['warnings'] );
		$summary      = $this->get_configuration_summary( $error_count, $warning_count );
		$edit_link    = get_edit_post_link( $post_id );

		if ( $edit_link ) {
			printf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $edit_link ),
				esc_html( $summary )
			);
			return;
		}

		echo esc_html( $summary );
	}

	/**
	 * Archives a published Path.
	 *
	 * @return void
	 */
	public function archive(): void {
		$this->handle_status_transition( PathPostType::ARCHIVED_STATUS, 'questuno_archive_path' );
	}

	/**
	 * Restores an archived Path as a draft.
	 *
	 * @return void
	 */
	public function restore(): void {
		$this->handle_status_transition( 'draft', 'questuno_restore_path' );
	}

	/**
	 * Transitions a Path to the requested status from an admin action.
	 *
	 * @param string $status Target status.
	 * @param string $action Action nonce prefix.
	 * @return void
	 */
	private function handle_status_transition( string $status, string $action ): void {
		$post_id = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;

		if ( 0 === $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		check_admin_referer( $action . '_' . $post_id );

		if ( ! $this->transition_post_status( $post_id, $status ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		wp_safe_redirect( admin_url( 'edit.php?post_type=' . PathPostType::POST_TYPE . '&post_status=' . $status ) );
		exit;
	}

	/**
	 * Changes a Path status without altering its associated QuestUno data.
	 *
	 * @param int    $post_id Path post identifier.
	 * @param string $status  Target post status.
	 * @return bool
	 */
	public function transition_post_status( int $post_id, string $status ): bool {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || PathPostType::POST_TYPE !== $post->post_type ) {
			return false;
		}

		if ( PathPostType::ARCHIVED_STATUS === $status && 'publish' !== $post->post_status ) {
			return false;
		}

		if ( 'draft' === $status && PathPostType::ARCHIVED_STATUS !== $post->post_status ) {
			return false;
		}

		$this->is_transitioning_post_status = true;

		try {
			$updated_post_id = wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => $status,
				),
				true
			);
		} finally {
			$this->is_transitioning_post_status = false;
		}

		if ( is_wp_error( $updated_post_id ) || 0 === $updated_post_id ) {
			return false;
		}

		$updated_post = get_post( $post_id );

		if ( ! $updated_post instanceof \WP_Post ) {
			return false;
		}

		$this->save( $post_id, $updated_post );

		return true;
	}

	/**
	 * Gets Checkpoint post identifiers belonging to a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return array<int, bool>
	 */
	private function get_checkpoint_ids_by_path( int $path_id ): array {
		$checkpoint_ids = array();

		foreach ( $this->checkpoint_service->get_checkpoints_by_path( $path_id ) as $checkpoint ) {
			$checkpoint_ids[ (int) $checkpoint->get_post_id() ] = true;
		}

		return $checkpoint_ids;
	}

	/**
	 * Gets the compact configuration diagnostic summary for the Path list.
	 *
	 * @param int $error_count   Number of blocking errors.
	 * @param int $warning_count Number of warnings.
	 * @return string
	 */
	private function get_configuration_summary( int $error_count, int $warning_count ): string {
		if ( 0 === $error_count && 0 === $warning_count ) {
			return __( '✓ OK', 'questuno' );
		}

		if ( 0 === $error_count ) {
			return sprintf(
				/* translators: %s: number of configuration warnings. */
				_n( '⚠ %s warning', '⚠ %s warnings', $warning_count, 'questuno' ),
				number_format_i18n( $warning_count )
			);
		}

		$summary = sprintf(
			/* translators: %s: number of blocking configuration errors. */
			_n( '✕ %s error', '✕ %s errors', $error_count, 'questuno' ),
			number_format_i18n( $error_count )
		);

		if ( 0 === $warning_count ) {
			return $summary;
		}

		return sprintf(
			/* translators: 1: error summary, 2: warning summary. */
			__( '%1$s · %2$s', 'questuno' ),
			$summary,
			sprintf(
				/* translators: %s: number of configuration warnings. */
				_n( '⚠ %s warning', '⚠ %s warnings', $warning_count, 'questuno' ),
				number_format_i18n( $warning_count )
			)
		);
	}

	/**
	 * Renders one configuration diagnostic group.
	 *
	 * @param string             $title Group title.
	 * @param array<int,string>  $items Diagnostic messages.
	 * @param string             $class WordPress notice class.
	 * @return void
	 */
	private function render_diagnostic_list( string $title, array $items, string $class ): void {
		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="notice inline <?php echo esc_attr( $class ); ?>">
			<p><strong><?php echo esc_html( $title ); ?></strong></p>
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo esc_html( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function format_datetime_input( string $value ): string {
		$timestamp = strtotime( $value );

		return false === $timestamp ? '' : wp_date( 'Y-m-d\\TH:i', $timestamp );
	}

	private function get_datetime_input( string $value ): ?string {
		if ( '' === $value ) {
			return null;
		}

		$date = \DateTimeImmutable::createFromFormat( 'Y-m-d\\TH:i', $value, wp_timezone() );

		return $date instanceof \DateTimeImmutable && $date->format( 'Y-m-d\\TH:i' ) === $value ? $date->format( 'Y-m-d H:i:s' ) : null;
	}

	/**
	 * Renders configuration errors after a prevented publication.
	 *
	 * @return void
	 */
	public function render_configuration_errors(): void {
		$post_id = get_the_ID();
		$errors  = false === $post_id ? false : get_transient( 'questuno_path_configuration_errors_' . $post_id );

		if ( ! is_array( $errors ) ) {
			return;
		}

		delete_transient( 'questuno_path_configuration_errors_' . $post_id );
		?>
		<div class="notice notice-error">
			<p><strong><?php esc_html_e( 'The Path was saved as a draft because its configuration is not publishable.', 'questuno' ); ?></strong></p>
			<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
