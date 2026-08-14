<?php
/**
 * Plugin administration menu registration.
 *
 * @package QuestUno
 */

namespace QuestUno;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the QuestUno administration menu.
 */
final class AdminMenu {

	/** @var callable */
	private $dashboard_callback;

	/**
	 * Creates an admin menu registrar.
	 *
	 * @param callable $dashboard_callback Dashboard callback.
	 */
	public function __construct( callable $dashboard_callback ) {
		$this->dashboard_callback = $dashboard_callback;
	}

	/**
	 * Registers the QuestUno administration menu.
	 *
	 * @return void
	 */
	public function register(): void {
		add_menu_page(
			__( 'QuestUno', 'questuno' ),
			__( 'QuestUno', 'questuno' ),
			'manage_options',
			'questuno',
			$this->dashboard_callback,
			'dashicons-location-alt',
			26
		);

		add_submenu_page(
			'questuno',
			__( 'Paths', 'questuno' ),
			__( 'Paths', 'questuno' ),
			'edit_posts',
			'edit.php?post_type=' . PathPostType::POST_TYPE
		);

		add_submenu_page(
			'questuno',
			__( 'Checkpoints', 'questuno' ),
			__( 'Checkpoints', 'questuno' ),
			'edit_posts',
			'edit.php?post_type=' . CheckpointPostType::POST_TYPE
		);
	}
}
