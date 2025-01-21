<?php

/**
 * @package              HWCF_GLOBAl
 * @wordpress-plugin
 * 
 * Plugin Name:          Hide Cart Functions
 * Plugin URI:           http://wordpress.org/plugins/hide-cart-functions
 * Description:          Hide product's price, add to cart button, quantity selector, and product options on any product and order. Add message below or above description.
 * Version:              1.2.5
 * Author:               Artios Media
 * Author URI:           http://www.artiosmedia.com
 * Assisting Developer:  Arafat Rahman
 * Copyright:            © 2022-2024 Artios Media (email: contact@artiosmedia.com).
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:          hide-cart-functions
 * Domain Path:          /languages
 * Tested up to:         6.7.1
 * WC requires at least: 6.5.0
 * WC tested up to:      9.5.2
 * PHP tested up to:     8.3.13
 */

namespace Artiosmedia\WC_Purchase_Customization;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('HWCF_GLOBAl_VERSION', '1.2.5');
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
         * @var \selfx
         */
        private static $_instance;


        public function __construct() {
            // Load translation
            add_action('init', [$this, 'init_translation']);
            //apply hide selector settings
            add_action('wp_head', [$this, 'apply_settings']);
            //add short description message if added
            add_filter('woocommerce_short_description', [$this, 'short_description'], 999);

            //run plugin option clean-up on plugin deactivation
            register_deactivation_hook(__FILE__, [$this, 'deactivation']);
            register_activation_hook(__FILE__, [$this, 'activation']);
            add_filter("woocommerce_get_price_html", [$this, 'modify_woocommerce_price'], 999);
            add_filter("woocommerce_cart_item_price", [$this, 'modify_woocommerce_price'], 999);
            add_filter( 'fusion_attr_fusion-column', [ $this, 'product_column_attributes' ], 999,1 );
            add_filter('tinvwl_wishlist_item_price', [$this, 'modify_tinvwl_wishlist_item_price'], 999, 3);
            add_filter( 'tinvwl_wishlist_item_action_add_to_cart', [$this,'hide_add_to_cart_button'], 1, 3 );
            add_filter( 'tinvwl_wishlist_item_action_default_loop_button', [$this,'hide_add_to_cart_button'], 1, 3 );
            add_filter( 'tinvwl_wishlist_item_cb', [$this,'hide_select_checkbox_highest_priority'], 1, 3 );
            add_filter( 'tinvwl_manage_buttons_create', [$this,'tinvwl_hide_add_all_to_cart'], 1,  );



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
                    if (!is_user_logged_in() && in_array(1, $loggedin_users)) {
                    } elseif (is_user_logged_in() && in_array(2, $loggedin_users)) {
                    } elseif (isset($loggedin_users[0]) && $loggedin_users[0] == '') {
                    } else {
                        continue;
                    }

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

                    if (!is_user_logged_in() && in_array(1, $loggedin_users)) {
                    } elseif (is_user_logged_in() && in_array(2, $loggedin_users)) {
                    } elseif (isset($loggedin_users[0]) && $loggedin_users[0] == '') {
                    } else {
                        continue;
                    }

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

            if (empty($settings_data)) {
                return false;
            }

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    

                    $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;

                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        continue;
                    }

                    $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;


                    if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');

                      //  print_r($option['hwcf_categories']);

                      if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');
                        $matched_cats = array_intersect($product_cats_ids, $option['hwcf_categories']);
                        if (!empty($matched_cats)) {
                            return '';
                        }else{
                            return $checkbox_html;
                        }
                    }

          
                    }

                    if ($product_ids != null) {
                        $product_ids = explode(",", $product_ids);
                        $product_ids = array_filter(array_map('absint', $product_ids));
                        if (in_array($id, $product_ids)) {
                           return false;
                        }else{
                            return $checkbox_html;
                        }
                    }
                }
            }

            return $checkbox_html;
           

        }


        public function hide_add_to_cart_button( $value, $wl_product, $product ) {            
           
            $settings_data    = hwcf_get_hwcf_data();
            // Extract the product ID
            $id = isset($wl_product['product_id']) ? absint($wl_product['product_id']) : null;

            if (empty($settings_data)) {
                return false;
            }

            if (!empty($settings_data) && is_array($settings_data)) {
                foreach ($settings_data as $option) {
                    

                    $product_ids = isset($option['hwcf_products']) ? $option['hwcf_products'] : null;

                    if (isset($option['hwcf_disable']) && (int)$option['hwcf_disable'] > 0) {
                        continue;
                    }

                    $hide_add_to_cart = isset($option['hwcf_hide_add_to_cart']) ? (int)($option['hwcf_hide_add_to_cart']) : 0;


                    if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');

                      //  print_r($option['hwcf_categories']);

                      if (isset($option['hwcf_categories']) && is_array($option['hwcf_categories'])) {
                        $product_cats_ids = wc_get_product_term_ids($id, 'product_cat');
                        $matched_cats = array_intersect($product_cats_ids, $option['hwcf_categories']);
                        if (!empty($matched_cats)) {
                            return false;
                        }else{
                            return true;
                        }
                    }

          
                    }

                    if ($product_ids != null) {
                        $product_ids = explode(",", $product_ids);
                        $product_ids = array_filter(array_map('absint', $product_ids));
                        if (in_array($id, $product_ids)) {
                           return false;
                        }else{
                            return true; 
                        }
                    }
                }
            }
           
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
         * Run plugin option clean-up on plugin deactivation
         * 
         * @since    1.0.0
         */
        public function deactivation() {
            if ((int)get_option('hwcf_delete_on_deactivation', 0) === 1) {
                delete_option('hwcf_delete_on_deactivation');
                delete_option('pcfw_notice_dismiss');
                delete_option('pcfw_version_1_0_0_installed');
                delete_option('hwcf_settings_data');
                delete_option('hwcf_settings_ids_increament');
            }
        }
        public function activation() {
            HWCF_Fix_Double_Selection();
        }
    }

    HWCF_GLOBAl::init();
}



add_action('initd', function () {






    var_dump($languages);
    exit;
});
