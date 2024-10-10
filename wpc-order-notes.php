<?php
/*
Plugin Name: WooCommerce Manual Order Notes
Description: Adds and sends woocommerce manual notes into email of admins
Version: 1.0
Author: Yeabsira
*/

defined( 'ABSPATH' ) || exit;

! defined( 'WOOON_VERSION' ) && define( 'WOOON_VERSION', '1.5.2' );
! defined( 'WOOON_LITE' ) && define( 'WOOON_LITE', __FILE__ );
! defined( 'WOOON_FILE' ) && define( 'WOOON_FILE', __FILE__ );
! defined( 'WOOON_URI' ) && define( 'WOOON_URI', plugin_dir_url( __FILE__ ) );
! defined( 'WOOON_REVIEWS' ) && define( 'WOOON_REVIEWS', 'https://wordpress.org/support/plugin/woo-order-notes/reviews/?filter=5' );
! defined( 'WOOON_CHANGELOG' ) && define( 'WOOON_CHANGELOG', 'https://wordpress.org/plugins/woo-order-notes/#developers' );
! defined( 'WOOON_DISCUSSION' ) && define( 'WOOON_DISCUSSION', 'https://wordpress.org/support/plugin/woo-order-notes' );
! defined( 'WPC_URI' ) && define( 'WPC_URI', WOOON_URI );

include 'includes/dashboard/wpc-dashboard.php';
include 'includes/kit/wpc-kit.php';
include 'includes/hpos.php';

if ( ! class_exists( 'WPCleverWooon' ) ) {
	class WPCleverWooon {
		protected static $instance = null;

		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		function __construct() {
			// textdomain
			add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );

			// menu
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );

			// enqueue
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

			// AJAX
			add_action( 'wp_ajax_wooon_quickview', [ $this, 'ajax_quickview' ] );
			add_action( 'wp_ajax_wooon_update_order_note', [ $this, 'ajax_update_order_note' ] );
			add_action( 'wp_ajax_wooon_add_order_note', [ $this, 'ajax_add_order_note' ] );

			// footer
			add_action( 'admin_footer', [ $this, 'admin_footer' ] );

			// settings link
			add_filter( 'plugin_action_links', [ $this, 'settings_link' ], 10, 2 );
			add_filter( 'plugin_row_meta', [ $this, 'row_meta' ], 10, 2 );

			// order column
			add_filter( 'manage_shop_order_posts_columns', [ $this, 'shop_order_columns' ], 99 );
			add_action( 'manage_shop_order_posts_custom_column', [ $this, 'shop_order_columns_content' ], 99, 2 );

			// order column for HPOS
			add_filter( 'woocommerce_shop_order_list_table_columns', [ $this, 'shop_order_columns' ], 99 );
			add_action( 'woocommerce_shop_order_list_table_custom_column', [
				$this,
				'shop_order_columns_content'
			], 99, 2 );

			add_action('woocommerce_email_order_meta', 'woo_add_manual_order_notes_to_email', 10, 4);
		}

		function load_textdomain() {
			load_plugin_textdomain( 'woo-order-notes', false, basename( __DIR__ ) . '/languages/' );
		}

		function admin_menu() {
			add_submenu_page( 'wpclever', esc_html__( 'WPC Order Notes', 'woo-order-notes' ), esc_html__( 'Order Notes', 'woo-order-notes' ), 'manage_options', 'wpclever-wooon', [
				$this,
				'admin_menu_content'
			] );
			add_submenu_page( 'woocommerce', esc_html__( 'Notes', 'woo-order-notes' ), esc_html__( 'Notes', 'woo-order-notes' ), 'manage_options', 'wpc-order-notes', [
				&$this,
				'notes_content'
			] );
		}

		function admin_menu_content() {
			$active_tab = $_GET['tab'] ?? 'how';
			?>
            <div class="wpclever_settings_page wrap">
                <h1 class="wpclever_settings_page_title"><?php echo esc_html__( 'WPC Order Notes for WooCommerce', 'woo-order-notes' ) . ' ' . esc_html( WOOON_VERSION ); ?></h1>
                <div class="wpclever_settings_page_desc about-text">
                    <p>
						<?php printf( /* translators: stars */ esc_html__( 'Thank you for using our plugin! If you are satisfied, please reward it a full five-star %s rating.', 'woo-order-notes' ), '<span style="color:#ffb900">&#9733;&#9733;&#9733;&#9733;&#9733;</span>' ); ?>
                        <br/>
                        <a href="<?php echo esc_url( WOOON_REVIEWS ); ?>" target="_blank"><?php esc_html_e( 'Reviews', 'woo-order-notes' ); ?></a> |
                        <a href="<?php echo esc_url( WOOON_CHANGELOG ); ?>" target="_blank"><?php esc_html_e( 'Changelog', 'woo-order-notes' ); ?></a> |
                        <a href="<?php echo esc_url( WOOON_DISCUSSION ); ?>" target="_blank"><?php esc_html_e( 'Discussion', 'woo-order-notes' ); ?></a>
                    </p>
                </div>
                <div class="wpclever_settings_page_nav">
                    <h2 class="nav-tab-wrapper">
                        <a href="?page=wpclever-wooon&tab=how" class="<?php echo esc_attr( $active_tab == 'how' ? 'nav-tab nav-tab-active' : 'nav-tab' ); ?>">
							<?php esc_html_e( 'How to use?', 'woo-order-notes' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpclever-kit' ) ); ?>" class="nav-tab">
							<?php esc_html_e( 'Essential Kit', 'woo-order-notes' ); ?>
                        </a>
                    </h2>
                </div>
                <div class="wpclever_settings_page_content">
					<?php if ( $active_tab === 'how' ) { ?>
                        <div class="wpclever_settings_page_content_text">
                            <p><?php esc_html_e( '1. View all order notes', 'woo-order-notes' ); ?></p>
                            <p>
                                <img src="<?php echo esc_url( WOOON_URI . 'assets/images/how-01.jpg' ); ?>" alt=""/>
                            </p>
                            <p><?php esc_html_e( '2. Quick view all notes of an order', 'woo-order-notes' ); ?></p>
                            <p>
                                <img src="<?php echo esc_url( WOOON_URI . 'assets/images/how-02.jpg' ); ?>" alt=""/>
                            </p>
                            <p><?php esc_html_e( '3. Open the order page', 'woo-order-notes' ); ?></p>
                            <p>
                                <img src="<?php echo esc_url( WOOON_URI . 'assets/images/how-03.jpg' ); ?>" alt=""/>
                            </p>
                        </div>
					<?php } ?>
                </div><!-- /.wpclever_settings_page_content -->
            </div>
			<?php
		}

		function settings_link( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( __FILE__ );
			}

			if ( $plugin == $file ) {
				$settings = '<a href="' . esc_url( admin_url( 'admin.php?page=wpclever-wooon' ) ) . '">' . esc_html__( 'How to use?', 'woo-order-notes' ) . '</a>';
				array_unshift( $links, $settings );
			}

			return (array) $links;
		}

		function row_meta( $links, $file ) {
			static $plugin;

			if ( ! isset( $plugin ) ) {
				$plugin = plugin_basename( __FILE__ );
			}

			if ( $plugin == $file ) {
				$row_meta = [
					'support' => '<a href="' . esc_url( WOOON_DISCUSSION ) . '" target="_blank">' . esc_html__( 'Community support', 'woo-order-notes' ) . '</a>',
				];

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		function admin_enqueue_scripts() {
			wp_enqueue_script( 'wc-admin-order-meta-boxes', WC()->plugin_url() . '/assets/js/admin/meta-boxes-order.js', [
				'wc-admin-meta-boxes',
				'wc-backbone-modal',
				'selectWoo',
				'wc-clipboard'
			], WOOON_VERSION, true );

			wp_enqueue_style( 'wooon-backend', WOOON_URI . 'assets/css/backend.css', [ 'woocommerce_admin_styles' ], WOOON_VERSION );
			wp_enqueue_script( 'wooon-backend', WOOON_URI . 'assets/js/backend.js', [
				'jquery',
				'jquery-ui-dialog'
			], WOOON_VERSION, true );
			wp_localize_script( 'wooon-backend', 'wooon_vars', [
					'nonce' => wp_create_nonce( 'wooon_nonce' )
				]
			);
		}

		function notes_count() {
			global $wpdb;
			$total = 0;
			$count = $wpdb->get_results( "
					SELECT comment_approved, COUNT(*) AS num_comments
					FROM {$wpdb->comments}
					WHERE comment_type IN ('order_note')
					GROUP BY comment_approved
				", ARRAY_A );

			foreach ( (array) $count as $row ) {
				// Don't count post-trashed toward totals.
				if ( 'post-trashed' !== $row['comment_approved'] && 'trash' !== $row['comment_approved'] ) {
					$total += $row['num_comments'];
				}
			}

			return $total;
		}

		function notes_content() {
			$search      = sanitize_text_field( $_GET['search'] ?? '' );
			$number      = 20;
			$total_notes = self::notes_count();
			$total_pages = floor( $total_notes / $number ) + 1;
			$paged       = absint( $_GET['paged'] ?? 1 );
			$offset      = $number * ( $paged - 1 );
			?>
            <div class="wrap">
                <h1 class="wp-heading-inline"><?php esc_html_e( 'Order Notes', 'woo-order-notes' ); ?></h1>
                <hr class="wp-header-end">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
                            <input type="hidden" name="page" value="wpc-order-notes"/> <label>
                                <input type="search" name="search" value="<?php echo esc_attr( $search ); ?>"/> </label>
                            <input type="submit" class="button" value="<?php esc_attr_e( 'Search', 'woo-order-notes' ); ?>"/>
                        </form>
                    </div>
                    <div class="tablenav-pages">
                        <div class="displaying-num">
							<?php printf( /* translators: counter */ esc_html__( '%1$d notes in %2$d pages', 'woo-order-notes' ), $total_notes, $total_pages ); ?>

                            <label> <select onchange="if (this.value) {window.location.href=this.value}">
									<?php
									for ( $i = 1; $i <= $total_pages; $i ++ ) {
										echo '<option value="' . admin_url( 'admin.php?page=wpc-order-notes&paged=' . $i ) . '" ' . ( $paged == $i ? 'selected' : '' ) . '>' . $i . '</option>';
									}
									?>
                                </select> </label>
                        </div>
                    </div>
                    <br class="clear">
                </div>
                <table class="wp-list-table widefat fixed striped posts">
                    <thead>
                    <tr>
                        <th scope="col" class="manage-column" style="width: 60px">
							<?php esc_html_e( 'Order', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Note', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Time', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Quick view', 'woo-order-notes' ); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody id="the-list">
					<?php
					$args = [
						'post_id' => 0,
						'orderby' => 'comment_ID',
						'order'   => 'DESC',
						'approve' => 'approve',
						'type'    => 'order_note',
						'number'  => $number,
						'offset'  => $offset,
						'search'  => $search,
					];

					remove_filter( 'comments_clauses', [ 'WC_Comments', 'exclude_order_comments' ] );
					$notes = get_comments( $args );
					add_filter( 'comments_clauses', [ 'WC_Comments', 'exclude_order_comments' ] );

					if ( $notes ) {
						foreach ( $notes as $note ) {
							$post_id = $note->comment_post_ID;
							$order   = wc_get_order( $post_id );

							if ( ! $order ) {
								continue;
							}

							$order_id = $order->get_order_number();
							?>
                            <tr>
                                <td style="width: 60px">
                                    <a href="<?php echo get_edit_post_link( $order_id ); ?>"><strong>#<?php echo esc_html( $order_id ); ?></strong></a>
                                </td>
                                <td>
									<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
                                </td>
                                <td>
									<?php
									printf( /* translators: date time */ esc_html__( '%1$s %2$s', 'woo-order-notes' ), date_i18n( wc_date_format(), strtotime( $note->comment_date ) ), date_i18n( wc_time_format(), strtotime( $note->comment_date ) ) );

									if ( $note->comment_author != 'WooCommerce' ) {
										printf( ' ' . /* translators: author */ esc_html__( 'by %s', 'woo-order-notes' ), $note->comment_author );
									}

									if ( get_comment_meta( $note->comment_ID, 'is_customer_note', true ) === '1' ) {
										echo ' <i class="dashicons dashicons-yes"></i> ' . esc_html__( 'Note to customer', 'woo-order-notes' );
									}
									?>
                                </td>
                                <td>
                                    <a href="#" class="wooon-quickview" data-order="<?php echo esc_attr( $order_id ); ?>" data-current="<?php echo esc_attr( $note->comment_ID ); ?>">
                                        <i class="dashicons dashicons-format-chat"></i> </a>
                                </td>
                            </tr>
							<?php
						}
					}
					?>
                    </tbody>
                    <tfoot>
                    <tr>
                        <th scope="col" class="manage-column" style="width: 60px">
							<?php esc_html_e( 'Order', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Note', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Time', 'woo-order-notes' ); ?>
                        </th>
                        <th scope="col" class="manage-column">
							<?php esc_html_e( 'Quick view', 'woo-order-notes' ); ?>
                        </th>
                    </tr>
                    </tfoot>
                </table>
                <div class="tablenav bottom">
                    <div class="alignleft actions">

                    </div>
                    <div class="tablenav-pages">
                        <div class="displaying-num">
							<?php printf( /* translators: counter */ esc_html__( '%1$d notes in %2$d pages', 'woo-order-notes' ), $total_notes, $total_pages ); ?>

                            <label> <select onchange="if (this.value) {window.location.href=this.value}">
									<?php
									for ( $i = 1; $i <= $total_pages; $i ++ ) {
										echo '<option value="' . admin_url( 'admin.php?page=wpc-order-notes&paged=' . $i ) . '" ' . ( $paged == $i ? 'selected' : '' ) . '>' . $i . '</option>';
									}
									?>
                                </select> </label>
                        </div>
                    </div>
                    <br class="clear">
                </div>
            </div>
			<?php
		}

		function ajax_quickview() {
			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wooon_nonce' ) ) {
				die( esc_html__( 'Permissions check failed!', 'woo-order-notes' ) );
			}

			$notes_html = '';

			if ( isset( $_POST['order'] ) ) {
				ob_start();
				echo '<div id="woocommerce-order-notes" data-id="' . esc_attr( $_POST['order'] ) . '">';
				$notes = wc_get_order_notes( [ 'order_id' => $_POST['order'] ] );
				include dirname( WC_PLUGIN_FILE ) . '/includes/admin/meta-boxes/views/html-order-notes.php';
				?>
                <div class="add_note">
                    <p>
                        <label for="add_order_note"><?php esc_html_e( 'Add note', 'woo-order-notes' ); ?><?php echo wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woo-order-notes' ) ); ?></label>
                        <textarea name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
                    </p>
                    <p>
                        <label for="order_note_type" class="screen-reader-text"><?php esc_html_e( 'Note type', 'woo-order-notes' ); ?></label>
                        <select name="order_note_type" id="order_note_type" style=" vertical-align: baseline">
                            <option value=""><?php esc_html_e( 'Private note', 'woo-order-notes' ); ?></option>
                            <option value="customer"><?php esc_html_e( 'Note to customer', 'woo-order-notes' ); ?></option>
                        </select>
                        <button type="button" style=" vertical-align: baseline" class="add_note button"><?php esc_html_e( 'Add', 'woo-order-notes' ); ?></button>
                    </p>
                </div>
				<?php
				echo '</div>';
				$notes_html = ob_get_clean();
			}

			echo $notes_html;
			wp_die();
		}

		function ajax_update_order_note() {
			$comment = [
				'comment_ID'      => sanitize_text_field( $_POST['note_id'] ),
				'comment_content' => sanitize_text_field( $_POST['note_content'] )
			];
			wp_update_comment( $comment );

			echo $_POST['note_content'];
			wp_die();
		}

		function ajax_add_order_note() {
			$ids              = explode( '.', $_POST['ids'] );
			$note_type        = wc_clean( wp_unslash( $_POST['note_type'] ) );
			$is_customer_note = ( 'customer' === $note_type ) ? 1 : 0;

			foreach ( $ids as $id ) {
				$order = wc_get_order( $id );

				if ( $order ) {
					$order->add_order_note( trim( $_POST['note'] ), $is_customer_note, true );
				}
			}

			wp_die();
		}

		function admin_footer() {
			?>
            <div class="wooon-dialog" id="wooon_dialog" style="display: none" title="<?php esc_html_e( 'Order Notes', 'woo-order-notes' ); ?>"></div>
			<?php
		}

		function shop_order_columns( $columns ) {
			$columns['wooon_latest']    = esc_html__( 'Latest Note', 'woo-order-notes' );
			$columns['wooon_quickview'] = esc_html__( 'Notes', 'woo-order-notes' );

			return $columns;
		}

		function shop_order_columns_content( $column, $order_id_or_obj ) {
			if ( is_numeric( $order_id_or_obj ) ) {
				$order_id = $order_id_or_obj;
			} else {
				$order_id = $order_id_or_obj->id;
			}

			if ( $column == 'wooon_latest' ) {
				$args = [
					'post_id' => (int) $order_id,
					'orderby' => 'comment_ID',
					'order'   => 'DESC',
					'approve' => 'approve',
					'type'    => 'order_note',
					'number'  => 1
				];
				remove_filter( 'comments_clauses', [ 'WC_Comments', 'exclude_order_comments' ] );
				$notes = get_comments( $args );
				add_filter( 'comments_clauses', [ 'WC_Comments', 'exclude_order_comments' ] );

				if ( $notes ) {
					foreach ( $notes as $note ) {
						?>
                        <div class="wooon_latest_note">
                            <div class="note_content">
								<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
                            </div>
                            <div class="note_meta">
								<?php
								printf( /* translators: date time */ esc_html__( '%1$s %2$s', 'woo-order-notes' ), date_i18n( wc_date_format(), strtotime( $note->comment_date ) ), date_i18n( wc_time_format(), strtotime( $note->comment_date ) ) );

								if ( $note->comment_author != 'WooCommerce' ) {
									printf( ' ' . /* translators: author */ esc_html__( 'by %s', 'woo-order-notes' ), $note->comment_author );
								}

								if ( get_comment_meta( $note->comment_ID, 'is_customer_note', true ) === '1' ) {
									echo ' <i class="dashicons dashicons-yes"></i> ' . esc_html__( 'Note to customer', 'woo-order-notes' );
								}
								?>
                            </div>
                        </div>
						<?php
					}
				}
			}

			if ( $column == 'wooon_quickview' ) {
				?>
                <a href="#" class="wooon-quickview" data-order="<?php echo esc_attr( $order_id ); ?>" data-current="0">
                    <i class="dashicons dashicons-format-chat"></i> </a>
				<?php
			}
		}

		function woo_add_manual_order_notes_to_email($order, $sent_to_admin, $plain_text, $email) {
			// Only proceed if the email is being sent to the admin
			if (!$sent_to_admin) {
				return; // Exit the function if it's not an admin email
			}
		
			// Get the list of admin users
			$admin_users = get_users(array(
				'role' => 'administrator',
				'fields' => array('user_login', 'display_name')
			));
		
			// Create a list of admin usernames and display names
			$admin_usernames = wp_list_pluck($admin_users, 'user_login');
			$admin_display_names = wp_list_pluck($admin_users, 'display_name');
		
			// Prepare arguments to fetch all order notes
			$args = array(
				'limit' => '', // No limit on the number of notes to retrieve
				'order_id' => $order->get_id(), // Fetch notes for the specific order ID
			);
			$notes = wc_get_order_notes($args); // Retrieve the order notes
		
			// Debugging: Check if notes are retrieved
			error_log('Order Notes: ' . print_r($notes, true));
		
			// If there are no notes, display a message
			if (empty($notes)) {
				echo '<li class="no-order-comment">There are no order notes</li>';
				return; // Exit the function if there are no notes
			}
		
			// Display the order notes with a specific heading and styling
			echo '<h4 style="font-family:Arial,sans-serif;font-size:120%;line-height:110%;color:red;">IMPORTANT Order Notes</h4>';
			echo '<ul class="order_notes">';
		
			// Loop through each note and print it if it is manually added by an admin
			foreach ($notes as $note) {
				// Check if the note was added by an admin
				if (isset($note->added_by) && (in_array($note->added_by, $admin_usernames) || in_array($note->added_by, $admin_display_names))) {
					// Debugging: Check note details
					error_log('Note ID: ' . $note->id . ', Content: ' . $note->content);
		
					// Determine the classes for the note element
					$note_classes = $note->customer_note ? array('customer-note', 'note') : array('note');
					?>
					<li rel="<?php echo absint($note->id); ?>" class="<?php echo implode(' ', $note_classes); ?>">
						<div class="note_content">
							<?php
							// Output the note content, safely formatted
							echo wpautop(wptexturize(wp_kses_post($note->content)));
							?>
						</div>
					</li>
					<?php
				} else {
					// Debugging: Note not added by an admin
					error_log('Note not added by admin or missing added_by property. Note ID: ' . $note->id);
				}
			}
			echo '</ul>';
		}
	}

	return WPCleverWooon::instance();
}