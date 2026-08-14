<?php
namespace QuestUno\Controller;

use QuestUno\Model\Group;
use QuestUno\Service\GroupService;
use QuestUno\Service\PathService;

defined( 'ABSPATH' ) || exit;

final class GroupController {
	public const PAGE_SLUG = 'questuno-groups';
	private $group_service;
	private $path_service;

	public function __construct( GroupService $group_service, PathService $path_service ) {
		$this->group_service = $group_service;
		$this->path_service  = $path_service;
	}

	public function register_page(): void {
		add_submenu_page( 'questuno', __( 'Groups', 'questuno' ), __( 'Groups', 'questuno' ), 'edit_posts', self::PAGE_SLUG, array( $this, 'render_page' ) );
	}

	public function render_page(): void {
		$paths        = $this->path_service->get_paths();
		$groups       = $this->group_service->get_groups();
		$path_names   = $this->get_path_names( $paths );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query parameter used only to preload the selected Group into the admin edit form; this request does not change any data.
		$editing_id   = isset( $_GET['group_id'] ) ? absint( wp_unslash( $_GET['group_id'] ) ) : 0;
		$editing_group = 0 === $editing_id ? null : $this->group_service->get_group( $editing_id );
		$is_edit_mode = null !== $editing_group;
		$form_title   = $is_edit_mode ? __( 'Edit Group', 'questuno' ) : __( 'Add Group', 'questuno' );
		$button_label = $is_edit_mode ? __( 'Update Group', 'questuno' ) : __( 'Add Group', 'questuno' );
		$path_id      = $is_edit_mode ? (int) $editing_group->get_path_id() : 0;
		$name         = $is_edit_mode ? (string) $editing_group->get_name() : '';
		$description  = $is_edit_mode ? (string) $editing_group->get_description() : '';
		$completion_mode = $is_edit_mode && null !== $editing_group->get_completion_mode() ? (string) $editing_group->get_completion_mode() : 'ALL';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Groups', 'questuno' ); ?></h1>

			<h2><?php echo esc_html( $form_title ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'questuno_save_group', 'questuno_group_nonce' ); ?>
				<input type="hidden" name="action" value="questuno_save_group" />
				<?php if ( $is_edit_mode ) : ?>
					<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) $editing_group->get_id() ); ?>" />
				<?php endif; ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="questuno-group-path"><?php esc_html_e( 'Path', 'questuno' ); ?></label>
							</th>
							<td>
								<select id="questuno-group-path" name="path_id" required>
									<option value=""><?php esc_html_e( 'Select a Path', 'questuno' ); ?></option>
									<?php foreach ( $paths as $path ) : ?>
										<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $path_id, $path->get_id() ); ?>>
											<?php echo esc_html( $path->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="questuno-group-name"><?php esc_html_e( 'Name', 'questuno' ); ?></label>
							</th>
							<td>
								<input id="questuno-group-name" class="regular-text" name="name" type="text" value="<?php echo esc_attr( $name ); ?>" required />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="questuno-group-description"><?php esc_html_e( 'Description', 'questuno' ); ?></label>
							</th>
							<td>
								<textarea id="questuno-group-description" class="large-text" name="description" rows="5"><?php echo esc_textarea( $description ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="questuno-group-completion-mode"><?php esc_html_e( 'Completion Mode', 'questuno' ); ?></label>
							</th>
							<td>
								<select id="questuno-group-completion-mode" name="completion_mode" required>
									<option value="ALL" <?php selected( $completion_mode, 'ALL' ); ?>><?php esc_html_e( 'ALL', 'questuno' ); ?></option>
									<option value="ANY" <?php selected( $completion_mode, 'ANY' ); ?>><?php esc_html_e( 'ANY', 'questuno' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( $button_label ); ?>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'questuno' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Description', 'questuno' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Path', 'questuno' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Completion Mode', 'questuno' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'questuno' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $groups as $group ) : ?>
						<tr>
							<td><?php echo esc_html( $group->get_name() ); ?></td>
							<td><?php echo esc_html( (string) $group->get_description() ); ?></td>
							<td><?php echo esc_html( $path_names[ $group->get_path_id() ] ?? '' ); ?></td>
							<td><?php echo esc_html( (string) $group->get_completion_mode() ); ?></td>
							<td>
								<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&group_id=' . $group->get_id() ) ); ?>">
									<?php esc_html_e( 'Edit', 'questuno' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="questuno-inline-form">
									<?php wp_nonce_field( 'questuno_delete_group_' . $group->get_id(), 'questuno_group_nonce' ); ?>
									<input type="hidden" name="action" value="questuno_delete_group" />
									<input type="hidden" name="group_id" value="<?php echo esc_attr( (string) $group->get_id() ); ?>" />
									<button type="submit" class="button-link-delete"><?php esc_html_e( 'Delete', 'questuno' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function save(): void {
		if ( ! isset( $_POST['questuno_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['questuno_group_nonce'] ) ), 'questuno_save_group' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$group_id = isset( $_POST['group_id'] ) ? absint( wp_unslash( $_POST['group_id'] ) ) : 0;
		$path_id  = absint( wp_unslash( $_POST['path_id'] ?? 0 ) );
		$path     = 0 === $path_id ? null : $this->path_service->get_path( $path_id );
		$existing_group = 0 === $group_id ? null : $this->group_service->get_group( $group_id );

		if ( null === $path || ! current_user_can( 'edit_post', (int) $path->get_post_id() ) || ( 0 !== $group_id && ( null === $existing_group || ! $this->can_edit_path( (int) $existing_group->get_path_id() ) ) ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$raw_completion_mode = isset( $_POST['completion_mode'] ) ? sanitize_key( wp_unslash( $_POST['completion_mode'] ) ) : 'all';
		$completion_mode = in_array( strtoupper( $raw_completion_mode ), array( 'ALL', 'ANY' ), true ) ? strtoupper( $raw_completion_mode ) : 'ALL';
		$group    = new Group();

		if ( 0 !== $group_id ) {
			$group->set_id( $group_id );
		}

		$group->set_path_id( $path_id );
		$group->set_name( sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ) );
		$group->set_description( sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ) );
		$group->set_completion_mode( $completion_mode );

		$this->group_service->save_group( $group );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	public function delete(): void {
		$id = absint( wp_unslash( $_POST['group_id'] ?? 0 ) );
		$group = 0 === $id ? null : $this->group_service->get_group( $id );

		if ( null === $group || ! $this->can_edit_path( (int) $group->get_path_id() ) || ! isset( $_POST['questuno_group_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['questuno_group_nonce'] ) ), 'questuno_delete_group_' . $id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$this->group_service->delete_group( $id );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	private function get_path_names( array $paths ): array {
		$path_names = array();

		foreach ( $paths as $path ) {
			$path_names[ $path->get_id() ] = $path->get_name();
		}

		return $path_names;
	}

	private function can_edit_path( int $path_id ): bool {
		$path = $this->path_service->get_path( $path_id );

		return null !== $path && current_user_can( 'edit_post', (int) $path->get_post_id() );
	}
}
