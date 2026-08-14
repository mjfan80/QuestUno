<?php
/**
 * Checkpoint controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\CheckpointPostType;
use QuestUno\Model\Checkpoint;
use QuestUno\Service\CheckpointService;
use QuestUno\Service\GroupService;
use QuestUno\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class CheckpointController {

	private $checkpoint_service;
	private $dependency_controller;
	private $group_service;
	private $path_service;

	public function __construct( CheckpointService $checkpoint_service, DependencyController $dependency_controller, GroupService $group_service, PathService $path_service ) {
		$this->checkpoint_service   = $checkpoint_service;
		$this->dependency_controller = $dependency_controller;
		$this->group_service        = $group_service;
		$this->path_service         = $path_service;
	}

	public function register_metabox(): void {
		add_meta_box( 'questuno-checkpoint-path', __( 'Path', 'questuno' ), array( $this, 'render_path_metabox' ), CheckpointPostType::POST_TYPE, 'side' );
	}

	public function enqueue_assets( string $hook_suffix ): void {
		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) || CheckpointPostType::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'questuno-admin-ui', plugins_url( 'assets/css/admin-ui.css', dirname( __DIR__, 2 ) . '/questuno.php' ), array(), '1.0.0' );
		wp_enqueue_script( 'questuno-checkpoint-metabox', plugins_url( 'assets/js/checkpoint-metabox.js', dirname( __DIR__, 2 ) . '/questuno.php' ), array(), '1.0.0', true );
		wp_localize_script(
			'questuno-checkpoint-metabox',
			'questunoCheckpointMetabox',
			array(
				'groups'       => $this->get_group_options(),
				'noGroupLabel' => __( 'No Group', 'questuno' ),
			)
		);
	}

	public function render_path_metabox( \WP_Post $post ): void {
		$checkpoint          = $this->checkpoint_service->get_checkpoint( $post->ID );
		$path_id             = null === $checkpoint ? 0 : (int) $checkpoint->get_path_id();
		$group_id            = null === $checkpoint || null === $checkpoint->get_group_id() ? 0 : (int) $checkpoint->get_group_id();
		$paths               = $this->path_service->get_paths();

		wp_nonce_field( 'questuno_checkpoint_path', 'questuno_checkpoint_path_nonce' );
		?>
		<label for="questuno-path-id"><?php esc_html_e( 'Path', 'questuno' ); ?></label>
		<select id="questuno-path-id" name="questuno_path_id">
			<option value="0"><?php esc_html_e( 'Select a Path', 'questuno' ); ?></option>
			<?php foreach ( $paths as $path ) : ?>
				<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>><?php echo esc_html( $path->get_name() ); ?></option>
			<?php endforeach; ?>
		</select>
		<p>
			<label for="questuno-group-id"><?php esc_html_e( 'Group', 'questuno' ); ?></label>
			<select id="questuno-group-id" name="questuno_group_id" data-selected-group-id="<?php echo esc_attr( (string) $group_id ); ?>">
				<option value="0"><?php esc_html_e( 'No Group', 'questuno' ); ?></option>
			</select>
		</p>
		<p>
			<label for="questuno-checkpoint-token"><?php esc_html_e( 'Token', 'questuno' ); ?></label>
			<?php if ( null === $checkpoint ) : ?>
				<input id="questuno-checkpoint-token" type="text" value="" readonly="readonly" />
				<span class="description"><?php esc_html_e( 'The token will be generated when the Checkpoint is first saved.', 'questuno' ); ?></span>
			<?php else : ?>
				<input id="questuno-checkpoint-token" type="text" value="<?php echo esc_attr( $checkpoint->get_token() ); ?>" readonly="readonly" />
			<?php endif; ?>
		</p>
		<?php $this->dependency_controller->render_section( $post, $path_id ); ?>
		<?php
	}

	public function save( int $post_id, \WP_Post $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) || ! isset( $_POST['questuno_checkpoint_path_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['questuno_checkpoint_path_nonce'] ) ), 'questuno_checkpoint_path' ) || ! isset( $_POST['questuno_path_id'] ) ) {
			return;
		}

		$path_id    = absint( wp_unslash( $_POST['questuno_path_id'] ) );
		$group_id   = isset( $_POST['questuno_group_id'] ) ? absint( wp_unslash( $_POST['questuno_group_id'] ) ) : 0;
		$path       = 0 === $path_id ? null : $this->path_service->get_path( $path_id );
		$group      = 0 === $group_id ? null : $this->group_service->get_group( $group_id );

		if ( null === $path || ! current_user_can( 'edit_post', (int) $path->get_post_id() ) || ( 0 !== $group_id && ( null === $group || $path_id !== (int) $group->get_path_id() ) ) ) {
			return;
		}

		$checkpoint = new Checkpoint();

		$checkpoint->set_post_id( $post_id );
		$checkpoint->set_path_id( $path_id );
		$checkpoint->set_group_id( 0 === $group_id ? null : $group_id );

		$this->checkpoint_service->save_path( $checkpoint );
		$this->dependency_controller->save( $post_id );
	}

	private function get_group_options(): array {
		$groups = array();

		foreach ( $this->group_service->get_groups() as $group ) {
			$groups[] = array(
				'id'      => $group->get_id(),
				'path_id' => $group->get_path_id(),
				'name'    => $group->get_name(),
			);
		}

		return $groups;
	}
}
