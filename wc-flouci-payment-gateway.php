<?php
/**
 * Plugin Name: Flouci Payment Gateway
 * Description: Enable secure online payments in WooCommerce with Flouci. Accept Credit Cards, E-Dinar, and Flouci with a smooth checkout experience.
 * Version: 1.0
 * Author: Majd Sassi
 * Author URI: https://github.com/majdsassi
 * Contributors : Majd Sassi
 * Requires at least: 4.0 
 * Tested on :  6.5.5
 * WC requires at least: 6.0
 * WC tested on : 9.0.2
 * @package Flouci Payment Gateway for WooCommerce
 * @author Majd Sassi
 */

if (!defined('ABSPATH')) exit;

// HPOS Compatibility
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Load Gateway
add_action('plugins_loaded', 'flouci_init_gateway_class');
function flouci_init_gateway_class()
{
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Flouci extends WC_Payment_Gateway
    {
        public function __construct()
        {
            $this->id = 'flouci';
            $this->method_title = 'Flouci';
            $this->method_description = 'Accept payments via Flouci';
            $this->icon= plugins_url('images/we-accept.png', __FILE__);
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->app_token = $this->get_option('app_token');
            $this->app_secret = $this->get_option('app_secret');
            $this->success_link = home_url('/');
            $this->fail_link = home_url('/checkout');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
        }

        public function init_form_fields()
        {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Enable/Disable',
                    'type' => 'checkbox',
                    'label' => 'Enable Flouci Gateway',
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => 'Title',
                    'type' => 'text',
                    'default' => 'Carte bancaire / E-dinar / Flouci '
                ],
                'description' => [
                    'title' => 'Description',
                    'type' => 'textarea',
                    'default' => 'Pay securely using Flouci.'
                ],
                'app_token' => [
                    'title' => 'Flouci App Token',
                    'type' => 'text'
                ],
                'app_secret' => [
                    'title' => 'Flouci App Secret',
                    'type' => 'password'
                ],
            ];
        }

        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            return [
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            ];
        }

        public function receipt_page($order_id)
        {
            $order = wc_get_order($order_id);
            $payment_link = $this->generate_flouci_payment_link($order);

            if (is_wp_error($payment_link)) {
                wc_add_notice('Error: ' . $payment_link->get_error_message(), 'error');
                error_log('Flouci Error: ' . $payment_link->get_error_message());
                echo '<p>Error generating payment link. Please contact support.</p>';
                return;
            }

            wp_redirect($payment_link);
            exit;
        }

        private function generate_flouci_payment_link($order)
        {
            $amount = intval($order->get_total() * 1000); // millimes
            $tracking_id = 'wc_order_' . $order->get_id();

            $body = [
                'app_token' => $this->app_token,
                'app_secret' => $this->app_secret,
                'accept_card' => "true",
                'amount' => strval($amount),
                'success_link' => $this->success_link,
                'fail_link' => $this->fail_link,
                'developer_tracking_id' => strval($tracking_id),
                'session_timeout_secs' => 1200
            ];

            $response = wp_safe_remote_post('https://developers.flouci.com/api/generate_payment', [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode($body),
                'timeout' => 15
            ]);

            if (is_wp_error($response)) {
                return new WP_Error('http_error', 'HTTP request failed: ' . $response->get_error_message());
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($data['result']['link']) || !$data['result']['success']) {
                return new WP_Error('api_error', 'Invalid Flouci API response: ' . wp_remote_retrieve_body($response));
            }

            return $data['result']['link'];
        }
    }
}

// Register Gateway
add_filter('woocommerce_payment_gateways', 'add_flouci_gateway_class');
function add_flouci_gateway_class($methods)
{
    $methods[] = 'WC_Gateway_Flouci';
    return $methods;
}

// Handle Flouci Payment Verification via Redirect (?payment_id=...)
add_action('init', 'flouci_handle_payment_verification');
function flouci_handle_payment_verification()
{
    if (!isset($_GET['payment_id'])) return;

    $payment_id = sanitize_text_field($_GET['payment_id']);
    error_log("⚡ Verifying Flouci payment with ID: $payment_id");
    
    // Get the WC_Gateway_Flouci instance
    $gateway = WC()->payment_gateways->get_available_payment_gateways()['flouci'];

    if ($gateway) {
        $app_token = $gateway->get_option('app_token');
        $app_secret = $gateway->get_option('app_secret');
        
    }

    $verify_url = 'https://developers.flouci.com/api/verify_payment/' . $payment_id;

    $response = wp_remote_get($verify_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'apppublic' => $app_token,
            'appsecret' => $app_secret
        ]
    ]);


    if (is_wp_error($response)) {
        error_log("❌ Verification error: " . $response->get_error_message());
        wp_redirect(wc_get_checkout_url());
        exit;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    error_log("🔍 Verification response: " . print_r($data, true));

    if (!empty($data['result']['developer_tracking_id'])) {
        $order_id = (int) str_replace('wc_order_', '', $data['result']['developer_tracking_id']);
        $order = wc_get_order($order_id);

        if ($data['result']['status'] === 'SUCCESS' && $order) {
            $order->add_order_note("✅ Payment verified via Flouci. Transaction ID: {$payment_id}");
            $order->payment_complete();

            wp_redirect($order->get_checkout_order_received_url());
            exit;
        }
    }

    wc_add_notice(__(wp_remote_retrieve_body($response), 'woocommerce'), 'error');
    wp_redirect(wc_get_checkout_url());
    exit;
}
