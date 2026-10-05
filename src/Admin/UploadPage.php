<?php
/**
 * Admin Upload Page class.
 *
 * @package EngineWP\RelayCore\Admin
 */

namespace EngineWP\RelayCore\Admin;

use EngineWP\RelayCore\Import\CsvReader;
use EngineWP\RelayCore\Import\ListingValidator;
use EngineWP\RelayCore\Import\ListingImporter;
use EngineWP\RelayCore\Import\FileValidator;
use EngineWP\RelayCore\Import\ColumnMapper;
use EngineWP\RelayCore\Import\JsonReader;

/**
 * Class UploadPage
 *
 * Handles the admin page for uploading files.
 *
 * @package EngineWP\RelayCore\Admin
 */
class UploadPage {


	/**
	 * Registers the admin menu page.
	 */
	public function register_menu() {
		add_submenu_page( 'edit.php?post_type=listing', 'Bulk Data Import | Relay', 'Bulk Import', 'manage_options', 'relay-import', array( $this, 'render_page' ) );
	}

	/**
	 * Renders the file upload form HTML.
	 *
	 * @return void
	 */
	public function import_html() {
		?>
		<div class="wrap">
			<h1>Relay Core Data Upload</h1>
			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'relay_core_upload', 'relay_core_nonce' ); ?>
				<input type="hidden" name="page_step_num" value="1">
				<input type="file" name="import_file" accept=".csv,.json" required>
				<input type="submit" name="submit_file" value="Upload File">
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the mapping HTML for the file columns.
	 *
	 * @param array  $headers   List of file headers.
	 * @param string $file_path Path to the uploaded file.
	 *
	 * @return void
	 */
	public function mapping_html( array $headers, string $file_path ) {
		?>
		<div class="wrap">
			<h1>Map File Columns</h1>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'relay_core_mapping', 'relay_core_nonce' ); ?>
				<input type="hidden" name="source_file_path" value="<?php echo esc_attr( $file_path ); ?>">
				<input type="hidden" name="page_step_num" value="2">
				<label for="select_post_title">Select Post title: </label>
				<select name="select_post_title" required>
					<option value="">--Select Post title--</option>
					<?php foreach ( $headers as $header ) : ?>
						<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
					<?php endforeach; ?>
				</select>
				<br><br>

				<label for="select_external_id">Select External ID: </label>
				<select name="select_external_id" required>
					<option value="">--Select External ID--</option>
					<?php foreach ( $headers as $header ) : ?>
						<option value="<?php echo esc_attr( $header ); ?>"><?php echo esc_html( $header ); ?></option>
					<?php endforeach; ?>
				</select>
				<br><br>

				<input type="submit" name="submit_mapping" value="Submit Mapping">
			</form>
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

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on the same line.
		if ( isset( $_POST['submit_mapping'] ) ) {
			$this->handle_file_upload();
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on the same line.
		if ( ! isset( $_POST['submit_file'] ) && ! isset( $_POST['submit_mapping'] ) ) {
			$this->import_html();
			return;
		}

		if ( isset( $_POST['submit_mapping'] ) || isset( $_POST['submit_file'] ) ) {
			if ( isset( $_POST['page_step_num'] ) ) {
				$page_step_num = intval( $_POST['page_step_num'] );
				if ( 1 === $page_step_num ) {
					$file_ext = strtolower( pathinfo( $temp_file_path, PATHINFO_EXTENSION ) );
					$reader   = $this->get_reader( $temp_file_path, $file_ext );
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
					$this->mapping_html( $headers, $temp_file_path );
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

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on this same line.
		if ( ! isset( $_POST['submit_file'] ) || ! check_admin_referer( 'relay_core_upload', 'relay_core_nonce' ) ) {
			wp_die( 'Nonce verification failed!' );
		}

		if ( ! isset( $_FILES['import_file'] ) ) {
			echo '<div class="notice notice-error"><p>No file uploaded.</p></div>';
			return null;
		}

		$file_validator = new FileValidator();
		$final_target   = $file_validator->validate( $_FILES['import_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- import_file is server-generated, not user input.

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

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on this same line.
		if ( ! isset( $_POST['submit_mapping'] ) || ! check_admin_referer( 'relay_core_mapping', 'relay_core_nonce' ) ) {
			wp_die( 'Nonce verification failed!' );
		}

		$validator = new ListingValidator();
		$importer  = new ListingImporter();
		$mapper    = new ColumnMapper();

		$file_path = isset( $_POST['source_file_path'] ) ? sanitize_text_field( wp_unslash( $_POST['source_file_path'] ) ) : '';

		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			echo '<div class="notice notice-error"><p>File not found.</p></div>';
			return;
		}

		$file_ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
		$reader   = $this->get_reader( $file_path, $file_ext );

		if ( null === $reader ) {
			echo '<div class="notice notice-error"><p>Unsupported file type.</p></div>';
			return;
		}

		$rows = $reader->read_rows();

		if ( empty( $rows ) ) {
			echo '<div class="notice notice-error"><p>Could not read file content.</p></div>';
			return;
		}

		$mapping = array(
			'post_title'  => isset( $_POST['select_post_title'] ) ? sanitize_text_field( wp_unslash( $_POST['select_post_title'] ) ) : '',
			'external_id' => isset( $_POST['select_external_id'] ) ? sanitize_text_field( wp_unslash( $_POST['select_external_id'] ) ) : '',
		);

		$mapped = $mapper->map( $rows, $mapping );

		$error_log     = array();
		$created_count = 0;
		$updated_count = 0;

		foreach ( $mapped as $entry ) {
			$validation_result = $validator->validate( $entry );
			$validation        = $validation_result[0];

			if ( false === $validation['valid'] ) {
				$error_log[] = array(
					'row_number' => $validation['error']['row_number'],
					'message'    => $validation['error']['message'],
				);
				continue;
			}

			$import_result = $importer->import( $entry );

			if ( true === $import_result['success'] ) {
				if ( 'created' === $import_result['action'] ) {
					++$created_count;
				} elseif ( 'updated' === $import_result['action'] ) {
					++$updated_count;
				}
			} else {
				$error_log[] = array(
					'row_number' => $entry['row_number'],
					'message'    => $import_result['error'],
				);
			}
		}

		// Summary.
		echo '<div class="notice notice-success"><p>Import complete! Created: ' . esc_html( $created_count ) . ', Updated: ' . esc_html( $updated_count ) . '</p></div>';

		if ( file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}

		if ( ! empty( $error_log ) ) {
			echo '<div class="notice notice-warning"><ul>';
			foreach ( $error_log as $err ) {
				echo '<li>Row ' . esc_html( $err['row_number'] ) . ': ' . esc_html( $err['message'] ) . '</li>';
			}
			echo '</ul></div>';
		}
	}

	/**
	 * Returns the appropriate reader based on file extension.
	 *
	 * @param string $file_path Path to the uploaded file.
	 * @param string $file_ext  File extension (csv or json).
	 *
	 * @return CsvReader|JsonReader|null The reader instance or null if unsupported.
	 */
	private function get_reader( string $file_path, string $file_ext ) {
		if ( 'csv' === $file_ext ) {
			return new CsvReader( $file_path );
		} elseif ( 'json' === $file_ext ) {
			return new JsonReader( $file_path );
		}

		return null;
	}
}
