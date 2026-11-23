<?php
/**
 * Admin Upload Page class.
 *
 * @package EngineWP\RelayCore\Admin
 */

namespace EngineWP\RelayCore\Admin;

use EngineWP\RelayCore\Import\CsvReader;
use EngineWP\RelayCore\Import\FileValidator;
use EngineWP\RelayCore\Import\JsonReader;
use EngineWP\RelayCore\Import\ImportState;
use EngineWP\RelayCore\Import\ReaderFactory;
use EngineWP\RelayCore\Import\BackgroundImport;

/**
 * Class UploadPage
 *
 * Handles the admin page for uploading files.
 *
 * @package EngineWP\RelayCore\Admin
 */
class UploadPage {

	/**
	 * Starts an import in the background.
	 *
	 * @var BackgroundImport
	 */
	private $background_import;

	/**
	 * Constructor.
	 *
	 * @param BackgroundImport $background_import Provide function so we can run improts on background.
	 */
	public function __construct( BackgroundImport $background_import ) {
		$this->background_import = $background_import;
	}

	/**
	 * Screen name returned by add_submenu_page(), used to load the stylesheet only on this page.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Registers the admin menu page.
	 */
	public function register_menu() {
		$this->hook = add_submenu_page( 'edit.php?post_type=listing', 'Bulk Data Import | Relay', 'Bulk Import', 'manage_options', 'relay-import', array( $this, 'render_page' ) );
	}

	/**
	 * Loads the Bulk Import stylesheet on this page only.
	 *
	 * @param string $hook_suffix Screen name of the current admin page.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook ) {
			return;
		}

		$asset_path = plugin_dir_path( RELAY_CORE_FILE ) . 'build/upload.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			return;
		}

		$asset = require $asset_path;
		wp_enqueue_style( 'relay-core-upload', plugins_url( '/build/upload.css', RELAY_CORE_FILE ), array(), $asset['version'] );
	}

	/**
	 * Prints the page title, subtitle and the three step indicator.
	 *
	 * @param int    $current_step Step being shown: 1 upload, 2 map, 3 import.
	 * @param string $title        Page heading.
	 * @param string $subtitle     Line under the heading.
	 * @return void
	 */
	private function page_header( int $current_step, string $title, string $subtitle ) {
		$steps = array( 'Upload file', 'Map columns', 'Import' );
		?>
		<div class="rc-page-header">
			<h1><?php echo esc_html( $title ); ?></h1>
			<p class="rc-page-header__subtitle"><?php echo esc_html( $subtitle ); ?></p>
		</div>
		<ol class="rc-steps">
			<?php
			foreach ( $steps as $index => $label ) :
				$step_number = $index + 1;
				$step_class  = '';

				if ( $step_number < $current_step ) {
					$step_class = 'is-done';
				} elseif ( $step_number === $current_step ) {
					$step_class = 'is-current';
				}
				?>
				<li class="rc-steps__item <?php echo esc_attr( $step_class ); ?>" <?php echo $step_number === $current_step ? 'aria-current="step"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Renders the file upload form HTML.
	 *
	 * @return void
	 */
	public function import_html() {
		?>
		<div class="wrap rc-app">
			<?php $this->page_header( 1, 'Bulk Import', 'Import listings from a CSV or JSON file.' ); ?>
			<div class="rc-card rc-form-card">
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'relay_core_upload', 'relay_core_nonce' ); ?>
					<input type="hidden" name="page_step_num" value="1">
					<label class="rc-dropzone" for="rc-import-file">
						<span class="dashicons dashicons-upload rc-dropzone__icon" aria-hidden="true"></span>
						<span class="rc-dropzone__title">Choose a CSV or JSON file</span>
						<span class="rc-dropzone__hint">You will pick the title and ID columns in the next step.</span>
						<input id="rc-import-file" class="rc-dropzone__input" type="file" name="import_file" accept=".csv,.json" required>
					</label>
					<div class="rc-actions">
						<input type="submit" name="submit_file" value="Upload and continue" class="button button-primary button-hero">
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the mapping HTML for the file columns.
	 *
	 * @param array  $headers   List of file headers.
	 * @param string $import_id State id to get state.
	 *
	 * @return void
	 */
	public function mapping_html( array $headers, string $import_id ) {
		?>
		<div class="wrap rc-app">
			<?php $this->page_header( 2, 'Map columns', 'Tell Relay Core which columns hold the listing title and its unique ID.' ); ?>
			<div class="rc-card rc-form-card">
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field( 'relay_core_mapping', 'relay_core_nonce' ); ?>
					<input type="hidden" name="import_id" value="<?php echo esc_attr( $import_id ); ?>">
					<input type="hidden" name="page_step_num" value="2">

					<div class="rc-field">
						<label class="rc-field__label" for="select_post_title">Post title <span class="rc-required" aria-hidden="true">*</span></label>
						<select id="select_post_title" name="select_post_title" required>
							<option value="">Select a column</option>
							<?php foreach ( $headers as $header ) : ?>
								<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="rc-field__hint">Becomes the listing title. Rows with this column empty are skipped.</p>
					</div>

					<div class="rc-field">
						<label class="rc-field__label" for="select_external_id">External ID <span class="rc-required" aria-hidden="true">*</span></label>
						<select id="select_external_id" name="select_external_id" required>
							<option value="">Select a column</option>
							<?php foreach ( $headers as $header ) : ?>
								<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="rc-field__hint">A unique value per row. Importing the same ID again updates that listing.</p>
					</div>

					<p class="rc-field__hint">All other columns are saved as listing meta.</p>

					<div class="rc-actions">
						<input type="submit" name="submit_mapping" value="Start import" class="button button-primary button-hero">
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the admin page and handles form submission.
	 *
	 * @return void
	 */
	public function render_page() {
		$temp_file_path = '';

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not capable to do this action!' );
		}

		// Nonce verification for both forms at the top.
		if ( isset( $_POST['submit_file'] ) || isset( $_POST['submit_mapping'] ) ) {
			$nonce = isset( $_POST['relay_core_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['relay_core_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'relay_core_upload' ) && ! wp_verify_nonce( $nonce, 'relay_core_mapping' ) ) {
				wp_die( 'Nonce verification failed!' );
			}
		}

		if ( isset( $_POST['submit_file'] ) ) {
			$final_target   = $this->handle_file_verifier();
			$temp_file_path = $final_target;
		}

		if ( null === $temp_file_path ) {
			return;
		}

		if ( isset( $_POST['submit_mapping'] ) ) {
			$this->handle_file_upload();
			return;
		}

		if ( ! isset( $_POST['submit_file'] ) && ! isset( $_POST['submit_mapping'] ) ) {
			$this->import_html();
			return;
		}

		if ( isset( $_POST['submit_mapping'] ) || isset( $_POST['submit_file'] ) ) {
			if ( isset( $_POST['page_step_num'] ) ) {
				$page_step_num = intval( $_POST['page_step_num'] );
				if ( 1 === $page_step_num ) {
					$reader = $this->get_reader( $temp_file_path );
					if ( null === $reader ) {
						echo '<div class="notice notice-error"><p>Unsupported file type.</p></div>';
						return;
					}
					$headers = $reader->read_headers();
					if ( empty( $headers ) ) {
						if ( file_exists( $temp_file_path ) ) {
							wp_delete_file( $temp_file_path );
						}
						echo '<div class="notice notice-error"><p>Could not read headers from the file. The file may be empty, invalid, or contain nested data (which is not supported).</p></div>';
						return;
					}
					$import_state = new ImportState();
					$import_id    = $import_state->create(
						array(
							'file_path' => $temp_file_path,
							'status'    => 'pending',
							'total'     => $reader->count_rows(),
						)
					);
					$this->mapping_html( $headers, $import_id );
					return;
				}
			}
		}
	}

	/**
	 * Handles the file upload and validation.
	 *
	 * @return string|void The path to the uploaded file or void on error.
	 */
	public function handle_file_verifier() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not capable to do this action!' );
		}

		check_admin_referer( 'relay_core_upload', 'relay_core_nonce' );

		if ( ! isset( $_FILES['import_file'] ) ) {
			echo '<div class="notice notice-error"><p>No file uploaded.</p></div>';
			return null;
		}

		$file_validator = new FileValidator();
		$final_target   = $file_validator->validate( $_FILES['import_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- FileValidator sanitizes and validates each key of the array.

		if ( is_array( $final_target ) && isset( $final_target['success'] ) && false === $final_target['success'] ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $final_target['message'] ) . '</p></div>';
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is server-generated, not user input.
		$tmp_file_path = isset( $_FILES['import_file']['tmp_name'] ) ? $_FILES['import_file']['tmp_name'] : '';
		// File Save to uploads directory.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/relay_temp_temp';
		wp_mkdir_p( $temp_dir );

		$file_name    = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
		$original_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		$name         = 'relay' . bin2hex( random_bytes( 8 ) ) . '.' . $original_ext;
		$destination  = $temp_dir . '/' . $name;

		if ( ! move_uploaded_file( $tmp_file_path, $destination ) ) {
			echo '<div class="notice notice-error"><p>Could not save file.</p></div>';
			return null;
		}

		return $destination;
	}

	/**
	 * Handles the file upload and imports listings.
	 *
	 * @return void
	 */
	public function handle_file_upload() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not capable to do this action!' );
		}

		check_admin_referer( 'relay_core_mapping', 'relay_core_nonce' );

		$import_id = isset( $_POST['import_id'] ) ? sanitize_text_field( wp_unslash( $_POST['import_id'] ) ) : '';

		$import_state = new ImportState();
		$state        = $import_state->get( $import_id );

		if ( null === $state ) {
			echo '<div class="notice notice-error"><p>Id is not correct. Please fill the form again!</p></div>';
			return;
		}

		$file_path = $state['file_path'];

		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			echo '<div class="notice notice-error"><p>File not found.</p></div>';
			return;
		}

		$reader = $this->get_reader( $file_path );

		if ( null === $reader ) {
			echo '<div class="notice notice-error"><p>Unsupported file type.</p></div>';
			return;
		}

		$mapping = array(
			'post_title'  => isset( $_POST['select_post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['select_post_title'] ) ) : '',
			'external_id' => isset( $_POST['select_external_id'] ) ? sanitize_text_field( wp_unslash( $_POST['select_external_id'] ) ) : '',
		);

		$state_changes = array(
			'mapping' => $mapping,
			'offset'  => 0,
			'created' => 0,
			'updated' => 0,
			'failed'  => 0,
			'status'  => 'running',
		);

		if ( 'pending' === $state['status'] ) {
			$update_state = $import_state->update( $import_id, $state_changes );
			if ( false === $update_state ) {
				echo '<div class="notice notice-error"><p>Your ID might be wrong. File upload the file again!</p></div>';
				return;
			}

			$this->background_import->start( $import_id );
		}

		$ajax_response = array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'relay_core_import_status' ),
			'importId' => $import_id,
		);

		wp_enqueue_script( 'relay-core-import', plugins_url( 'assets/js/import.js', RELAY_CORE_FILE ), array(), '0.2.0', true );
		wp_localize_script( 'relay-core-import', 'relayCoreImport', $ajax_response );

		?>

		<div class="wrap rc-app">
			<?php $this->page_header( 3, 'Importing', 'You can leave this page. The import keeps running in the background.' ); ?>
			<div class="rc-card rc-form-card rc-progress-card" id="relay-core-progress-card">
				<div class="rc-progress" role="presentation">
					<span class="rc-progress__bar" id="relay-core-progress-bar"></span>
				</div>
				<p id="relay-core-progress" aria-live="polite">Starting…</p>
				<div class="rc-actions">
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=listing&page=relay-user-history' ) ); ?>">View import history</a>
					<a class="button-link" href="<?php echo esc_url( admin_url( 'edit.php?post_type=listing&page=relay-import' ) ); ?>">Import another file</a>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Returns the appropriate reader based on file extension.
	 *
	 * @param string $file_path Path to the uploaded file.
	 *
	 * @return CsvReader|JsonReader|null The reader instance or null if unsupported.
	 */
	private function get_reader( string $file_path ) {
		$check_file = new ReaderFactory();
		return $check_file->make( $file_path );
	}
}
