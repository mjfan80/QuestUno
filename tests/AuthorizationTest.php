<?php
/**
 * Authorization integration tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Controller\CheckpointController;
use QuestUno\Controller\DependencyController;
use QuestUno\Controller\GroupController;
use QuestUno\Controller\ParticipationController;
use QuestUno\Service\DependencyService;

/**
 * Verifies server-side authorization for resources associated with a Path.
 */
final class AuthorizationTest extends IntegrationTestCase {

	/** @var int */
	private $denied_post_id = 0;

	/**
	 * Resets request state between authorization tests.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_filter( 'user_has_cap', array( $this, 'deny_selected_post_edit' ), 10 );
		$_POST = array();
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Rejects a Group save when the current user cannot edit its target Path.
	 *
	 * @return void
	 */
	public function test_group_save_requires_edit_capability_for_target_path(): void {
		$services = $this->get_services();
		$path     = $this->create_path();
		$controller = new GroupController( $services['group_service'], $services['path_service'] );

		$this->set_current_administrator();
		$this->deny_editing_post( (int) $path->get_post_id() );
		$_POST = array(
			'questuno_group_nonce' => wp_create_nonce( 'questuno_save_group' ),
			'path_id'              => (string) $path->get_id(),
			'name'                 => 'Restricted group',
			'description'          => '',
			'completion_mode'      => 'ALL',
		);

		$this->assert_wp_die( array( $controller, 'save' ) );
		self::assertSame( array(), $services['group_service']->get_groups() );
	}

	/**
	 * Rejects cancelling a Participation when the associated Path cannot be edited.
	 *
	 * @return void
	 */
	public function test_participation_cancellation_requires_edit_capability_for_associated_path(): void {
		$services      = $this->get_services();
		$path          = $this->create_path();
		$participant   = self::factory()->user->create();
		$participation = $this->create_participation( $participant, (int) $path->get_id() );
		$controller    = new ParticipationController( $services['participation_service'], $services['path_service'], $services['progress_builder'], $services['event_service'], $services['checkpoint_service'] );

		$this->set_current_administrator();
		$this->deny_editing_post( (int) $path->get_post_id() );
		$_POST = array(
			'questuno_participation_nonce' => wp_create_nonce( 'questuno_cancel_participation_' . $participation->get_id() ),
			'participation_id'              => (string) $participation->get_id(),
		);

		$this->assert_wp_die( array( $controller, 'cancel' ) );
		self::assertSame( 'in_progress', $services['participation_service']->get_participation( (int) $participation->get_id() )->get_status() );
	}

	/**
	 * Preserves a Checkpoint assignment when the submitted Path cannot be edited.
	 *
	 * @return void
	 */
	public function test_checkpoint_assignment_requires_edit_capability_for_target_path(): void {
		$services      = $this->get_services();
		$current_path  = $this->create_path();
		$target_path   = $this->create_path();
		$checkpoint    = $this->create_checkpoint( (int) $current_path->get_id() );
		$dependency_controller = new DependencyController( new DependencyService( $services['dependency_repository'] ), $services['checkpoint_service'], $services['group_service'] );
		$controller    = new CheckpointController( $services['checkpoint_service'], $dependency_controller, $services['group_service'], $services['path_service'] );
		$checkpoint_post = get_post( (int) $checkpoint->get_post_id() );

		$this->set_current_administrator();
		$this->deny_editing_post( (int) $target_path->get_post_id() );
		$_POST = array(
			'questuno_checkpoint_path_nonce' => wp_create_nonce( 'questuno_checkpoint_path' ),
			'questuno_path_id'               => (string) $target_path->get_id(),
			'questuno_group_id'              => '0',
		);

		$controller->save( (int) $checkpoint->get_post_id(), $checkpoint_post );

		self::assertSame( (int) $current_path->get_id(), $services['checkpoint_service']->get_checkpoint( (int) $checkpoint->get_post_id() )->get_path_id() );
	}

	/**
	 * Creates an administrator for the current test request.
	 *
	 * @return void
	 */
	private function set_current_administrator(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Denies the object-specific edit check for the selected post.
	 *
	 * @param int $post_id Post identifier.
	 * @return void
	 */
	private function deny_editing_post( int $post_id ): void {
		$this->denied_post_id = $post_id;
		add_filter( 'user_has_cap', array( $this, 'deny_selected_post_edit' ), 10, 4 );
	}

	/**
	 * Removes the selected object's primitive edit capabilities.
	 *
	 * @param array<string,bool> $allcaps User capabilities.
	 * @param array<int,string>  $caps    Required primitive capabilities.
	 * @param array<int,mixed>   $args    Capability check arguments.
	 * @return array<string,bool>
	 */
	public function deny_selected_post_edit( array $allcaps, array $caps, array $args ): array {
		if ( isset( $args[0], $args[2] ) && 'edit_post' === $args[0] && $this->denied_post_id === (int) $args[2] ) {
			foreach ( $caps as $cap ) {
				$allcaps[ $cap ] = false;
			}
		}

		return $allcaps;
	}

	/**
	 * Asserts that the request is stopped with wp_die().
	 *
	 * @param callable $callback Request handler.
	 * @return void
	 */
	private function assert_wp_die( callable $callback ): void {
		add_filter( 'wp_die_handler', array( $this, 'get_throwing_wp_die_handler' ) );

		try {
			$callback();
			self::fail( 'Expected wp_die() to stop the request.' );
		} catch ( \RuntimeException $exception ) {
			self::assertSame( 'Invalid request.', $exception->getMessage() );
		} finally {
			remove_filter( 'wp_die_handler', array( $this, 'get_throwing_wp_die_handler' ) );
		}
	}

	/**
	 * Replaces WordPress' normal wp_die handler during a test.
	 *
	 * @return callable
	 */
	public function get_throwing_wp_die_handler(): callable {
		return array( $this, 'throw_wp_die_exception' );
	}

	/**
	 * Converts wp_die into an exception that PHPUnit can assert.
	 *
	 * @param string $message Error message.
	 * @return void
	 */
	public function throw_wp_die_exception( string $message ): void {
		throw new \RuntimeException( wp_strip_all_tags( $message ) );
	}
}
