<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPCleverMenu' ) ) {
	class WPCleverMenu {
		function __construct() {
			// do nothing, moved to WPCleverDashboard
		}
	}

	new WPCleverMenu();
}

if ( ! class_exists( 'WPCleverDashboard' ) ) {
	class WPCleverDashboard {
		function __construct() {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		}

		function enqueue_scripts() {
			wp_enqueue_style( 'wpc-dashboard', WPC_URI . 'includes/dashboard/css/dashboard.css' );
			wp_enqueue_script( 'wpc-dashboard', WPC_URI . 'includes/dashboard/js/backend.js', [ 'jquery' ] );
			wp_localize_script( 'wpc-dashboard', 'wpc_dashboard_vars', [
					'nonce' => wp_create_nonce( 'wpc_dashboard' ),
				]
			);
		}

		function admin_menu() {
			// Correct parameters for add_menu_page
			add_menu_page(
				'WPC Dashboard', // Page title
				'WPC Dashboard', // Menu title
				'manage_options', // Capability
				'wpc-dashboard', // Menu slug
				'', // Callback (optional)
				WPC_URI . 'includes/dashboard/images/wpc-icon.svg', // Icon URL
				26 // Position
			);

			// Correct parameters for add_submenu_page
			add_submenu_page(
				'wpc-dashboard', // Parent slug
				'Submenu Title', // Page title
				'Submenu Title', // Menu title
				'manage_options', // Capability
				'wpc-submenu', // Menu slug
				'' // Callback (optional)
			);
		}
	}

	new WPCleverDashboard();
}
