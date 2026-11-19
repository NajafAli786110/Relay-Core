<?php
/**
 * Logs admin page.
 *
 * @package EngineWP\RelayCore\Admin
 */

namespace EngineWP\RelayCore\Admin;

/**
 * Adds the Logs submenu page and loads the React dashboard on it.
 */
class LogsPage {

	/**
	 * Screen name returned by add_submenu_page(), used to load the script only on this page.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Registers the Logs submenu under Listings.
	 *
	 * @return void
	 */
	public function register_page_menu() {
		$this->hook = add_submenu_page( 'edit.php?post_type=listing', 'Logs Dashboard | Relay Core', 'History', 'manage_options', 'relay-user-history', array( $this, 'render_logs_page' ) );
	}

	/**
	 * Loads the dashboard script on the Logs page only.
	 *
	 * Dependencies and version come from the asset file that the build generates.
	 *
	 * @param string $hook_suffix Screen name of the current admin page.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( $hook_suffix !== $this->hook ) {
			return;
		}

		$asset = require plugin_dir_path( RELAY_CORE_FILE ) . 'build/app.asset.php';
		wp_enqueue_script( 'relay-user-history', plugins_url( '/build/app.js', RELAY_CORE_FILE ), $asset['dependencies'], $asset['version'], true );
	}

	/**
	 * Prints the page wrapper and the root element React renders into.
	 *
	 * @return void
	 */
	public function render_logs_page() {
		?>
		<div class="wrap">
			<h1>Relay Core Logs Dashboard</h1>
			<div id="relay-user-history"></div>
		</div>
		<?php
	}
}
