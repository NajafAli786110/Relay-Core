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

/**
 * Class UploadPage
 *
 * Handles the admin page for uploading CSV files.
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
	 * Renders the CSV upload form HTML.
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
				<input type="file" name="csv_file" accept=".csv" required>
				<input type="submit" name="submit_csv" value="Upload CSV">
			</form>
		</div>
		<?php
	}

	/**
	 * Renders the mapping HTML for the CSV headers.
	 *
	 * @param array  $headers   List of CSV headers.
	 * @param string $file_path Path to the uploaded CSV file.
	 *
	 * @return void
	 */
	public function mapping_html( array $headers, string $file_path ) {
		?>
		<div class="wrap">
			<h1>Map CSV Columns</h1>

			<form method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'relay_core_mapping', 'relay_core_nonce' ); ?>
				<input type="hidden" name="csv_file_path" value="<?php echo esc_attr( $file_path ); ?>">
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
		if ( isset( $_POST['submit_csv'] ) || isset( $_POST['submit_mapping'] ) ) {
			$nonce = isset( $_POST['relay_core_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['relay_core_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'relay_core_upload' ) && ! wp_verify_nonce( $nonce, 'relay_core_mapping' ) ) {
				wp_die( 'Nonce verification failed!' );
			}
		}

		if ( isset( $_POST['submit_csv'] ) ) {
			$final_target   = $this->handle_file_verifier();
			$temp_file_path = $final_target;
		}

		if ( null === $temp_file_path ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on the same line.
		if ( isset( $_POST['submit_mapping'] ) ) {
			$this->handle_csv_upload();
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on the same line.
		if ( ! isset( $_POST['submit_csv'] ) && ! isset( $_POST['submit_mapping'] ) ) {
			$this->import_html();
			return;
		}

		if ( isset( $_POST['submit_mapping'] ) || isset( $_POST['submit_csv'] ) ) {
			if ( isset( $_POST['page_step_num'] ) ) {
				$page_step_num = intval( $_POST['page_step_num'] );
				if ( 1 === $page_step_num ) {
					$csv_reader = new CsvReader( $temp_file_path );
					$headers    = $csv_reader->read_headers();
					$this->mapping_html( $headers, $temp_file_path );
					return;
				}
			}
		}
	}

	/**
	 * Handles the CSV file upload and validation.
	 *
	 * @return string|void The path to the uploaded CSV file or void on error.
	 */
	public function handle_file_verifier() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You are not capable to do this action!' );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified via check_admin_referer() on this same line.
		if ( ! isset( $_POST['submit_csv'] ) || ! check_admin_referer( 'relay_core_upload', 'relay_core_nonce' ) ) {
			wp_die( 'Nonce verification failed!' );
		}

		if ( ! isset( $_FILES['csv_file'] ) ) {
			echo '<div class="notice notice-error"><p>No file uploaded.</p></div>';
			return null;
		}

		$file_validator = new FileValidator();
		$final_target   = $file_validator->validate( $_FILES['csv_file'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- csv_file is server-generated, not user input.

		if ( is_array( $final_target ) && isset( $final_target['success'] ) && false === $final_target['success'] ) {
			echo '<div class="notice notice-error"><p>' . esc_html( $final_target['message'] ) . '</p></div>';
			return null;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- tmp_name is server-generated, not user input.
		$tmp_file_path = isset( $_FILES['csv_file']['tmp_name'] ) ? $_FILES['csv_file']['tmp_name'] : '';
		// File Save to uploads directory.
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/relay_temp_temp';
		wp_mkdir_p( $temp_dir );

		$name        = 'relay' . bin2hex( random_bytes( 8 ) ) . '.csv';
		$destination = $temp_dir . '/' . $name;

		if ( ! move_uploaded_file( $tmp_file_path, $destination ) ) {
			echo '<div class="notice notice-error"><p>Could not save file.</p></div>';
			return null;
		}

		return $destination;
	}

	/**
	 * Handles the CSV file upload and imports listings.
	 *
	 * @return void
	 */
	public function handle_csv_upload() {
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

		$file_path = isset( $_POST['csv_file_path'] ) ? sanitize_text_field( wp_unslash( $_POST['csv_file_path'] ) ) : '';

		if ( '' === $file_path || ! file_exists( $file_path ) ) {
			echo '<div class="notice notice-error"><p>CSV file not found.</p></div>';
			return;
		}

		$csv_reader = new CsvReader( $file_path );
		$rows       = $csv_reader->read_rows();

		if ( empty( $rows ) ) {
			echo '<div class="notice notice-error"><p>Could not read CSV content.</p></div>';
			return;
		}

		$mapping = array(
			'post_title'  => $_POST['select_post_title'],
			'external_id' => $_POST['select_external_id'],
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
}