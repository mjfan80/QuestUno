<?php
/**
 * QR code controller.
 *
 * @package QuestUno
 */

namespace QuestUno\Controller;

use QuestUno\Model\Checkpoint;
use QuestUno\Service\CheckpointService;
use QuestUno\Service\PathService;
use QuestUno\Service\QrCodeService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles QR code administration.
 */
final class QrCodeController {

	/** @var CheckpointService */
	private $checkpoint_service;

	/** @var PathService */
	private $path_service;

	/** @var QrCodeService */
	private $qr_code_service;

	/**
	 * Creates a QR code controller.
	 *
	 * @param CheckpointService $checkpoint_service Checkpoint service.
	 * @param PathService       $path_service       Path service.
	 * @param QrCodeService     $qr_code_service    QR code service.
	 */
	public function __construct( CheckpointService $checkpoint_service, PathService $path_service, QrCodeService $qr_code_service ) {
		$this->checkpoint_service = $checkpoint_service;
		$this->path_service       = $path_service;
		$this->qr_code_service    = $qr_code_service;
	}

	/**
	 * Registers the QR Codes admin page.
	 *
	 * @return void
	 */
	public function register_page(): void {
		add_submenu_page(
			'questuno',
			__( 'QR Codes', 'questuno' ),
			__( 'QR Codes', 'questuno' ),
			'edit_posts',
			'questuno-qr-codes',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the QR Codes admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$paths = $this->path_service->get_paths();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query parameter used only to filter the admin listing by Path; this request does not change any data.
		$selected_path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$selected_path    = 0 === $selected_path_id ? null : $this->path_service->get_path( $selected_path_id );
		$checkpoints      = null === $selected_path ? array() : $this->checkpoint_service->get_checkpoints_by_path( $selected_path_id );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'QR Codes', 'questuno' ); ?></h1>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
				<input type="hidden" name="page" value="questuno-qr-codes" />
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="questuno-qr-path"><?php esc_html_e( 'Path', 'questuno' ); ?></label>
							</th>
							<td>
								<select id="questuno-qr-path" name="path_id">
									<option value="0"><?php esc_html_e( 'Select a Path', 'questuno' ); ?></option>
									<?php foreach ( $paths as $path ) : ?>
										<option value="<?php echo esc_attr( (string) $path->get_id() ); ?>" <?php selected( $selected_path_id, $path->get_id() ); ?>>
											<?php echo esc_html( $path->get_name() ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<?php submit_button( __( 'Load Checkpoints', 'questuno' ), 'secondary', '', false ); ?>
							</td>
						</tr>
					</tbody>
				</table>
			</form>

			<?php if ( null !== $selected_path ) : ?>
				<p>
					<a class="button button-secondary" href="<?php echo esc_url( $this->get_print_url( $selected_path_id ) ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Print Path', 'questuno' ); ?>
					</a>
				</p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Name', 'questuno' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Token', 'questuno' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'questuno' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $checkpoints ) ) : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'No Checkpoints found for the selected Path.', 'questuno' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $checkpoints as $checkpoint ) : ?>
								<tr>
									<td><?php echo esc_html( get_the_title( (int) $checkpoint->get_post_id() ) ); ?></td>
									<td><code><?php echo esc_html( (string) $checkpoint->get_token() ); ?></code></td>
									<td>
										<a class="button" href="<?php echo esc_url( $this->get_download_url( (int) $checkpoint->get_post_id(), 'png' ) ); ?>">
											<?php esc_html_e( 'Download PNG', 'questuno' ); ?>
										</a>
										<a class="button" href="<?php echo esc_url( $this->get_download_url( (int) $checkpoint->get_post_id(), 'svg' ) ); ?>">
											<?php esc_html_e( 'Download SVG', 'questuno' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Downloads a QR code asset.
	 *
	 * @return void
	 */
	public function download(): void {
		$checkpoint_id = isset( $_GET['checkpoint_id'] ) ? absint( wp_unslash( $_GET['checkpoint_id'] ) ) : 0;
		$format        = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : '';

		if ( ! current_user_can( 'edit_post', $checkpoint_id ) || ! in_array( $format, array( 'png', 'svg' ), true ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'questuno_download_qr_code_' . $checkpoint_id . '_' . $format ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$checkpoint = $this->checkpoint_service->get_checkpoint( $checkpoint_id );

		if ( null === $checkpoint ) {
			wp_die( esc_html__( 'Checkpoint not found.', 'questuno' ) );
		}

		if ( 'png' === $format ) {
			$this->output_file(
				$this->build_checkpoint_filename( $checkpoint, 'png' ),
				'image/png',
				$this->qr_code_service->generate_checkpoint_png( $checkpoint )
			);
		}

		$this->output_file(
			$this->build_checkpoint_filename( $checkpoint, 'svg' ),
			'image/svg+xml',
			$this->qr_code_service->generate_checkpoint_svg( $checkpoint )
		);
	}

	/**
	 * Renders the printable QR code page for a Path.
	 *
	 * @return void
	 */
	public function print_path(): void {
		$path_id = isset( $_GET['path_id'] ) ? absint( wp_unslash( $_GET['path_id'] ) ) : 0;
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$path    = $this->path_service->get_path( $path_id );

		if ( null === $path || ! current_user_can( 'edit_post', (int) $path->get_post_id() ) || ! wp_verify_nonce( $nonce, 'questuno_print_path_qr_codes_' . $path_id ) ) {
			wp_die( esc_html__( 'Invalid request.', 'questuno' ) );
		}

		$checkpoints = $this->checkpoint_service->get_checkpoints_by_path( $path_id );

		$this->render_print_document( (string) $path->get_name(), $checkpoints );
	}

	/**
	 * Builds the download URL for a QR code asset.
	 *
	 * @param int    $checkpoint_id Checkpoint post identifier.
	 * @param string $format        Asset format.
	 * @return string
	 */
	private function get_download_url( int $checkpoint_id, string $format ): string {
		return wp_nonce_url(
			admin_url(
				'admin-post.php?action=questuno_download_qr_code&checkpoint_id=' . $checkpoint_id . '&format=' . rawurlencode( $format )
			),
			'questuno_download_qr_code_' . $checkpoint_id . '_' . $format
		);
	}

	/**
	 * Builds the print URL for a Path.
	 *
	 * @param int $path_id Path identifier.
	 * @return string
	 */
	private function get_print_url( int $path_id ): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=questuno_print_path_qr_codes&path_id=' . $path_id ),
			'questuno_print_path_qr_codes_' . $path_id
		);
	}

	/**
	 * Outputs a downloadable file and terminates execution.
	 *
	 * @param string $filename  File name.
	 * @param string $mime_type MIME type.
	 * @param string $content   File content.
	 * @return void
	 */
	private function output_file( string $filename, string $mime_type, string $content ): void {
		nocache_headers();
		header( 'Content-Type: ' . $mime_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary/image response generated internally by QrCodeService.
		exit;
	}

	/**
	 * Builds a download filename for a Checkpoint QR code.
	 *
	 * @param Checkpoint $checkpoint Checkpoint.
	 * @param string     $extension  File extension.
	 * @return string
	 */
	private function build_checkpoint_filename( Checkpoint $checkpoint, string $extension ): string {
		$title = sanitize_title( get_the_title( (int) $checkpoint->get_post_id() ) );

		if ( '' === $title ) {
			$title = 'checkpoint-' . (int) $checkpoint->get_post_id();
		}

		return $title . '.' . $extension;
	}

	/**
	 * Renders the printable HTML document.
	 *
	 * @param string                  $path_name   Path name.
	 * @param array<int, Checkpoint> $checkpoints Checkpoints.
	 * @return void
	 */
	private function render_print_document( string $path_name, array $checkpoints ): void {
		$this->enqueue_print_assets();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>" />
			<title><?php echo esc_html( $path_name ); ?></title>
			<?php wp_print_styles( array( 'questuno-print-path' ) ); ?>
		</head>
		<body>
			<div class="questuno-print-wrap">
				<h1 class="questuno-print-title"><?php echo esc_html( $path_name ); ?></h1>
				<div class="questuno-print-grid">
					<?php foreach ( $checkpoints as $checkpoint ) : ?>
						<div class="questuno-print-card">
							<?php echo $this->qr_code_service->generate_checkpoint_svg( $checkpoint ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG markup is generated internally by QrCodeService from trusted Checkpoint tokens. ?>
							<div class="questuno-print-name"><?php echo esc_html( get_the_title( (int) $checkpoint->get_post_id() ) ); ?></div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php wp_print_scripts( array( 'questuno-print-path' ) ); ?>
		</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Registers and queues the standalone Path print assets.
	 *
	 * @return void
	 */
	private function enqueue_print_assets(): void {
		$plugin_file = dirname( __DIR__, 2 ) . '/questuno.php';

		wp_register_style(
			'questuno-print-path',
			plugins_url( 'assets/css/print-path.css', $plugin_file ),
			array(),
			'1.0.1'
		);
		wp_enqueue_style( 'questuno-print-path' );

		wp_register_script(
			'questuno-print-path',
			plugins_url( 'assets/js/print-path.js', $plugin_file ),
			array(),
			'1.0.1',
			false
		);
		wp_enqueue_script( 'questuno-print-path' );
	}
}
