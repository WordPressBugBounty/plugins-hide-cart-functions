<?php
/**
 * Hide Cart Functions Admin
 *
 * @package Hide_Cart_Functions
 */

namespace Artiosmedia\WC_Purchase_Customization_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use hwcf_List;

if (!class_exists('hwcf_admin')) {
	class hwcf_Admin {
		const MENU_SLUG = 'woocommerce';

		// class instance
		public static $instance;

		// WP_List_Table object
		public $terms_table;

		/**
		 * Constructor
		 *
		 * @return void
		 * @author Olatechpro
		 */
		public function __construct() {
			if (!is_admin()) {
				return;
			}
			add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
			// admin menu - priority 70 to run after WooCommerce registers all items
			add_action('admin_menu', [$this, 'admin_menu'], 99);
			//add settings page to plugin activation menu
			add_filter('plugin_action_links_' . HWCF_GLOBAl_BASE_NAME, [$this, 'plugin_settings_link']);
			//add donate link to plugim row
			add_filter('plugin_row_meta', [$this, 'add_description_link'], 10, 2);
			//add more info link to plugin row
			add_filter('plugin_row_meta', [$this, 'add_details_link'], 10, 4);
			//admin scripts
			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
			//plugin review admin notice
			add_action("admin_notices", [$this, "admin_notice"]);
			//plugin review admin notice ajax handler
			add_action("wp_ajax_hwcf_dismiss_notice", [$this, "dismiss_notice"]);
			//plugin data delete on uninstall ajax handler
			add_action("wp_ajax_hwcf_delete_on_uninstall", [$this, "hwcf_delete_on_uninstall_callback"]);
			//cripple bots ajax handler
			add_action("wp_ajax_hwcf_cripple_bots", [$this, "hwcf_cripple_bots_callback"]);
			//disable purchases ajax handler
			add_action("wp_ajax_hwcf_disable_purchases", [$this, "hwcf_disable_purchases_callback"]);
			//support button notification
			add_action("wp_ajax_hwcf_support_notification", [$this, "hwcf_support_notification"]);
			//installation/upgrade code
			add_action("admin_init", [$this, "admin_init"]);

			//woocommerce product search field
			add_action("wp_ajax_custom_product_search", [$this, "custom_product_search"]);
		}

		public static function set_screen($status, $option, $value) {
			return $value;
		}

		/** 
		 * Singleton instance
		 */
		public static function get_instance() {
			if (!isset(self::$instance)) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Installation/upgrade code
		 * 
		 * @since    1.0.0
		 */
		public function admin_init() {
			if (!get_option("hwcf_version_1_0_0_installed")) {
				update_option("hwcf_notice_dismiss", gmdate('Y-m-d', strtotime('+30 days')));
				update_option("hwcf_version_1_0_0_installed", 1);
			}
			
			// Migration: Rename "Hide Cart Function" to "Function Rule" and migrate block_purchases (v1.2.16)
			if (!get_option("hwcf_migrated_1_2_16")) {
				$settings_data = get_option('hwcf_settings_data', array());
				if (!empty($settings_data) && is_array($settings_data)) {
					$updated = false;
					$block_purchases_enabled = false;
					foreach ($settings_data as $key => $rule) {
						// Migrate title
						if (isset($rule['hwcf_title']) && $rule['hwcf_title'] === 'Hide Cart Function') {
							$settings_data[$key]['hwcf_title'] = 'Function Rule';
							$updated = true;
						}
						// Check if any rule had block_purchases enabled
						if (isset($rule['hwcf_block_purchases']) && (int)$rule['hwcf_block_purchases'] === 1) {
							$block_purchases_enabled = true;
						}
					}
					if ($updated) {
						update_option('hwcf_settings_data', $settings_data);
					}
					// Migrate block_purchases to global option
					if ($block_purchases_enabled) {
						update_option('hwcf_disable_purchases', 1);
					}
				}
				update_option("hwcf_migrated_1_2_16", 1);
			}
		}

		/**
		 * Plugin data delete on uninstall ajax handler
		 * 
		 * @since    1.2.16
		 */
		public function hwcf_delete_on_uninstall_callback() {
			check_ajax_referer('hwcf_admin_nonce', 'nonce');
			
			if (current_user_can("manage_options")) {
				update_option("hwcf_delete_on_deactivation", isset($_POST["settings_action"]) ? (int)$_POST["settings_action"] : 0);
				wp_send_json(array("status" => true));
			}
			
			wp_send_json_error(['message' => 'Permission denied']);
		}

		/**
		 * Cripple bots ajax handler
		 * 
		 * @since    1.2.16
		 */
		public function hwcf_cripple_bots_callback() {
			check_ajax_referer('hwcf_admin_nonce', 'nonce');
			
			if (current_user_can("manage_options")) {
				update_option("hwcf_cripple_bots", isset($_POST["settings_action"]) ? (int)$_POST["settings_action"] : 0);
				wp_send_json(array("status" => true));
			}
			
			wp_send_json_error(['message' => 'Permission denied']);
		}

		/**
		 * Disable purchases ajax handler
		 * 
		 * @since    1.2.16
		 */
		public function hwcf_disable_purchases_callback() {
			check_ajax_referer('hwcf_admin_nonce', 'nonce');
			
			if (current_user_can("manage_options")) {
				update_option("hwcf_disable_purchases", isset($_POST["settings_action"]) ? (int)$_POST["settings_action"] : 0);
				wp_send_json(array("status" => true));
			}
			
			wp_send_json_error(['message' => 'Permission denied']);
		}

		/**
		 * Plugin review admin notice ajax handler
		 * 
		 * @since    1.0.0
		 */
		public function dismiss_notice() {
			check_ajax_referer('hwcf_admin_nonce', 'nonce');
			
			if (current_user_can("manage_options")) {
				if (!empty($_POST["dismissed_final"])) {
					update_option("hwcf_notice_dismiss", '1');
				} else {
					update_option("hwcf_notice_dismiss", gmdate('Y-m-d', strtotime('+30 days')));
				}
				wp_send_json(array("status" => true));
			}
			
			wp_send_json_error(['message' => 'Permission denied']);
		}

		/**
		 * Support button notification handler
		 * Sends email notification when user clicks Support button
		 */
		public function hwcf_support_notification() {
			check_ajax_referer('hwcf_admin_nonce', 'nonce');
			
			if (!current_user_can('manage_options')) {
				wp_send_json_error(['message' => 'Permission denied']);
				return;
			}
			
			global $wpdb;
			
			$site_url = home_url();
			$site_name = html_entity_decode(get_bloginfo('name'), ENT_QUOTES, 'UTF-8');
			$support_url = 'https://wordpress.org/support/plugin/hide-cart-functions/';
			
			// Environment info
			$theme = wp_get_theme();
			$parent_theme = $theme->parent() ? $theme->parent()->get('Name') . ' ' . $theme->parent()->get('Version') : 'None';
			$wc_version = defined('WC_VERSION') ? WC_VERSION : 'Not Active';
			$memory_limit = ini_get('memory_limit');
			$max_execution = ini_get('max_execution_time');
			$upload_max = ini_get('upload_max_filesize');
			$post_max = ini_get('post_max_size');
			$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown';
			$https = is_ssl() ? 'Yes' : 'No';
			$multisite = is_multisite() ? 'Yes' : 'No';
			
			// Active plugins
			$active_plugins = get_option('active_plugins', []);
			$plugin_list = [];
			foreach ($active_plugins as $plugin) {
				$plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
				if (!empty($plugin_data['Name'])) {
					$plugin_list[] = $plugin_data['Name'] . ' ' . $plugin_data['Version'];
				}
			}
			$plugins_formatted = implode(', ', $plugin_list);
			
			$to = 'contact@artiosmedia.com';
			$subject = 'HCF Support Request: ' . $site_name;
			
			$message = "A user has clicked the support button in Hide Cart Functions.\n\n";
			$message .= "Site: {$site_name}\n";
			$message .= "URL: {$site_url}\n";
			$message .= "Admin Email: " . get_option('admin_email') . "\n";
			$message .= "Time: " . current_time('mysql') . "\n\n";
			$message .= "── Environment ──────────────────────\n";
			$message .= "WordPress: " . get_bloginfo('version') . "\n";
			$message .= "PHP: " . phpversion() . "\n";
			$message .= "MySQL: " . $wpdb->db_version() . "\n";
			$message .= "Server: {$server_software}\n";
			$message .= "HTTPS: {$https}\n";
			$message .= "Multisite: {$multisite}\n\n";
			$message .= "── Theme ───────────────────────────\n";
			$message .= "Active: " . $theme->get('Name') . ' ' . $theme->get('Version') . "\n";
			$message .= "Parent: {$parent_theme}\n\n";
			$message .= "── WooCommerce ─────────────────────\n";
			$message .= "Version: {$wc_version}\n\n";
			$message .= "── PHP Settings ────────────────────\n";
			$message .= "Memory Limit: {$memory_limit}\n";
			$message .= "Max Execution: {$max_execution}s\n";
			$message .= "Upload Max: {$upload_max}\n";
			$message .= "Post Max: {$post_max}\n\n";
			$message .= "── Active Plugins ──────────────────\n";
			$message .= "{$plugins_formatted}\n\n";
			$message .= "─────────────────────────────────────\n";
			$message .= "They have been directed to the <a href=\"{$support_url}\">plugins support forum</a>.";
			
			$headers = [
				'Content-Type: text/html; charset=UTF-8'
			];
			
			// Convert newlines to <br> for HTML email
			$message = nl2br($message);
			
			wp_mail($to, $subject, $message, $headers);
			
			wp_send_json_success(['message' => 'Notification sent']);
		}

		// woocommerce custom product search ajax handler

		public function custom_product_search() {
			// Verify the request is from an authenticated admin user
			if (!current_user_can('manage_options')) {
				wp_send_json_error('Unauthorized', 403);
				return;
			}

			global $wpdb;
			
			// Sanitize input
			$product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
			
			if (empty($product_name)) {
				wp_send_json([]);
				return;
			}

			// Use prepared statement to prevent SQL injection
			$search_query = $wpdb->prepare(
				"SELECT ID, post_name FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status = 'publish' AND post_name LIKE %s",
				'%' . $wpdb->esc_like($product_name) . '%'
			);
			$results = $wpdb->get_results($search_query);

			wp_send_json($results);
		}


		/**
		 * Plugin review admin notice
		 * 
		 * @since    1.0.0
		 */
		public function admin_notice() {
			$last_dismissed = get_option("hwcf_notice_dismiss");
			
			// If permanently dismissed (user clicked feedback link), don't show
			if ($last_dismissed === '1') {
				return;
			}
			
			// If no value set yet, don't show (wait for admin_init to set the 30-day timer)
			if (empty($last_dismissed)) {
				return;
			}
			
			// Show notice only if current date has reached or passed the scheduled date
			$current_date = date('Y-m-d');
			$scheduled_date = substr($last_dismissed, 0, 10);
			
			if ($current_date >= $scheduled_date) {
				echo '<div class="notice notice-info is-dismissible" id="hwcf_notice">
				<p>How do you like <strong>Hide Cart Functions</strong>? Your feedback assures the continued maintenance of this plugin! <a class="button button-primary hwcf-feedback" href="https://wordpress.org/plugins/hide-cart-functions/#reviews" target="_blank">Leave Feedback</a></p>
				</div>';
			}
		}


		/**
		 * Register the stylesheets for the public-facing side of the site.
		 *
		 * @since    1.0.0
		 */
		public function enqueue_admin_styles() {
			global $pagenow;

			wp_register_style('hide-cart-functions-select2', HWCF_GLOBAl_URL . 'admin/assets/css/select2.css');
			wp_register_script('hide-cart-functions-select2', HWCF_GLOBAl_URL . 'admin/assets/js/select2.js', array('jquery'), '4.0.3', true);

			wp_enqueue_style(HWCF_GLOBAl_BASE_NAME . '-multi-select', HWCF_GLOBAl_URL . 'admin/assets/css/multi-select.css', [], HWCF_GLOBAl_VERSION, 'all');
			wp_enqueue_style(HWCF_GLOBAl_BASE_NAME . '-style', HWCF_GLOBAl_URL . 'admin/assets/css/style.css', ['hide-cart-functions-select2'], HWCF_GLOBAl_VERSION, 'all');

			wp_enqueue_script(HWCF_GLOBAl_BASE_NAME . '-multi-select', HWCF_GLOBAl_URL . 'admin/assets/js/multi-select.js', ['jquery'], HWCF_GLOBAl_VERSION, false);
			wp_enqueue_script(HWCF_GLOBAl_BASE_NAME . '-admin-customs', HWCF_GLOBAl_URL . 'admin/assets/js/customs.js', ['jquery', 'hide-cart-functions-select2'], HWCF_GLOBAl_VERSION, false);
			//localize wpforo js
			wp_localize_script(HWCF_GLOBAl_BASE_NAME . '-admin-customs', 'hwcf', [
				'ajaxurl'        => admin_url('admin-ajax.php'),
				'nonce'          => wp_create_nonce('hwcf_admin_nonce'),
				'search_product' => esc_html__('Search Product', 'hide-cart-functions'),
				'search_text'    => esc_html__('Select category', 'hide-cart-functions'),
				'search_none'    => esc_html__('No results found.', 'hide-cart-functions'),
				'show_toast'     => isset($_GET['new_hwcf']) && $_GET['new_hwcf'] == 1 ? 'success' : (isset($_GET['deleted_hwcf']) && $_GET['deleted_hwcf'] == 1 ? 'deleted' : ''),
			]);


			//select to library
			wp_enqueue_script('wc-enhanced-select');

			if (function_exists('WC')) {
				wp_enqueue_style('woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css');
			}
		}

		/**
		 * Add WP admin menu for Tags
		 *
		 * @return void
		 * @author Olatechpro
		 */
		public function admin_menu() {
			$hook = add_submenu_page(
				self::MENU_SLUG,
				esc_html__('Hide Functions', 'hide-cart-functions'),
				esc_html__('Hide Functions', 'hide-cart-functions'),
				'manage_options',
				'hwcf_settings',
				[
					$this,
					'page_manage_hwcf',
				],
				99
			);

			add_action("load-$hook", [$this, 'screen_option']);
		}

		/**
		 * Add settings page to plugin activation menu
		 *
		 * @return void
		 * @author Olatechpro
		 */
		public function plugin_settings_link($links) {
			return array_merge(array(
				'<a href="' .
					admin_url('admin.php?page=hwcf_settings') .
					'">' . esc_html__('Settings', 'hide-cart-functions') . '</a>'
			), $links);;
		}

		/**
		 * add donate link to plugim row
		 *
		 * @param array $links
		 * @param string $file
		 * @return array
		 */
		public function add_description_link($links, $file) {
			if (HWCF_GLOBAl_BASE_NAME == $file) {
				$row_meta = array(
					'donation' => '<a href="' . esc_url('https://www.zeffy.com/en-US/donation-form/your-donation-makes-a-difference-6') . '" target="_blank">' . esc_html__('Donation for Homeless', 'hide-cart-functions') . '</a>'
				);
				return array_merge($links, $row_meta);
			}
			return (array) $links;
		}

		/**
		 * add more info link to plugin row
		 *
		 * @param array $links
		 * @param srting $plugin_file
		 * @param array $plugin_data
		 * @return array
		 */
		public function add_details_link($links, $plugin_file, $plugin_data) {
			if (
				(isset($plugin_data['PluginURI']))
				&&
				(false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/extend/plugins/')
					|| false !== strpos($plugin_data['PluginURI'], 'http://wordpress.org/plugins/hide-cart-functions')
				)
			) {
				$slug = basename($plugin_data['PluginURI']);
				$links[2] = sprintf('<a href="%s" class="thickbox" title="%s">%s</a>', self_admin_url('plugin-install.php?tab=plugin-information&amp;plugin=' . $slug . '&amp;TB_iframe=true&amp;width=772&amp;height=563'), esc_attr(sprintf(__('More information about %s', 'hide-cart-functions'), $plugin_data['Name'])), __('View Details', 'hide-cart-functions'));
			}
			return $links;
		}

		/**
		 * Screen options
		 */
		public function screen_option() {
			$option = 'per_page';
			$args   = [
				'label'   => esc_html__('Number of items per page', 'hide-cart-functions'),
				'default' => 20,
				'option'  => 'hwcf_settings_per_page'
			];

			add_screen_option($option, $args);

			$this->terms_table = new hwcf_List();
		}

		/**
		 * Method for build the page HTML manage tags
		 *
		 * @return void
		 * @author Olatechpro
		 */
		public function page_manage_hwcf() {
			// Default order
			if (!isset($_GET['order'])) {
				$_GET['order'] = 'name-asc';
			}

			settings_errors(__CLASS__);

			if (!isset($_GET['add'])) {
				//all tax 
				
				//the terms table instance - must be before header so search_box works
				$this->terms_table->prepare_items();
?>
				<div class="wrap st_wrap st-manage-taxonomies-page hwcf-table-page">

					<div id="">
						<div class="hwcf-table-header">
							<div class="hwcf-table-header-left">
								<h1><?php esc_html_e('Hide Cart Functions', 'hide-cart-functions'); ?></h1>
								<a href="<?php echo esc_url(admin_url('admin.php?page=hwcf_settings&add=new_item')); ?>" class="hwcf-add-new-btn"><?php esc_html_e('Add New', 'hide-cart-functions'); ?></a>
								<?php
								if (isset($_REQUEST['s']) && $search = esc_attr(sanitize_key(wp_unslash($_REQUEST['s'])))) {
									printf(' <span class="subtitle">' . esc_html__('Search results for %s', 'hide-cart-functions') . '</span>', esc_html($search));
								} ?>
							</div>
							<form class="hwcf-table-header-right" method="get">
								<input type="hidden" name="page" value="hwcf_settings" />
								<?php $this->terms_table->search_box(esc_html__('Search Result', 'hide-cart-functions'), 'term'); ?>
							</form>
						</div>

						<div id="ajax-response"></div>
						<div class="clear"></div>

						<div id="col-container" class="wp-clearfix">

							<div class="col-wrap">
								<form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
									<?php $this->terms_table->display(); ?>
								</form>
							</div>


						</div>

						<!-- Store-Wide Settings Section -->
						<div class="hwcf-admin-ui">
							<div class="hwcf-section postbox hwcf-store-wide-section">
								<h2 id="poststuff"><?php echo esc_html__('Store-Wide Settings', 'hide-cart-functions'); ?></h2>
								<div class="inside">
									<table class="form-table hwcf-table">
										<tr valign="top">
											<th scope="row">
												<label for="hwcf_cripple_bots">
													<?php echo esc_html__('Cripple Bots', 'hide-cart-functions'); ?>
												</label>
											</th>
											<td>
												<input type="checkbox" id="hwcf_cripple_bots" class="checkinput-box" value="1" <?php checked(get_option('hwcf_cripple_bots', 0), 1); ?>>
												<span class="description checkinput-description"><?php echo esc_html__('Require a valid cart session before checkout.', 'hide-cart-functions'); ?></span>
												<span class="hwcf-tooltip">
													<span class="dashicons dashicons-editor-help"></span>
													<span class="tooltiptext"><?php echo esc_html__('Blocks direct POST attacks by card test bots. Real customers continue to add products to their cart through normal browsing. Orders without a valid session are rejected before reaching the payment gateway.', 'hide-cart-functions'); ?></span>
												</span>
											</td>
										</tr>

										<tr valign="top">
											<th scope="row">
												<label for="hwcf_disable_purchases">
													<?php echo esc_html__('Disable Purchases', 'hide-cart-functions'); ?>
												</label>
											</th>
											<td>
												<input type="checkbox" id="hwcf_disable_purchases" class="checkinput-box" value="1" <?php checked(get_option('hwcf_disable_purchases', 0), 1); ?>>
												<span class="description checkinput-description"><?php echo esc_html__('Check this option to completely block code-activated purchases.', 'hide-cart-functions'); ?></span>
												<span class="hwcf-tooltip">
													<span class="dashicons dashicons-editor-help"></span>
													<span class="tooltiptext"><?php echo esc_html__('Completely prevents cart bots from placing orders via direct POST requests, as well as customers. Best used temporarily as a nuclear option.', 'hide-cart-functions'); ?></span>
												</span>
											</td>
										</tr>

										<tr valign="top">
											<th scope="row">
												<label for="hwcf_delete_on_uninstall">
													<?php echo esc_html__('Delete Data on Uninstall', 'hide-cart-functions'); ?>
												</label>
											</th>
											<td>
												<input type="checkbox" id="hwcf_delete_on_uninstall" class="checkinput-box" value="1" <?php checked(get_option('hwcf_delete_on_deactivation', 0), 1); ?>>
												<span class="description checkinput-description"><span class="hwcf-warning-text"><?php echo esc_html__('WARNING:', 'hide-cart-functions'); ?></span> <?php echo esc_html__('All plugin settings will be permanently deleted if this plugin is uninstalled.', 'hide-cart-functions'); ?></span>
											</td>
										</tr>
									</table>
								</div>
								<div class="clear"></div>
								<div class="hwcf-table-submit-panel">
									<div class="submit-buttons">
										<button type="button" class="button-primary hwcf-save-store-settings"><?php esc_html_e('Save Settings', 'hide-cart-functions'); ?></button>
										<a href="https://wordpress.org/support/plugin/hide-cart-functions/" target="_blank" class="button button-secondary hwcf-support-btn"><?php esc_html_e('Support', 'hide-cart-functions'); ?></a>
										<a href="https://wordpress.org/support/plugin/hide-cart-functions/reviews/#new-post" target="_blank" class="button button-secondary"><?php esc_html_e('Leave Review', 'hide-cart-functions'); ?></a>
									</div>
									<p class="hwcf-donation-text">
										<?php esc_html_e('This plugin is free, but your donation aids orphans:', 'hide-cart-functions'); ?>
										<a href="https://www.zeffy.com/en-US/donation-form/your-donation-makes-a-difference-6" target="_blank" class="button button-secondary"><?php esc_html_e('I Want to Help', 'hide-cart-functions'); ?></a>
									</p>
								</div>
							</div>
						</div>

						<!-- Toast Notification -->
						<div id="hwcf-toast" class="hwcf-toast">
							<div class="hwcf-toast-icon">
								<span class="dashicons dashicons-yes-alt"></span>
							</div>
							<div class="hwcf-toast-content">
								<div class="hwcf-toast-title"><?php esc_html_e('Success!', 'hide-cart-functions'); ?></div>
								<div class="hwcf-toast-message"><?php esc_html_e('Settings saved.', 'hide-cart-functions'); ?></div>
							</div>
							<button type="button" class="hwcf-toast-close">&times;</button>
							<div class="hwcf-toast-progress"></div>
						</div>

					</div>
				<?php
			} else {
				if ($_GET['add'] == 'new_item') {
					$this->hwcf_manage_hwcf();
					echo '<div>';
				}
			} ?>
				</div>
			<?php
		}


		/**
		 * Create our settings page output.
		 *
		 * @internal
		 */
		public function hwcf_manage_hwcf() {
			$tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
			$tab_class = 'hwcf-' . $tab;
			$current   = null; ?>

				<div class="wrap <?php echo esc_attr($tab_class); ?>">

					<?php

					$hwcf      = hwcf_get_hwcf_data();
					$hwcf_edit = false;

					global $current_user;

					if ('edit' === $tab) {
						$selected_hwcf = hwcf_get_current_hwcf();

						if ($selected_hwcf && array_key_exists($selected_hwcf, $hwcf)) {
							$current         = $hwcf[$selected_hwcf];
							$hwcf_edit = true;
						}
					} ?>


					<div class="wrap <?php echo esc_attr($tab_class); ?>">
						<div class="hwcf-settings-header"><h1><?php echo esc_html__('Hide Functions Settings', 'hide-cart-functions'); ?>&nbsp;&nbsp;&nbsp;<a href="<?php echo esc_url(admin_url('admin.php?page=hwcf_settings')); ?>" class="page-title-action"><?php esc_html_e('Saved Settings', 'hide-cart-functions'); ?></a></h1><span class="hwcf-version">version <?php echo esc_html(HWCF_GLOBAl_VERSION); ?></span></div>
						<div class="wp-clearfix"></div>

						<!-- Toast Notification -->
						<div id="hwcf-toast" class="hwcf-toast">
							<div class="hwcf-toast-icon">
								<svg class="icon-success" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
								<svg class="icon-error" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
							</div>
							<div class="hwcf-toast-content">
								<div class="hwcf-toast-title"><?php esc_html_e('Success!', 'hide-cart-functions'); ?></div>
								<div class="hwcf-toast-message"><?php esc_html_e('Settings saved.', 'hide-cart-functions'); ?></div>
							</div>
							<button type="button" class="hwcf-toast-close">&times;</button>
							<div class="hwcf-toast-progress"></div>
						</div>

						<form method="post" action="">


							<div class="hwcf-admin-ui">
								<div class="hwcf-postbox-container">
									<div id="poststuff">
										<div class="hwcf-section postbox">
											<div class="postbox-header">
												<h2 class="hndle ui-sortable-handle">
													<?php
													if ($hwcf_edit) {
														echo esc_html__(
															'Edit Settings',
															'hide-cart-functions'
														) . '<span>' . esc_html__('Existing Rule', 'hide-cart-functions') . ': <font color="green">' . esc_html($current['hwcf_title']) . '</font></span>';
														echo '<input type="hidden" name="edited_hwcf" value="' . esc_attr($current['ID']) . '" />';
														echo '<input type="hidden" name="hwcf[ID]" value="' . esc_attr($current['ID']) . '" />';
													} else {
														echo esc_html__('Add New Settings', 'hide-cart-functions');
														echo '<span>' . esc_html__('Existing Rule', 'hide-cart-functions') . ': <font color="green">' . esc_html__('New Settings', 'hide-cart-functions') . '</font></span>';
													} ?>
												</h2>
											</div>
											<div class="inside">
												<div class="main">

													<div class="st-taxonomy-content">

														<table class="form-table hwcf-table">

															<tr valign="top">
																<th scope="row"><label for="hwcf_disable"><?php echo esc_html__('Disable Settings', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_disable" class="checkinput-box" name="hwcf[hwcf_disable]" value="1" <?php echo (isset($current) && isset($current['hwcf_disable']) && (int)$current['hwcf_disable'] > 0 ? 'checked="checked"' : ''); ?>>
																	<p class="description checkinput-description"><?php echo esc_html__('Check this option to disable this rule. Leave unchecked to apply the selected settings.', 'hide-cart-functions'); ?></p>
																</td>
															</tr>
															<?php $loggedinUsersArr = array();
															if ((isset($current) && isset($current['loggedinUsers']) && (int)$current['loggedinUsers'])) {
																$loggedinUsersArr = explode(",", $current['loggedinUsers']);
															} ?>
															<tr valign="top">
																<th scope="row"><label for="hwcf_loggedinUsers"><?php echo esc_html__('Rule enabled', 'hide-cart-functions'); ?></label></th>
																<td>
																	<input type="checkbox" class="checkinput-box usercheckbox guest-checkbox" name="hwcf[loggedinUsers][]" value="1" <?php echo (in_array(1, $loggedinUsersArr))  ? 'checked="checked"' : ''; ?>><label for="hwcf_loggedinUsers">
																		<p class="description checkinput-description"><?php echo esc_html__('Guests Only', 'hide-cart-functions'); ?></p>
																	</label>
																	&nbsp;&nbsp;&nbsp;
																	<input type="checkbox" class="checkinput-box usercheckbox logged-in-checkbox" name="hwcf[loggedinUsers][]" value="2" <?php echo (in_array(2, $loggedinUsersArr))  ? 'checked="checked"' : ''; ?>><label for="hwcf_loggedinUsers">
																		<p class="description checkinput-description"><?php echo esc_html__('Logged in Users --> Leave both unchecked for ALL users.', 'hide-cart-functions'); ?></p>
																	</label>



																</td>
															</tr>


															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_title">
																		<?php echo esc_html__('Rule Title', 'hide-cart-functions'); ?>
																	</label>
																</th>
																<td>
																	<span class="hwcf-input-wrap"><input type="text" id="hwcf_title" class="" name="hwcf[hwcf_title]" value="<?php echo (isset($current) && isset($current['hwcf_title'])) ? esc_html($current['hwcf_title']) : ''; ?>">
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__("Create short title for rule for display in the settings table.", "hide-cart-functions"); ?></span>
																	</span></span>
																</td>
															</tr>

																<tr class="hwcf-section-divider"><td colspan="2"></td></tr>

															<tr valign="top">
																<th scope="row"><label for="hwcf_hide_quantity"><?php echo esc_html__('Hide Quantity', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_hide_quantity" class="checkinput-box" name="hwcf[hwcf_hide_quantity]" value="1" <?php echo (isset($current) && isset($current['hwcf_hide_quantity']) && (int)$current['hwcf_hide_quantity'] > 0 ? 'checked="checked"' : ''); ?>>
																	<p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Quantity" product function.', 'hide-cart-functions'); ?></p>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row"><label for="hwcf_hide_options"><?php echo esc_html__('Hide Options', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_hide_options" class="checkinput-box" name="hwcf[hwcf_hide_options]" value="1" <?php echo (isset($current) && isset($current['hwcf_hide_options']) && (int)$current['hwcf_hide_options'] > 0 ? 'checked="checked"' : ''); ?>>
																	<p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Options" dropdown selector.', 'hide-cart-functions'); ?></p>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row"><label for="hwcf_hide_add_to_cart"><?php echo esc_html__('Hide Add to Cart', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_hide_add_to_cart" class="checkinput-box" name="hwcf[hwcf_hide_add_to_cart]" value="1" <?php echo (isset($current) && isset($current['hwcf_hide_add_to_cart']) && (int)$current['hwcf_hide_add_to_cart'] > 0 ? 'checked="checked"' : ''); ?>>
																	<p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Add to Cart" button.', 'hide-cart-functions'); ?></p>
																</td>
															</tr>


															<tr valign="top">
																<th scope="row"><label for="hwcf_hide_price"><?php echo esc_html__('Hide Price', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_hide_price" class="checkinput-box" name="hwcf[hwcf_hide_price]" value="1" <?php echo (isset($current) && isset($current['hwcf_hide_price']) && (int)$current['hwcf_hide_price'] > 0 ? 'checked="checked"' : ''); ?>>
																	<p class="description checkinput-description"><?php echo esc_html__('Check this option to hide the default cart "Price" displayed.', 'hide-cart-functions'); ?></p>
																</td>
															</tr>


															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_overridePriceTag"><?php echo esc_html__('Override Price Tag', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<?php 
																	$overridePriceTag_key = hwcf_get_key_for_language('overridePriceTag');
																	$value = isset($current[$overridePriceTag_key]) ? $current[$overridePriceTag_key] : '[price]';
																	?>
																	<input type="text" id="hwcf_overridePriceTag" name="hwcf[<?php echo esc_attr($overridePriceTag_key) ?>]" value="<?php echo esc_attr($value); ?>" <?php echo (isset($current) && isset($current['hwcf_hide_price']) && (int)$current['hwcf_hide_price'] > 0 ? 'disabled' : ''); ?> />
																	<label for="hwcf_overridePriceTag">
																		<p>
																			<?php
																			printf(
																				__('This text will override the price tag. Use the %s to keep showing price in the tag with other text.', 'hide-cart-functions'),
																				'<code>[price]</code>'
																			)
																			?>
																		</p>
																	</label>
																</td>
															</tr>


								<tr class="hwcf-section-divider"><td colspan="2"></td></tr>
															<tr valign="top">
																<th scope="row"><label for="hwcf_show_login_button"><?php echo esc_html__('Show Login Button', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<input type="checkbox" id="hwcf_show_login_button" class="checkinput-box" name="hwcf[hwcf_show_login_button]" value="1" <?php echo (isset($current) && isset($current['hwcf_show_login_button']) && (int)$current['hwcf_show_login_button'] > 0 ? 'checked="checked"' : ''); ?>>
																	<span class="description checkinput-description"><?php echo esc_html__('Display a login button in place of the Add to Cart button.', 'hide-cart-functions'); ?></span>
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Works best with Guests Only enabled. Shows a login button that redirects users to log in, then returns them to the product page.', 'hide-cart-functions'); ?></span>
																	</span>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_login_button_text"><?php echo esc_html__('Login Button Text', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<?php 
																	$login_button_text_key = hwcf_get_key_for_language('login_button_text');
																	$login_text_value = isset($current[$login_button_text_key]) ? $current[$login_button_text_key] : '';
																	?>
																	<span class="hwcf-input-wrap"><input type="text" id="hwcf_login_button_text" name="hwcf[<?php echo esc_attr($login_button_text_key); ?>]" value="<?php echo esc_attr($login_text_value); ?>" placeholder="<?php echo esc_attr__('Login to See Prices', 'hide-cart-functions'); ?>" />
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Custom text for the login button. Leave empty for default.', 'hide-cart-functions'); ?></span>
																	</span></span>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_login_return_url"><?php echo esc_html__('Login Return URL', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<?php $return_url = isset($current['hwcf_login_return_url']) ? $current['hwcf_login_return_url'] : 'product'; ?>
																	<select id="hwcf_login_return_url" name="hwcf[hwcf_login_return_url]">
																		<option value="product" <?php selected($return_url, 'product'); ?>><?php echo esc_html__('Current Product Page', 'hide-cart-functions'); ?></option>
																		<option value="shop" <?php selected($return_url, 'shop'); ?>><?php echo esc_html__('Shop Page', 'hide-cart-functions'); ?></option>
																		<option value="home" <?php selected($return_url, 'home'); ?>><?php echo esc_html__('Home Page', 'hide-cart-functions'); ?></option>
																		<option value="account" <?php selected($return_url, 'account'); ?>><?php echo esc_html__('My Account Page', 'hide-cart-functions'); ?></option>
																	</select>
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Choose where customers are redirected after logging in.', 'hide-cart-functions'); ?></span>
																	</span>
																</td>
															</tr>

																<tr class="hwcf-section-divider"><td colspan="2"></td></tr>


															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_custom_element">
																		<?php echo esc_html__('Hide Custom Element', 'hide-cart-functions'); ?>
																	</label>
																</th>
																<td>
																	<span class="hwcf-input-wrap"><input type="text" id="hwcf_custom_element" class="" name="hwcf[hwcf_custom_element]" value="<?php echo (isset($current) && isset($current['hwcf_custom_element'])) ? esc_html($current['hwcf_custom_element']) : ''; ?>" />
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__("Separate multiple values by comma (examples: .custom-item-one, .custom-item-two, #new-item-id).", "hide-cart-functions"); ?></span>
																	</span></span>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_custom_message">
																		<?php echo esc_html__('Custom Message', 'hide-cart-functions'); ?></label>
																</th>
																<td class="wp-editor-td">
																	<?php
																	$editor_id = hwcf_get_key_for_language('hwcf_custom_message');
																	$content = (isset($current) && isset($current[$editor_id])) ? stripslashes($current[$editor_id]) : '';
																	$args      = array(
																		'textarea_name' => 'hwcf[' . $editor_id . ']',
																		'media_buttons' => true,
																		'textarea_rows' =>  3
																	);
																	wp_editor($content, $editor_id, $args);
																	?>
																	<p class="description"><?php echo esc_html__('Enter a custom message to be added above or below the "Short product description"', 'hide-cart-functions'); ?></p>
																</td>
															</tr>

															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_custom_message_position">
																		<?php echo esc_html__('Custom Message Position', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<span class="hwcf-input-wrap"><select id="hwcf_custom_message_position" class="" name="hwcf[hwcf_custom_message_position]">
																		<?php
																		$postion_options = [
																			'above' => esc_html__('Above Short product description', 'hide-cart-functions'),
																			'below' => esc_html__('Below Short product description.', 'hide-cart-functions')
																		];
																		foreach ($postion_options as $key => $label) {
																		?>
																			<option value="<?php echo esc_attr($key); ?>" <?php if (isset($current) && isset($current['hwcf_custom_message_position']) && $current['hwcf_custom_message_position'] === $key) {
																																echo 'selected="selected"';
																															}; ?>>
																				<?php echo esc_html($label); ?></option>
																		<?php }

																		?>
																	</select>
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Select position where Custom Message should be inserted.', 'hide-cart-functions'); ?></span>
																	</span></span>
																</td>
															</tr>

															<?php
															$terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
															if (!is_wp_error($terms)) :  ?>
																<tr valign="top">
																	<th scope="row">
																		<label for="hwcf_categories">
																			<?php echo esc_html__('Selected Category', 'hide-cart-functions'); ?></label>
																	</th>
																	<td>
																		<span class="hwcf-input-wrap"><select id="hwcf_categories" class="hwcf_categories" name="hwcf[hwcf_categories][]" multiple>
																			<?php
																			$terms = get_terms(array(
																				'taxonomy' => 'product_cat',
																				'hide_empty' => false,
																			));

																			foreach ($terms as $term) {
																			?>
																				<option value="<?php echo esc_attr($term->term_id); ?>" <?php if (isset($current) && isset($current['hwcf_categories']) && in_array($term->term_id, $current['hwcf_categories'])) {
																																			echo 'selected="selected"';
																																		}; ?>><?php echo esc_html($term->name); ?></option>
																			<?php }

																			?>
																		</select>
																		<span class="hwcf-tooltip">
																			<span class="dashicons dashicons-editor-help"></span>
																			<span class="tooltiptext"><?php echo esc_html__('Some browsers, hold the "Ctrl" key while clicking to select multiple or to deselect entirely.', 'hide-cart-functions'); ?></span>
																		</span></span>
																	</td>
																</tr>
															<?php endif; ?>

															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_products">
																		<?php echo esc_html__('Product IDs', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<span class="hwcf-input-wrap"><input type="text" id="hwcf_products" class="" name="hwcf[hwcf_products]" value="<?php echo (isset($current) && isset($current['hwcf_products'])) ? esc_html($current['hwcf_products']) : ''; ?>">
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Separate multiple values by comma (3443, 5567, 3456) or leave empty to apply this rule to all products.', 'hide-cart-functions'); ?></span>
																	</span>
																</td>
															</tr>
															<tr valign="top">
																<th scope="row">
																	<label for="hwcf_search_products">
																		<?php echo esc_html__('Search Products', 'hide-cart-functions'); ?></label>
																</th>
																<td>
																	<span class="hwcf-input-wrap"><select id="custom-product-search-field" name="hwcf[hwcf_custom_product_search][]" multiple>
																	</select>
																	<span class="hwcf-tooltip">
																		<span class="dashicons dashicons-editor-help"></span>
																		<span class="tooltiptext"><?php echo esc_html__('Search and select products with 3 letter minimum. Works in combination with the Product ID field.', 'hide-cart-functions'); ?></span>
																	</span>
																</td>
															</tr>

														</table>


													</div>
													<div class="clear"></div>

													<!-- Submit buttons inside panel -->
													<div class="hwcf-submit-inside-panel">
														<div class="submit-buttons">
															<?php
															wp_nonce_field(
																'hwcf_addedit_hwcf_nonce_action',
																'hwcf_addedit_hwcf_nonce_field'
															);
															if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
																<input type="submit" class="button-primary hwcf-settings-submit" name="hwcf_submit" value="<?php echo esc_attr__('Save Settings', 'hide-cart-functions'); ?>" />
															<?php } else { ?>
																<input type="submit" class="button-primary hwcf-settings-submit" name="hwcf_submit" value="<?php echo esc_attr__('Save Settings', 'hide-cart-functions'); ?>" />
															<?php } ?>
															<a href="https://wordpress.org/support/plugin/hide-cart-functions/" target="_blank" class="button button-secondary hwcf-support-btn"><?php esc_html_e('Support', 'hide-cart-functions'); ?></a>
																<a href="https://wordpress.org/support/plugin/hide-cart-functions/reviews/#new-post" target="_blank" class="button button-secondary"><?php esc_html_e('Leave Review', 'hide-cart-functions'); ?></a>
														</div>
														<p class="hwcf-donation-text">
															<?php esc_html_e('This plugin is free, but your donation aids orphans:', 'hide-cart-functions'); ?>
															<a href="https://www.zeffy.com/en-US/donation-form/your-donation-makes-a-difference-6" target="_blank" class="button button-secondary"><?php esc_html_e('I Want to Help', 'hide-cart-functions'); ?></a>
														</p>
													</div>


												</div>
											</div>
										</div>


									</div>
								</div>


							</div>



							<div class="clear"></div>


						</form>

					</div><!-- End .wrap -->

					<div class="clear"></div>

		<?php
		}
	}
	hwcf_Admin::get_instance();
}
