<?php

use Moosend\TrackerFactory;
use Moosend\Tracker;

if (!class_exists('MooTracker')) {
    class MooTracker
    {

        /**
         * @var TrackerFactory
         */
        private $trackerFactory;

        /**
         * @var Tracker
         */
        private $tracker;
        private $siteId;

        public function __construct(TrackerFactory $trackerFactory, $siteId)
        {
            $this->trackerFactory = $trackerFactory;
            if (!empty($siteId)) {
                $this->tracker = $this->trackerFactory->create($siteId);
            }
            $this->siteId = $siteId;

            //hooks
            add_action('wp', [$this, 'initialLoad']);
            add_action('woocommerce_add_to_cart', [$this, 'addToCart'], 10, 6);
            add_action('woocommerce_checkout_order_processed', [$this, 'orderProcessed'], 10, 3);
            add_action('woocommerce_payment_complete', [$this, 'orderCompleted'], 10, 3);
            add_action('admin_menu', [$this, 'setupAdminPage']);
            add_action('admin_notices', [$this, 'adminWarnings']);
            add_filter('plugin_action_links_moosend-email-marketing/index.php', [$this, 'actionLinks']);
            // Enqueues the Website Tracking Script and prints the INIT inline with the site ID
            if (!is_admin()) {
                add_action('wp_print_scripts', [$this, 'mooprintscript']);
            }
        }

        public function mooprintscript()
        {
            ?>
            <script>
                // Moosend Tracking and Forms library
                !function (t, n, e, o, a) {
                    function d(t) {
                        var n = ~~(Date.now() / 3e5), o = document.createElement(e);
                        o.async = !0, o.src = t + "?ts=" + n;
                        var a = document.getElementsByTagName(e)[0];
                        a.parentNode.insertBefore(o, a)
                    }

                    t.MooTrackerObject = a, t[a] = t[a] || function () {
                        return t[a].q ? void t[a].q.push(arguments) : void (t[a].q = [arguments])
                    }, window.attachEvent ? window.attachEvent("onload", d.bind(this, o)) : window.addEventListener("load", d.bind(this, o), !1)
                }(window, document, "script", "//cdn.stat-track.com/statics/moosend-tracking.min.js", "mootrack");
                mootrack('setCookieNames', { userIdName: 'MOOSEND_USER_ID' });
                mootrack('init', '<?php echo esc_attr($this->siteId); ?>');
            </script>
            <?php
        }

        public function orderProcessed($order_id) {
            $order = new WC_Order($order_id);
            if($order->get_payment_method() == 'cod' || $order->get_payment_method() == 'cheque') $this->orderCompleted($order_id);
        }

        public function orderCompleted($order_id)
        {
            if (!$this->siteId || !$this->tracker) {
                return;
            }

            $order = new WC_Order($order_id);
            
            if ($order->get_billing_email()) {
                if (!$this->tracker->isIdentified($order->get_billing_email())) {

                    $order_full_name = sprintf('%s %s', $order->get_billing_first_name(), $order->get_billing_last_name());
                    $this->tracker->identify($order->get_billing_email(), $order_full_name);

                    if(!isset($_COOKIE['email'])) {
                        setrawcookie('email', $order->get_billing_email(), 0, '/');
                    }

                }
            }

            $trackerOrder = $this->tracker->createOrder($order->get_total());
            $products_factory = new WC_Product_Factory();
            $products = $order->get_items();

            foreach ($products as $product) {
                $instantiatedProduct = !!$product->get_variation_id() ? new WC_Product_Variation($product->get_variation_id()) : $products_factory->get_product($product['product_id']);

                if ($instantiatedProduct) {

                    $thumbnail_id = $instantiatedProduct->get_type() == 'variation' ? $instantiatedProduct->get_image_id() : get_post_thumbnail_id($instantiatedProduct->get_id());
                    $large_image = wp_get_attachment_image_src($thumbnail_id, 'large');
                    $large_image_url = $large_image ? $large_image[0] : '';
                    $productUrl = get_permalink($instantiatedProduct->get_id());

                    $variation = [];
                    $product_id = $instantiatedProduct->get_id();

                    if ($instantiatedProduct->get_type() == 'variation') {
                        $product_id = $instantiatedProduct->get_parent_id();
                        $product_variable = new WC_Product_Variable( $instantiatedProduct->get_parent_id() );
                        $variation = $product_variable->get_variation_attributes();
                    }

                    $itemQuantity = intval($product->get_quantity());
                    $itemPriceTotal = floatval(wc_get_price_excluding_tax($instantiatedProduct));
                    $variation['itemCategory'] = $this->getCatNames($product_id);

                    $trackerOrder->addProduct($product_id, (float)$instantiatedProduct->get_price(), $productUrl, $itemQuantity, $itemPriceTotal, $instantiatedProduct->get_title(), $large_image_url, $variation);
                }
            }

            try {
                $this->tracker->orderCompleted($trackerOrder);
            } catch (Exception $err) {
                add_action('wp_footer', [$this, 'addErrorMessageInFooter'], 99, 100);
            }

        }

        public function addToCart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
        {
            if (!$this->siteId || !$this->tracker) {
                return;
            }

            //add to order
            global $woocommerce;
            $product = $woocommerce->cart->get_cart_item($cart_item_key);

            if ($product) {
                //get post thumbnail
                $thumbnail_id = $product['data']->get_type() == 'variation' ? $product['data']->get_image_id() : get_post_thumbnail_id($product['data']->get_id());
                $large_image = wp_get_attachment_image_src($thumbnail_id, 'large');
                $large_image_url = $large_image ? $large_image[0] : '';
                $productUrl = get_permalink($product['data']->get_id());

                $variation = [];
                $product_id = $product['data']->get_id();

                if ($product['data']->get_type() == 'variation') {
                    $product_variable = new WC_Product_Variable( $product['product_id'] );
                    $variation = $product_variable->get_variation_attributes();
                    $product_id = $product['product_id'];
                }

                $itemPrice = floatval($product['data']->get_price());
                $itemTotalPrice = floatval(wc_get_price_excluding_tax($product['data']));
                $variation['itemCategory'] = $this->getCatNames($product_id);

                try {
                    $this->tracker->addToOrder($product_id, $itemPrice, $productUrl, $quantity, $itemTotalPrice, $product['data']->get_title(), $large_image_url, $variation);
                } catch (Exception $err) {
                    add_action('wp_footer', [$this, 'addErrorMessageInFooter'], 99, 100);
                }
            }
        }

        private function get_formatted_variation_attributes($product)
        {
            $variation_data = $product->get_variation_attributes();
            $product_parent_id = $product->get_parent_id();
            $product_parent = new WC_Product($product_parent_id);
            $attributes = $product_parent->get_attributes();

            $variation = [];

            if (is_array($variation_data)) {
                foreach ($attributes as $attribute) {

                    // Only deal with attributes that are variations
                    if (!$attribute['variation']) {
                        continue;
                    }

                    $variation_selected_value = isset($variation_data['attribute_' . sanitize_title($attribute['name'])]) ? $variation_data['attribute_' . sanitize_title($attribute['name'])] : '';
                    $variation_name = esc_html(wc_attribute_label($attribute['name']));

                    if ($variation_selected_value) {
                        $variation[$variation_name] = $variation_selected_value;
                    }
                }
            }

            return $variation;
        }

        /**
         *
         * @return string
         */
        private function getCurrentUrl()
        {
            global $wp;
            $current_url = home_url(add_query_arg([], $wp->request));
            return $current_url;
        }

        public function initialLoad()
        {
            if (!is_admin() && !empty($this->tracker) && !empty($this->siteId) && !array_key_exists('wc-ajax', $_REQUEST)
                && !array_key_exists('add-to-cart', $_REQUEST)) {
                $this->tracker->init($this->siteId);

                //identify
                $user = wp_get_current_user();

                if ($user->ID) {

                    $user_meta = get_userdata($user->data->ID);
                    $userName = $user_meta->first_name ? $user_meta->first_name . ' ' . $user_meta->last_name : $user->data->display_name;

                    $userEmail = $user->data->user_email;

                    if (!$this->tracker->isIdentified($userEmail)) {
                        try {
                            $this->tracker->identify($userEmail, $userName);
                            setrawcookie('email', $userEmail, 0, '/');
                            
                        } catch (Exception $err) {
                            add_action('wp_footer', [$this, 'addErrorMessageInFooter'], 99, 100);
                        }
                    }
                }

                //page view
                $actual_link = $this->getCurrentUrl();
                try {
                    if ($this->isWooCommerceInstalled() && $this->isProductPage()) {
                        $this->trackProductPageView($actual_link);
                    } else {
                        $this->tracker->pageView($actual_link);
                    }
                } catch (Exception $err) {
                    add_action('wp_footer', [$this, 'addErrorMessageInFooter'], 99, 100);
                }
            }
        }

        public function addErrorMessageInFooter()
        {
            echo esc_js('<script type="text/javascript">console.warn("' . esc_js(__('Could not track events for Moosend Website Tracking', MOO_TEXT_DOMAIN)) . '")</script>');
        }

        public function setupAdminPage()
        {
            add_options_page('Moosend Website Tracking', __('Moosend Website Tracking Settings', MOO_TEXT_DOMAIN), 'manage_options', 'moosend-website-tracking', [$this, 'renderSettingsPage']);
        }

        public function renderSettingsPage()
        {
            if (isset( $_POST['site_id'] ) && wp_verify_nonce( $_POST['site_id'], 'add_site_id' )) {
                $siteId = isset($_POST[MO_SITE_ID]) ? sanitize_text_field($_POST[MO_SITE_ID]) : '';
                $isSubmit = isset($_POST['submit']) ? true : false;

                if (!empty($siteId) && $isSubmit) {
                    update_option(MO_SITE_ID, $siteId);
                }
            }

            if(current_user_can('manage_options')):
            ?>
                <div class="wrap">
                    <h2>Moosend Website Tracking</h2>

                    <form method="post"
                        action="<?php menu_page_url('moosend-website-tracking') ?>">
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e('Enter your Website ID', MOO_TEXT_DOMAIN); ?></th>
                                <td>
                                    <?php wp_nonce_field('add_site_id', 'site_id'); ?>
                                    <input type="text" name="<?php echo esc_attr(MO_SITE_ID); ?>"
                                        value="<?php echo esc_attr(get_option(MO_SITE_ID)); ?>"/>
                                    <p class="description">Please find your Website ID by clicking the corresponding Tracked
                                        Website under your account settings of your Moosend Account (Account -> Tracked
                                        Websites).</p>
                                </td>
                            </tr>
                        </table>
                        <?php
                        submit_button(); ?>
                    </form>
                </div>
            <?php
            endif;
        }

        public function actionLinks($links)
        {
            $updatedLink = [
                '<a href="' . menu_page_url('moosend-website-tracking', false) . '">' . __('Settings', MOO_TEXT_DOMAIN) . '</a>',
            ];

            return array_merge($links, $updatedLink);
        }

        public function adminWarnings()
        {
            $hasSiteId = get_option(MO_SITE_ID);
            $hasSiteId = !empty($hasSiteId);

            if (!$hasSiteId):
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p>
                        <strong>
                            <a href="<?php menu_page_url('moosend-website-tracking'); ?>"><?php _e('In order to make it work, Moosend Website Tracking requires a Website ID.', MOO_TEXT_DOMAIN); ?></a>
                        </strong>
                    </p>
                </div>
            <?php
            endif;

            $siteIdFromPost = isset($_POST[MO_SITE_ID]) ? sanitize_text_field($_POST[MO_SITE_ID]) : '';
            $isSubmit = isset($_POST['submit']) ? true : false;

            if (empty($siteIdFromPost) && $isSubmit):
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php _e('Website ID cannot be blank.', MOO_TEXT_DOMAIN); ?>
                    </p>
                </div>
            <?php
            endif;

            if (!empty($siteIdFromPost) && $isSubmit):
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php _e('Website ID updated successfully', MOO_TEXT_DOMAIN); ?>
                    </p>
                </div>
            <?php
            endif;
        }

        /**
         * @return boolean
         */
        protected function isWooCommerceInstalled()
        {
            if (class_exists('WooCommerce')) {
                return true;
            }
            return false;
        }

        /**
         * @return bool|WC_Product
         */
        protected function isProductPage()
        {
            return wc_get_product(get_queried_object_id()) ? true : false;
        }

        /**
         * @param string $actual_link
         */
        protected function trackProductPageView($actual_link)
        {
            $product_id = get_queried_object_id();
            $product = new WC_Product($product_id);
            $itemImageAttachment = wp_get_attachment_image_src($product->get_image_id(), 'large');
            $itemImage = is_array($itemImageAttachment) ? reset($itemImageAttachment) : "";

            $properties = [
                [
                    'product' => [
                        'itemCode' => $product_id,
                        'itemPrice' => (float)$product->get_price(),
                        'itemUrl' => get_permalink($product_id),
                        'itemQuantity' => $product->get_stock_quantity() ?: 1,
                        'itemTotal' => (float)$product->get_price(),
                        'itemImage' => $itemImage,
                        'itemName' => $product->get_title(),
                        'itemDescription' => get_post($product_id)->post_excerpt,
                        'itemCategory' => $this->getCatNames($product_id),
                        'itemStockStatus' => $product->get_availability()['class']
                    ]
                ]
            ];

            $properties[0]['product'] = array_merge($properties[0]['product'], $this->getProductAttributes($product));
            $this->tracker->pageView($actual_link, $properties);
        }

        /**
         * @param string $product_id
         * @return string
         */
        protected function getCatNames($product_id)
        {
            $product_cats = wp_get_post_terms($product_id, 'product_cat');
            $product_cats_names = array_map(function ($cat) {
                return $cat->name;
            }, $product_cats);
            return implode(', ', $product_cats_names) ?: null;
        }

        /**
         * @param $product
         * @return array
         */
        protected function getProductAttributes($product)
        {
            $productAttrs = $product->get_attributes();
            $product_attributes = array_map(function ($attr) {
                if ($attr instanceof WC_Product_Attribute) {
                    return $attr->get_options();
                }
                return $attr;
            }, $productAttrs);
            return $product_attributes;
        }
    }
}
