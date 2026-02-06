<?php

/**
 * @package              HWCF_GLOBAl
 * @wordpress-plugin
 * 
 * Plugin Name:          Hide Cart Functions
 * Plugin URI:           http://wordpress.org/plugins/hide-cart-functions
 * Description:          Hide product's price, add to cart button, quantity selector, and product options on any product and order. Add message below or above description.
 * Version:              1.2.16
 * Author:               Artios Media
 * Author URI:           http://www.artiosmedia.com
 * Assisting Developer:  Arafat Rahman
 * Copyright:            © 2022-2026 Artios Media (email: contact@artiosmedia.com).
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          hide-cart-functions
 * Domain Path:          /languages
 * Tested up to:         6.9.1
 * Requires at least:    5.8
 * WC requires at least: 6.5.0
 * WC tested up to:      10.4.3
 * Requires PHP:         7.4
 * PHP tested up to:     8.4.17
 */

namespace Artiosmedia\WC_Purchase_Customization;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('HWCF_GLOBAl_VERSION', '1.2.16');
define('HWCF_GLOBAl_NAME', 'hwcf-global');
define('HWCF_GLOBAl_ABSPATH', __DIR__);
define('HWCF_GLOBAl_BASE_NAME', plugin_basename(__FILE__));
define('HWCF_GLOBAl_DIR', plugin_dir_path(__FILE__));
define('HWCF_GLOBAl_URL', plugin_dir_url(__FILE__));

include(HWCF_GLOBAl_DIR . 'inc/utilities-functions.php');
require HWCF_GLOBAl_DIR . 'admin/hwcf-table.php';
require HWCF_GLOBAl_DIR . 'admin/hwcf-admin.php';

add_action('before_woocommerce_init', function () {
    // Check if the FeaturesUtil class exists in the \Automattic\WooCommerce\Utilities namespace.
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        // Declare compatibility with custom order tables using the FeaturesUtil class.
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});




if (!class_exists('HWCF_GLOBAl')) {

    class HWCF_GLOBAl {

        /**
         * Static instance of this class
         *
         * @var self
         */
        private static $_instance;


        public function __construct() {
            // Load translation
            add_action('init', [$this, 'init_translation']);
            //apply hide selector settings
            add_action('wp_head', [$this, 'apply_settings']);
            //add short description message if added
            add_filter('woocommerce_short_description', [$this, 'short_description'], 999);

            //run plugin option clean-up on plugin uninstall (handled by uninstall.php)
            register_activation_hook(__FILE__, [$this, 'activation']);
            add_filter("woocommerce_get_price_html", [$this, 'modify_woocommerce_price'], 999);
            add_filter("woocommerce_cart_item_price", [$this, 'modify_woocommerce_price'], 999);
            add_filter( 'fusion_attr_fusion-column', [ $this, 'product_column_attributes' ], 999, 1 );
            add_filter('tinvwl_wishlist_item_price', [$this, 'modify_tinvwl_wishlist_item_price'], 999, 3);
            add_filter( 'tinvwl_wishlist_item_action_add_to_cart', [$this,'hide_add_to_cart_button'], 1, 3 );
            add_filter( 'tinvwl_wishlist_item_action_default_loop_button', [$this,'hide_add_to_cart_button'], 1, 3 );
            add_filter( 'tinvwl_wishlist_item_cb', [$this,'hide_select_checkbox_highest_priority'], 1, 3 );
            add_filter( 'tinvwl_manage_buttons_create', [$this,'tinvwl_hide_add_all_to_cart'], 1, 1 );
            add_filter( 'woocommerce_is_purchasable', [$this, 'block_purchases'], 10, 2 );

            // Cripple Bots: Set session flag when adding to cart (requires page visit first)
            add_action( 'woocommerce_add_to_cart', [$this, 'set_valid_cart_session'], 10 );
            add_filter( 'woocommerce_add_to_cart_validation', [$this, 'validate_add_to_cart'], 10, 3 );
            
            // Cripple Bots: Block checkout at all levels
            add_action( 'wp_loaded', [$this, 'block_direct_checkout_posts'], 1 );
            add_action( 'woocommerce_checkout_process', [$this, 'validate_cart_session_checkout'], 1 );
            add_action( 'woocommerce_before_checkout_process', [$this, 'validate_cart_session_checkout'], 1 );
            
            // Block AJAX checkout
            add_action( 'wc_ajax_checkout', [$this, 'block_ajax_checkout'], 1 );
            add_action( 'wp_ajax_woocommerce_checkout', [$this, 'block_ajax_checkout'], 1 );
            add_action( 'wp_ajax_nopriv_woocommerce_checkout', [$this, 'block_ajax_checkout'], 1 );
            
            // Block Store API checkout - THIS IS THE MAIN BOT ATTACK VECTOR
            // Hooks into REST authentication to block /wc/store/v1/checkout POST requests
            add_filter( 'rest_authentication_errors', [$this, 'block_store_api_checkout'], 1 );
            
            // Block at order creation level - last line of defense
            add_filter( 'woocommerce_create_order', [$this, 'block_order_creation'], 1, 2 );

            // Login Button: Display login button in place of Add to Cart
            add_action( 'woocommerce_after_add_to_cart_form', [$this, 'display_login_button'], 10 );
            add_action( 'woocommerce_after_shop_loop_item', [$this, 'display_login_button_loop'], 15 );


        }


        public static function init() {
            if (!self::$_instance) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        /**
         * Load translation
         *
         * @since    1.0.0
         */
        public function init_translation() {
            $domain = 'hide-cart-functions';
            $mofile_custom = sprintf('%s-%s.mo', $domain, get_locale());
            $locations = array(
                trailingslashit(WP_LANG_DIR . '/' . $domain),
                trailingslashit(WP_LANG_DIR . '/loco/plugins/'),
                trailingslashit(WP_LANG_DIR),
                trailingslashit(HWCF_GLOBAl_DIR . 'languages'),
            );

            // Update Loggedin Users Checkbox
            $settings_data    = hwcf_get_hwcf_data();
            $UpdateSettingData = array();
            if (!empty($settings_data) && is_array($settings_data)) {

                foreach ($settings_data as $option) {
                    $option['loggedinUsers'] = ($option['loggedinUsers'] != '' ? $option['loggedinUsers'] : '');
                    $UpdateSettingData[$option['ID']] = $option;
                }
                //update_option('hwcf_settings_data', $UpdateSettingData);
            }


            // Try custom locations in WP_LANG_DIR.
            foreach ($locations as $location) {
                if (load_textdomain('hide-cart-functions', $location . $mofile_custom)) {
                    return true;
                }
            }
        }

        /**
         * Apply hide selector settings
         *
         * @since    1.0.0
         */
        public function apply_settings() {
            $settings_data    = hwcf_get_hwcf_data();
            $hidding_selector = [];
            $ghost_protocol = [];
            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    $hide_all = true;
                    $hide_products = false;
                    $hide_categories = false;

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",", $option['loggedinUsers']) : array();
                    $hide_quantity = isset($option['hwcf_hide_quantity']) ? (int)($option['hwcf_hide_quantity']) : 0;
                    $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;
                    $hide_price = isset($option['hwcf_hide_price']) ? (int)($option['hwcf_hide_price']) : 0;
                    $hide_options = isset($option['hwcf_hide_options']) ? (int)($option['hwcf_hide_options']) : 0;
                    $custom_element = isset($option['hwcf_custom_element']) ? $option['hwcf_custom_element'] : '';
                    $custom_message = isset($option['hwcf_custom_message']) ? stripslashes($option['hwcf_custom_message']) : '';
                    $categories_limit = isset($option['hwcf_categories']) ? (array)$option['hwcf_categories'] : [];
                    $categories_limit = array_filter($categories_limit);
                    $products_limit = isset($option['hwcf_products']) ? $option['hwcf_products'] : '';


                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        //skip setup if it's disabled
                        continue;
                    }

                    if(in_array(1, $loggedin_users) && is_user_logged_in()){
                        continue;
                    }elseif(in_array(2, $loggedin_users) && !is_user_logged_in()){
                        continue;
                    }
                    

                    /*
                    
                    if (!is_user_logged_in() && in_array(1, $loggedin_users)) {
                    } elseif (is_user_logged_in() && in_array(2, $loggedin_users)) {
                    } elseif (isset($loggedin_users[0]) && $loggedin_users[0] == '') {
                    } else {
                        continue;
                    }
                    */

                    if (!empty($categories_limit)) {
                        //category limitation is 
                        $hide_all = false;
                        $hide_categories = true;
                    }

                    if (!empty(trim($products_limit))) {
                        //product limitation is enabled
                        $hide_all = false;
                        $hide_products = true;
                    }

                    if ($hide_all) {

                        if ($hide_quantity) {
                            $hidding_selector[] = '.product.type-product .quantity';
                            $hidding_selector[] = '.product.type-product .product-quantity';
                        }
                        if ($hide_add_to_cart) {
                            $hidding_selector[] = 'form.cart .single_add_to_cart_button';
                            $hidding_selector[] = '.product.type-product .single_add_to_cart_button';
                            $hidding_selector[] = '.product.type-product .add_to_cart_button';
                        }
                        if ($hide_price) {
                            $ghost_protocol[] = '.single-product .product .summary .price';    // added to remove entire container
                            $ghost_protocol[] = '.products .product .price';
                            $hidding_selector[] = '.product.type-product .woocommerce-Price-amount';
                            $hidding_selector[] = '.product.type-product .fusion-price-rating .price';
                            $hidding_selector[] = '.widget .woocommerce-Price-amount';
                            $hidding_selector[] = '.widget .fusion-price-rating .price';
                        }
                        if ($hide_options) {
                            $hidding_selector[] = '.product.type-product .variations';
                            $hidding_selector[] = '.product.type-product .product_type_variable.add_to_cart_button';
                        }

                        if (!empty(trim($custom_element))) {
                            $cl_element = explode(",", $custom_element);
                            $cl_element = array_map('trim', $cl_element);
                            $hidding_selector = array_merge($hidding_selector, $cl_element);
                        }
                    } else {

                        if ($hide_products) {
                            $product_ids = explode(",", $products_limit);
                            $product_ids = array_map('trim', $product_ids);
                            foreach ($product_ids as $product_id) {
                                $product_id = (int)$product_id;
                                if ($product_id > 0) {
                                    if ($hide_quantity) {
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .quantity';
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .product-quantity';
                                    }
                                    if ($hide_add_to_cart) {
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .add_to_cart_button';
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .single_add_to_cart_button';
                                        $hidding_selector[] = 'body.single-product.postid-' . $product_id . ' form.cart .single_add_to_cart_button';
                                    }
                                    if ($hide_price) {
                                        $ghost_protocol[] = '.product.type-product.post-' . $product_id . ' .price';
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .woocommerce-Price-amount';
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .fusion-price-rating .price';
                                    }
                                    if ($hide_options) {
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .variations';
                                        $hidding_selector[] = '.product.type-product.post-' . $product_id . ' .product_type_variable.add_to_cart_button';
                                    }

                                    if (!empty(trim($custom_element))) {
                                        $cl_element = explode(",", $custom_element);
                                        $cl_element = array_map(function ($el) use ($product_id) {
                                            return '.product.type-product.post-' . $product_id . " " . trim($el);
                                        }, $cl_element);
                                        $hidding_selector = array_merge($hidding_selector, $cl_element);
                                    }
                                }
                            }
                        }

                        if ($hide_categories) {
                            $category_ids = $categories_limit;
                            foreach ($category_ids as $category_id) {
                                $category_id = (int)$category_id;
                                if ($category_id > 0) {
                                    $category_data = get_term($category_id, 'product_cat');

                                    if ($category_data && !is_wp_error($category_data) && is_object($category_data) && isset($category_data->slug)) {
                                        $category_slug = $category_data->slug;

                                        if ($hide_quantity) {
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .quantity';
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .product-quantity';
                                        }
                                        if ($hide_add_to_cart) {
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .single_add_to_cart_button';
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .add_to_cart_button';
                                            $hidding_selector[] = 'body.tax-product_cat.term-' . $category_slug . ' .add_to_cart_button';

                                            if (is_product()) {
                                                $product_cats_ids = wc_get_product_term_ids(get_the_ID(), 'product_cat');
                                                if (in_array($category_id, $product_cats_ids)) {
                                                    $hidding_selector[] = 'body.single-product.postid-' . get_the_ID() . ' form.cart .single_add_to_cart_button';
                                                }
                                            }
                                        }
                                        if ($hide_price) {
                                            $ghost_protocol[] = '.product.type-product.product_cat-' . $category_slug . ' .summary .price';
                                            $ghost_protocol[] = '.product.type-product.product_cat-' . $category_slug . ' .price';
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .woocommerce-Price-amount';
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .fusion-price-rating .price';
                                        }
                                        if ($hide_options) {
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .variations';
                                            $hidding_selector[] = '.product.type-product.product_cat-' . $category_slug . ' .product_type_variable.add_to_cart_button';
                                        }

                                        if (!empty(trim($custom_element))) {
                                            $cl_element = explode(",", $custom_element);
                                            $cl_element = array_map(function ($el) use ($category_slug) {
                                                return '.product.type-product.product_cat-' . $category_slug . " " . trim($el);
                                            }, $cl_element);
                                            $hidding_selector = array_merge($hidding_selector, $cl_element);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            echo '<style id="hwcf-style">.woocommerce-variation-description .hwcf-ui-custom-message ';
            if (!empty($hidding_selector)) {
                echo esc_html(', ' . join(',', $hidding_selector) . '');
            }
            echo '{ display: none!important;}';

            if (!empty($ghost_protocol)) {
                echo esc_html(' ' . join(',', $ghost_protocol) . '');
                echo '{visibility:hidden !important;}';
            }
            echo '</style>';
        }

        /**
         * Add short description message if added
         *
         * @since    1.0.0
         */
        public function short_description($excerpt) {
            global $post;

            $settings_data    = hwcf_get_hwcf_data();
            $hidding_selector = [];

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",", $option['loggedinUsers']) : array();
                    $custom_message = isset($option['hwcf_custom_message']) ? stripslashes($option['hwcf_custom_message']) : '';
                    $custom_message_postion = isset($option['hwcf_custom_message_position']) ? stripslashes($option['hwcf_custom_message_position']) : 'below';
                    $categories_limit = isset($option['hwcf_categories']) ? (array)$option['hwcf_categories'] : [];
                    $categories_limit = array_filter($categories_limit);
                    $products_limit = isset($option['hwcf_products']) ? $option['hwcf_products'] : '';


                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        //skip setup if it's disabled
                        continue;
                    }

                    if(in_array(1, $loggedin_users) && is_user_logged_in()){
                        continue;
                    }elseif(in_array(2, $loggedin_users) && !is_user_logged_in()){
                        continue;
                    }
                    /*
                    if (!is_user_logged_in() && in_array(1, $loggedin_users)) {
                    } elseif (is_user_logged_in() && in_array(2, $loggedin_users)) {
                    } elseif (isset($loggedin_users[0]) && $loggedin_users[0] == '') {
                    } else {
                        continue;
                    }
                    */

                    if (!empty(trim($products_limit)) && isset($post->ID)) {
                        $product_ids = explode(",", $products_limit);
                        $product_ids = array_map('trim', $product_ids);
                        if (!in_array($post->ID, $product_ids)) {
                            continue;
                        }
                    }

                    if (!empty($categories_limit) && isset($post->ID)) {
                        $category_ids = $categories_limit;
                        $cat_ids = wp_get_post_terms($post->ID, 'product_cat', array('fields' => 'ids'));
                        $intersection = array_intersect($category_ids, $cat_ids);
                        if (count($intersection) === 0) {
                            continue;
                        }
                    }

                    if (!empty(trim($custom_message))) {
                        if ($custom_message_postion === 'below') {
                            $excerpt .= " <div class='hwcf-ui-custom-message'> " . hwcf_translate_string($custom_message) . "</div>";
                        } else {
                            $excerpt = "<div class='hwcf-ui-custom-message'> " . hwcf_translate_string($custom_message) . "</div> " . $excerpt;
                        }
                    }
                }
            }

            return $excerpt;
        }

        /**
         * 
         * Modify woocommerce price text for selected user type/role
         * 
         */
        function modify_woocommerce_price($price) {
            $settings_data    = hwcf_get_hwcf_data();
            global $id;

            if (empty($settings_data)) {
                return $price;
            }

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    $overridePriceTag_key = hwcf_get_key_for_language('overridePriceTag');
                    $overridePriceTag = !empty($option[$overridePriceTag_key]) ? $option[$overridePriceTag_key] : $price;

                    $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;

                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        continue;
                    }

                    $loggedin_users = isset($option['loggedinUsers']) ? explode(",", $option['loggedinUsers']) : array();


                    if(in_array(1, $loggedin_users) && is_user_logged_in()){
                        continue;
                    }elseif(in_array(2, $loggedin_users) && !is_user_logged_in()){
                        continue;
                    }

                    if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');
                    
                        if (!empty($product_cats_ids) && !empty($option['hwcf_categories'])) {
                            
                            // Clean both arrays to ensure valid integers
                            $product_cats_ids = array_map('intval', $product_cats_ids);
                            $option_cats_ids  = array_map('intval', $option['hwcf_categories']);
                    
                            // Compare product categories with configured categories
                            $matched_cats = array_filter($product_cats_ids, function($cat_id) use ($option_cats_ids) {
                                return in_array($cat_id, $option_cats_ids, true);
                            });
                    
                            if (!empty($matched_cats)) {
                                if (!empty($overridePriceTag)) {
                                    if(isset($option['hwcf_hide_price']) && (int)$option['hwcf_hide_price'] > 0){
                                        $price = '';
                                    }
                                    $priceTag = hwcf_translate_string($overridePriceTag);
                                    $price = str_replace('[price]', $price, $overridePriceTag);
                                }
                            }
                        }
                    }

                    if ($product_ids != null) {
                        $product_ids = explode(",", $product_ids);
                        $product_ids = array_filter(array_map('absint', $product_ids));
                        if (in_array($id, $product_ids)) {
                            if(isset($option['hwcf_hide_price']) && (int)$option['hwcf_hide_price'] > 0){
                                $price = '';
                            }
                            $price = str_replace('[price]', $price, $overridePriceTag);
                        }
                    }
                }
            }

            return $price;
        }



        public function modify_tinvwl_wishlist_item_price($price, $wl_product, $product) {
            $settings_data    = hwcf_get_hwcf_data();
            // Extract the product ID
            $id = isset($wl_product['product_id']) ? absint($wl_product['product_id']) : null;

            if (empty($settings_data)) {
                return $price;
            }

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    $overridePriceTag_key = hwcf_get_key_for_language('overridePriceTag');
                    $overridePriceTag = !empty($option[$overridePriceTag_key]) ? $option[$overridePriceTag_key] : $price;

                    $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;

                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        continue;
                    }

                    $loggedin_users = isset($option['loggedinUsers']) ? $option['loggedinUsers'] : '';

                    if ($loggedin_users == 1 && !is_user_logged_in()) {
                        $price = str_replace('[price]', $price, $overridePriceTag);
                    }

                    if ($loggedin_users == 2 && is_user_logged_in()) {
                        $price = str_replace('[price]', $price, $overridePriceTag);
                    }

                    if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');

                    
                        if (!empty($product_cats_ids) && !empty($option['hwcf_categories'])) {
                            
                            // Clean both arrays to ensure valid integers
                            $product_cats_ids = array_map('intval', $product_cats_ids);
                            $option_cats_ids  = array_map('intval', $option['hwcf_categories']);
                    
                            // Compare product categories with configured categories
                            $matched_cats = array_filter($product_cats_ids, function($cat_id) use ($option_cats_ids) {
                                return in_array($cat_id, $option_cats_ids, true);
                            });
                    
                            if (!empty($matched_cats)) {
                                if (!empty($overridePriceTag)) {
                                    $priceTag = hwcf_translate_string($overridePriceTag);
                                    $price = str_replace('[price]', $price, $overridePriceTag);
                                }
                            }
                        }
                    }

                    if ($product_ids != null) {
                        $product_ids = explode(",", $product_ids);
                        $product_ids = array_filter(array_map('absint', $product_ids));
                        if (in_array($id, $product_ids)) {
                            $price = str_replace('[price]', $price, $overridePriceTag);
                        }
                    }
                }
            }

            return $price;

        }

        public function hide_select_checkbox_highest_priority($checkbox_html, $wl_product, $product){

            $settings_data    = hwcf_get_hwcf_data();
            // Extract the product ID
            $id = isset($wl_product['product_id']) ? absint($wl_product['product_id']) : null;

            if (empty($settings_data) || !is_array($settings_data)) {
                return $checkbox_html;
            }

            foreach ($settings_data as $option) {

                if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                    continue;
                }

                $loggedin_users = isset($option['loggedinUsers']) ? explode(",", $option['loggedinUsers']) : array();

                if(in_array(1, $loggedin_users) && is_user_logged_in()){
                    continue;
                }elseif(in_array(2, $loggedin_users) && !is_user_logged_in()){
                    continue;
                }

                $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;
                
                if (!$hide_add_to_cart) {
                    continue;
                }

                // Check categories
                if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories']) && !empty($option['hwcf_categories'])) {
                    $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');
                    $matched_cats = array_intersect($product_cats_ids, $option['hwcf_categories']);
                    if (!empty($matched_cats)) {
                        return '';
                    }
                    continue;
                }

                // Check product IDs
                $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;
                if ($product_ids != null && !empty(trim($product_ids))) {
                    $product_ids = explode(",", $product_ids);
                    $product_ids = array_filter(array_map('absint', $product_ids));
                    if (in_array($id, $product_ids)) {
                        return '';
                    }
                    continue;
                }
                
                // No category or product restriction - hide_add_to_cart applies to all
                return '';
            }

            return $checkbox_html;
        }


        public function hide_add_to_cart_button( $value, $wl_product, $product ) {            
           
            $settings_data    = hwcf_get_hwcf_data();
            // Extract the product ID
            $id = isset($wl_product['product_id']) ? absint($wl_product['product_id']) : null;

            if (empty($settings_data) || !is_array($settings_data)) {
                return $value;
            }

            foreach ($settings_data as $option) {

                if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                    continue;
                }

                $loggedin_users = isset($option['loggedinUsers']) ? explode(",", $option['loggedinUsers']) : array();

                if(in_array(1, $loggedin_users) && is_user_logged_in()){
                    continue;
                }elseif(in_array(2, $loggedin_users) && !is_user_logged_in()){
                    continue;
                }

                $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;
                
                if (!$hide_add_to_cart) {
                    continue;
                }

                // Check categories
                if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories']) && !empty($option['hwcf_categories'])) {
                    $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');
                    $matched_cats = array_intersect($product_cats_ids, $option['hwcf_categories']);
                    if (!empty($matched_cats)) {
                        return false;
                    }
                    continue;
                }

                // Check product IDs
                $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;
                if ($product_ids != null && !empty(trim($product_ids))) {
                    $product_ids = explode(",", $product_ids);
                    $product_ids = array_filter(array_map('absint', $product_ids));
                    if (in_array($id, $product_ids)) {
                        return false;
                    }
                    continue;
                }
                
                // No category or product restriction - hide_add_to_cart applies to all
                return false;
            }
           
            return $value;
        }

        public function tinvwl_hide_add_all_to_cart($button){
            $settings_data    = hwcf_get_hwcf_data();
            
            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;
                    if($hide_add_to_cart ){
                        $button['2'] = array();
                    }
                }
            }
            
            return $button;

        }

        /**
         * Block purchases at PHP level (global setting)
         *
         * @since    1.2.16
         * @param bool $purchasable Whether the product is purchasable.
         * @param object $product The product object.
         * @return bool
         */
        public function block_purchases( $purchasable, $product ) {
            if ( ! $purchasable ) {
                return $purchasable;
            }

            // Check global Disable Purchases setting
            if ( (int) get_option( 'hwcf_disable_purchases', 0 ) === 1 ) {
                return false;
            }

            return $purchasable;
        }

        /**
         * Set valid cart session flag when product is added to cart
         * Only sets if request appears to come from legitimate site interaction
         *
         * @since    1.2.16
         */
        public function set_valid_cart_session() {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return;
            }

            // Verify request has valid referrer from this site
            if ( ! $this->is_legitimate_request() ) {
                return;
            }

            if ( function_exists( 'WC' ) && WC()->session ) {
                WC()->session->set( 'hwcf_valid_cart_session', true );
            }
        }

        /**
         * Check if request appears to be from legitimate user interaction
         * Bots typically don't send proper referrer/origin headers
         *
         * @return bool
         * @since 1.2.16
         */
        private function is_legitimate_request() {
            // Get site host
            $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
            
            // Check HTTP_REFERER
            if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
                $referer_host = wp_parse_url( $_SERVER['HTTP_REFERER'], PHP_URL_HOST );
                if ( $referer_host === $site_host ) {
                    return true;
                }
            }
            
            // Check HTTP_ORIGIN (used in AJAX/fetch requests)
            if ( isset( $_SERVER['HTTP_ORIGIN'] ) ) {
                $origin_host = wp_parse_url( $_SERVER['HTTP_ORIGIN'], PHP_URL_HOST );
                if ( $origin_host === $site_host ) {
                    return true;
                }
            }
            
            // Allow if it's a standard form POST with WooCommerce nonce
            if ( isset( $_POST['woocommerce-add-to-cart-nonce'] ) || isset( $_POST['add-to-cart'] ) ) {
                if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
                    return true;
                }
            }
            
            // Block requests with no referrer/origin (typical bot behavior)
            return false;
        }

        /**
         * Block direct POST requests to checkout without valid cart session
         * This runs very early at wp_loaded to catch bots before WooCommerce processes
         *
         * @since    1.2.16
         */
        public function block_direct_checkout_posts() {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return;
            }

            // Only check POST requests
            if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                return;
            }

            // Check if this is a checkout or payment request
            $is_checkout_post = false;
            
            // Check for WooCommerce checkout nonce (indicates checkout form submission)
            if ( isset( $_POST['woocommerce-process-checkout-nonce'] ) || isset( $_POST['_wpnonce'] ) ) {
                $is_checkout_post = true;
            }
            
            // Check for payment method being posted
            if ( isset( $_POST['payment_method'] ) ) {
                $is_checkout_post = true;
            }
            
            // Check for billing email (checkout form field)
            if ( isset( $_POST['billing_email'] ) && isset( $_POST['billing_first_name'] ) ) {
                $is_checkout_post = true;
            }

            if ( ! $is_checkout_post ) {
                return;
            }

            // Skip for admin users
            if ( current_user_can( 'manage_options' ) ) {
                return;
            }

            // Skip for REST API requests
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                return;
            }

            // Skip for AJAX requests from legitimate sources (they'll have proper session)
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                // Still check session for AJAX
            }

            // Skip for cron jobs
            if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
                return;
            }

            // Initialize WC session if needed
            if ( function_exists( 'WC' ) && WC()->session && ! WC()->session->has_session() ) {
                WC()->session->set_customer_session_cookie( true );
            }

            // Check for valid session
            $valid_session = false;
            if ( function_exists( 'WC' ) && WC()->session ) {
                $valid_session = WC()->session->get( 'hwcf_valid_cart_session' );
            }

            if ( ! $valid_session ) {
                // Log the blocked attempt
                if ( function_exists( 'wc_get_logger' ) ) {
                    $logger = wc_get_logger();
                    $logger->warning( 'HWCF Cripple Bots: Blocked direct checkout POST from IP: ' . sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), array( 'source' => 'hide-cart-functions' ) );
                }
                
                // Return 403 Forbidden and die
                status_header( 403 );
                wp_die( 
                    __( 'Access denied. Invalid checkout session.', 'hide-cart-functions' ), 
                    __( 'Checkout Blocked', 'hide-cart-functions' ), 
                    array( 'response' => 403 ) 
                );
            }
        }

        /**
         * Validate cart session on checkout process (backup check)
         *
         * @since    1.2.16
         */
        public function validate_cart_session_checkout() {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return;
            }

            // Skip for admin users
            if ( current_user_can( 'manage_options' ) ) {
                return;
            }

            // Skip for REST API requests (allows API orders)
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                return;
            }

            // Skip for WooCommerce Subscriptions renewals
            if ( class_exists( 'WC_Subscriptions' ) && ( did_action( 'woocommerce_scheduled_subscription_payment' ) || doing_action( 'woocommerce_scheduled_subscription_payment' ) ) ) {
                return;
            }

            // Skip for cron
            if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
                return;
            }

            // Check for valid session
            $valid_session = false;
            if ( function_exists( 'WC' ) && WC()->session ) {
                $valid_session = WC()->session->get( 'hwcf_valid_cart_session' );
            }

            if ( ! $valid_session ) {
                throw new Exception( __( 'Invalid checkout session. Please add items to your cart and try again.', 'hide-cart-functions' ) );
            }
        }

        /**
         * Block AJAX checkout requests without valid session
         *
         * @since    1.2.16
         */
        public function block_ajax_checkout() {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return;
            }

            // Skip for admin users
            if ( current_user_can( 'manage_options' ) ) {
                return;
            }

            // Skip for cron
            if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
                return;
            }

            // Check for valid session
            $valid_session = false;
            if ( function_exists( 'WC' ) && WC()->session ) {
                $valid_session = WC()->session->get( 'hwcf_valid_cart_session' );
            }

            if ( ! $valid_session ) {
                wp_send_json_error( array( 
                    'message' => __( 'Invalid checkout session. Please add items to your cart and try again.', 'hide-cart-functions' ) 
                ), 403 );
                exit;
            }
        }

        /**
         * Block order creation without valid session - last line of defense
         *
         * @param int|WC_Order|null $order_id Order ID or order object
         * @param WC_Checkout $checkout Checkout object
         * @return int|WC_Order|null
         * @since    1.2.16
         */
        public function block_order_creation( $order_id, $checkout ) {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return $order_id;
            }

            // Skip for admin users
            if ( current_user_can( 'manage_options' ) ) {
                return $order_id;
            }

            // Skip for cron
            if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
                return $order_id;
            }

            // Skip for WooCommerce Subscriptions renewals
            if ( class_exists( 'WC_Subscriptions' ) && ( did_action( 'woocommerce_scheduled_subscription_payment' ) || doing_action( 'woocommerce_scheduled_subscription_payment' ) ) ) {
                return $order_id;
            }

            // Check for valid session
            $valid_session = false;
            if ( function_exists( 'WC' ) && WC()->session ) {
                $valid_session = WC()->session->get( 'hwcf_valid_cart_session' );
            }

            if ( ! $valid_session ) {
                // Log the blocked attempt
                if ( function_exists( 'wc_get_logger' ) ) {
                    $logger = wc_get_logger();
                    $logger->warning( 'HWCF Cripple Bots: Blocked order creation from IP: ' . sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), array( 'source' => 'hide-cart-functions' ) );
                }
                
                throw new Exception( __( 'Invalid checkout session. Order blocked.', 'hide-cart-functions' ) );
            }

            return $order_id;
        }

        /**
         * Block Store API checkout requests - THE MAIN BOT ATTACK VECTOR
         * Bots POST directly to /wc/store/v1/checkout bypassing normal cart flow
         *
         * @param WP_Error|null|true $result Authentication result
         * @return WP_Error|null|true
         * @since 1.2.16
         */
        public function block_store_api_checkout( $result ) {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return $result;
            }

            // Only check POST requests
            if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
                return $result;
            }

            // Check if this is the Store API checkout endpoint
            if ( ! isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
                return $result;
            }
            
            $route = $GLOBALS['wp']->query_vars['rest_route'];
            
            // Match /wc/store/v1/checkout or /wc/store/checkout
            if ( ! preg_match( '#/wc/store(?:/v\d+)?/checkout#', $route ) ) {
                return $result;
            }

            // Skip for admin users
            if ( current_user_can( 'manage_options' ) ) {
                return $result;
            }

            // Skip for cron
            if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
                return $result;
            }

            // Check for valid session
            $valid_session = false;
            if ( function_exists( 'WC' ) && WC()->session ) {
                $valid_session = WC()->session->get( 'hwcf_valid_cart_session' );
            }

            if ( ! $valid_session ) {
                // Log the blocked attempt
                if ( function_exists( 'wc_get_logger' ) ) {
                    $logger = wc_get_logger();
                    $logger->warning( 'HWCF Cripple Bots: Blocked Store API checkout from IP: ' . sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ), array( 'source' => 'hide-cart-functions' ) );
                }
                
                return new WP_Error( 
                    'hwcf_checkout_blocked', 
                    __( 'Invalid checkout session. Please add items to your cart and try again.', 'hide-cart-functions' ),
                    array( 'status' => 403 )
                );
            }

            return $result;
        }

        /**
         * Validate on add to cart to set session early
         * Only sets session if request appears legitimate
         *
         * @param bool $passed Validation passed
         * @param int $product_id Product ID
         * @param int $quantity Quantity
         * @return bool
         * @since 1.2.16
         */
        public function validate_add_to_cart( $passed, $product_id, $quantity ) {
            if ( (int) get_option( 'hwcf_cripple_bots', 0 ) !== 1 ) {
                return $passed;
            }
            
            // Only set session flag if request appears legitimate
            if ( $this->is_legitimate_request() ) {
                if ( function_exists( 'WC' ) && WC()->session ) {
                    WC()->session->set( 'hwcf_valid_cart_session', true );
                }
            }
            return $passed;
        }



        /**
         * Adds product category slugs as CSS classes to column attributes.
         * @param array $attr Column attributes.
         * @return array Modified attributes with category classes.
         */
        public function product_column_attributes( $attr ) {
            if ( is_product() || is_shop() || is_product_category() ) {
                global $post;
                // Get the product's categories
                $terms = get_the_terms( $post->ID, 'product_cat' );

                if ( $terms && ! is_wp_error( $terms ) ) {
                    foreach ( $terms as $term ) {
                        if ( isset( $attr['class'] ) ) {
                            $attr['class'] .= ' product_cat-' . $term->slug;
                        } else {
                            $attr['class'] = 'product_cat-' . $term->slug;
                        }
                    }
                }

            }
    
            return $attr;

        }


        /**
         * Display login button on single product page
         * Replaces Add to Cart button for guests when enabled
         * 
         * @since 1.2.16
         */
        public function display_login_button() {
            // Only for non-logged in users
            if ( is_user_logged_in() ) {
                return;
            }

            global $product;
            if ( ! $product ) {
                return;
            }

            $settings_data = hwcf_get_hwcf_data();
            if ( empty( $settings_data ) || ! is_array( $settings_data ) ) {
                return;
            }

            foreach ( $settings_data as $option ) {
                // Check if rule is disabled
                if ( isset( $option['hwcf_disabled'] ) && (int) $option['hwcf_disabled'] > 0 ) {
                    continue;
                }

                // Check if Guests Only is enabled
                $loggedin_users = isset( $option['loggedinUsers'] ) ? explode( ",", $option['loggedinUsers'] ) : array();
                if ( ! in_array( 'guestonly', $loggedin_users ) ) {
                    continue;
                }

                // Check if Show Login Button is enabled
                if ( ! isset( $option['hwcf_show_login_button'] ) || (int) $option['hwcf_show_login_button'] < 1 ) {
                    continue;
                }

                // Check if this rule applies to this product
                if ( ! $this->rule_applies_to_product( $option, $product->get_id() ) ) {
                    continue;
                }

                // Get button text
                $login_button_text_key = hwcf_get_key_for_language( 'login_button_text' );
                $button_text = isset( $option[$login_button_text_key] ) && ! empty( $option[$login_button_text_key] ) 
                    ? $option[$login_button_text_key] 
                    : __( 'Login to See Prices', 'hide-cart-functions' );

                // Get return URL
                $return_url = $this->get_login_return_url( $option, $product->get_id() );

                // Build login URL
                $login_url = wp_login_url( $return_url );
                if ( function_exists( 'wc_get_page_id' ) ) {
                    $myaccount_page_id = wc_get_page_id( 'myaccount' );
                    if ( $myaccount_page_id > 0 ) {
                        $login_url = add_query_arg( 'redirect_to', urlencode( $return_url ), get_permalink( $myaccount_page_id ) );
                    }
                }

                // Output button
                echo '<div class="hwcf-login-button-wrap">';
                echo '<a href="' . esc_url( $login_url ) . '" class="button alt hwcf-login-button">' . esc_html( $button_text ) . '</a>';
                echo '</div>';
                
                break; // Only show one login button
            }
        }

        /**
         * Display login button on shop/archive pages
         * 
         * @since 1.2.16
         */
        public function display_login_button_loop() {
            // Only for non-logged in users
            if ( is_user_logged_in() ) {
                return;
            }

            global $product;
            if ( ! $product ) {
                return;
            }

            $settings_data = hwcf_get_hwcf_data();
            if ( empty( $settings_data ) || ! is_array( $settings_data ) ) {
                return;
            }

            foreach ( $settings_data as $option ) {
                // Check if rule is disabled
                if ( isset( $option['hwcf_disabled'] ) && (int) $option['hwcf_disabled'] > 0 ) {
                    continue;
                }

                // Check if Guests Only is enabled
                $loggedin_users = isset( $option['loggedinUsers'] ) ? explode( ",", $option['loggedinUsers'] ) : array();
                if ( ! in_array( 'guestonly', $loggedin_users ) ) {
                    continue;
                }

                // Check if Show Login Button AND Hide Add to Cart are enabled
                if ( ! isset( $option['hwcf_show_login_button'] ) || (int) $option['hwcf_show_login_button'] < 1 ) {
                    continue;
                }
                if ( ! isset( $option['hwcf_hide_add_to_cart'] ) || (int) $option['hwcf_hide_add_to_cart'] < 1 ) {
                    continue;
                }

                // Check if this rule applies to this product
                if ( ! $this->rule_applies_to_product( $option, $product->get_id() ) ) {
                    continue;
                }

                // Get button text
                $login_button_text_key = hwcf_get_key_for_language( 'login_button_text' );
                $button_text = isset( $option[$login_button_text_key] ) && ! empty( $option[$login_button_text_key] ) 
                    ? $option[$login_button_text_key] 
                    : __( 'Login to See Prices', 'hide-cart-functions' );

                // Get return URL
                $return_url = $this->get_login_return_url( $option, $product->get_id() );

                // Build login URL
                $login_url = wp_login_url( $return_url );
                if ( function_exists( 'wc_get_page_id' ) ) {
                    $myaccount_page_id = wc_get_page_id( 'myaccount' );
                    if ( $myaccount_page_id > 0 ) {
                        $login_url = add_query_arg( 'redirect_to', urlencode( $return_url ), get_permalink( $myaccount_page_id ) );
                    }
                }

                // Output button
                echo '<a href="' . esc_url( $login_url ) . '" class="button hwcf-login-button">' . esc_html( $button_text ) . '</a>';
                
                break; // Only show one login button
            }
        }

        /**
         * Check if a rule applies to a specific product
         * 
         * @param array $option Rule settings
         * @param int $product_id Product ID
         * @return bool
         * @since 1.2.16
         */
        private function rule_applies_to_product( $option, $product_id ) {
            $products_limit = isset( $option['hwcf_products'] ) ? trim( $option['hwcf_products'] ) : '';
            $categories_limit = isset( $option['hwcf_categories'] ) ? $option['hwcf_categories'] : array();

            // If no product or category limits, rule applies to all
            if ( empty( $products_limit ) && empty( $categories_limit ) ) {
                return true;
            }

            // Check product IDs
            if ( ! empty( $products_limit ) ) {
                $product_ids = array_map( 'trim', explode( ',', $products_limit ) );
                $product_ids = array_map( 'intval', $product_ids );
                if ( in_array( $product_id, $product_ids ) ) {
                    return true;
                }
            }

            // Check categories
            if ( ! empty( $categories_limit ) && is_array( $categories_limit ) ) {
                $product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
                if ( ! is_wp_error( $product_cats ) ) {
                    foreach ( $categories_limit as $cat_id ) {
                        if ( in_array( (int) $cat_id, $product_cats ) ) {
                            return true;
                        }
                    }
                }
            }

            // If we have limits but product doesn't match, return false
            if ( ! empty( $products_limit ) || ! empty( $categories_limit ) ) {
                return false;
            }

            return true;
        }

        /**
         * Get the return URL after login based on rule settings
         * 
         * @param array $option Rule settings
         * @param int $product_id Product ID
         * @return string
         * @since 1.2.16
         */
        private function get_login_return_url( $option, $product_id ) {
            $return_type = isset( $option['hwcf_login_return_url'] ) ? $option['hwcf_login_return_url'] : 'product';

            switch ( $return_type ) {
                case 'shop':
                    if ( function_exists( 'wc_get_page_id' ) ) {
                        return get_permalink( wc_get_page_id( 'shop' ) );
                    }
                    return home_url( '/shop/' );

                case 'home':
                    return home_url();

                case 'account':
                    if ( function_exists( 'wc_get_page_id' ) ) {
                        return get_permalink( wc_get_page_id( 'myaccount' ) );
                    }
                    return home_url();

                case 'product':
                default:
                    return get_permalink( $product_id );
            }
        }



        public function activation() {
            HWCF_Fix_Double_Selection();
        }
    }

    HWCF_GLOBAl::init();
}
