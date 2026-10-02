<?php
/**
 * Path publication tests.
 *
 * @package QuestUno
 */

namespace QuestUno\Tests;

use QuestUno\Controller\PathController;

/**
 * Verifies Path publication through the WordPress post save flow.
 */
final class PathPublicationTest extends IntegrationTestCase {
	/**
	 * Publishes an already saved Path with a valid configuration.
	 *
	 * @return void
	 */
	public function test_publishes_an_already_saved_valid_path(): void {
		$services      = $this->get_services();
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$path          = $this->create_path( array( 'status' => 'draft' ) );
		$start         = $this->create_checkpoint( (int) $path->get_id() );
		$finish        = $this->create_checkpoint( (int) $path->get_id() );

		$path->set_start_checkpoint_id( (int) $start->get_post_id() );
		$path->set_finish_checkpoint_id( (int) $finish->get_post_id() );
		$services['path_service']->save_path( $path );
		$configured_path = $services['path_service']->get_path_by_post_id( (int) $path->get_post_id() );

		self::assertNotNull( $configured_path );
		self::assertNotNull( $configured_path->get_id() );
		self::assertSame( $start->get_post_id(), $configured_path->get_start_checkpoint_id() );
		self::assertSame( $finish->get_post_id(), $configured_path->get_finish_checkpoint_id() );
		self::assertSame( array(), $services['path_configuration_validator']->validate( $configured_path ) );

		wp_set_current_user( $administrator );
		wp_update_post(
			array(
				'ID'          => $path->get_post_id(),
				'post_status' => 'publish',
			)
		);
		$post = get_post( (int) $path->get_post_id() );

		self::assertInstanceOf( '\\WP_Post', $post );

		$controller = new PathController(
			$services['path_service'],
			$services['checkpoint_service'],
			$services['path_configuration_validator']
		);
		$controller->save( (int) $path->get_post_id(), $post );

		$published_path = $services['path_service']->get_path_by_post_id( (int) $path->get_post_id() );

		self::assertSame( 'publish', get_post_status( (int) $path->get_post_id() ) );
		self::assertNotNull( $published_path );
		self::assertSame( $path->get_id(), $published_path->get_id() );
		self::assertSame( 'publish', $published_path->get_status() );
	}
}
