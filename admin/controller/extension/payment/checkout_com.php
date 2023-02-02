<?php
require_once(DIR_SYSTEM . 'library/vendor/checkout_com/checkout.php');

use Checkout\CheckoutApi;
use Checkout\Library\Exceptions\CheckoutException;
use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Library\Exceptions\CheckoutModelException;
use Checkout\Models\Payments\Refund;
use Checkout\Models\Payments\Voids;
use Checkout\Models\Webhooks\Webhook;

class ControllerExtensionPaymentCheckoutCom extends Controller {
    private $error = array();
    private $version = '1.2.0';

    public function index() {
        $data = $this->load->language('extension/payment/checkout_com');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_checkout_com', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        if (!$this->config->get('payment_checkout_com_captured_status_id')) {
            $data['error_order_statuses'] = $this->language->get('error_order_statuses');
        } else {
            $data['error_order_statuses'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['public_key'])) {
            $data['error_public_key'] = $this->error['public_key'];
        } else {
            $data['error_public_key'] = '';
        }

        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        if (isset($this->error['payment_title'])) {
            $data['error_payment_title'] = $this->error['payment_title'];
        } else {
            $data['error_payment_title'] = '';
        }

        if (isset($this->error['billing_descriptor_name'])) {
            $data['error_billing_descriptor_name'] = $this->error['billing_descriptor_name'];
        } else {
            $data['error_billing_descriptor_name'] = '';
        }

        if (isset($this->error['billing_descriptor_city'])) {
            $data['error_billing_descriptor_city'] = $this->error['billing_descriptor_city'];
        } else {
            $data['error_billing_descriptor_city'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/checkout_com', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/checkout_com', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_checkout_com_public_key'])) {
            $data['payment_checkout_com_public_key'] = $this->request->post['payment_checkout_com_public_key'];
        } else {
            $data['payment_checkout_com_public_key'] = $this->config->get('payment_checkout_com_public_key');
        }

        if (isset($this->request->post['payment_checkout_com_secret_key'])) {
            $data['payment_checkout_com_secret_key'] = $this->request->post['payment_checkout_com_secret_key'];
        } else {
            $data['payment_checkout_com_secret_key'] = $this->config->get('payment_checkout_com_secret_key');
        }

        if (isset($this->request->post['payment_checkout_com_test'])) {
            $data['payment_checkout_com_test'] = $this->request->post['payment_checkout_com_test'];
        } else {
            $data['payment_checkout_com_test'] = $this->config->get('payment_checkout_com_test');
        }

        if (isset($this->request->post['payment_checkout_com_total'])) {
            $data['payment_checkout_com_total'] = $this->request->post['payment_checkout_com_total'];
        } else {
            $data['payment_checkout_com_total'] = $this->config->get('payment_checkout_com_total');
        }

        if (isset($this->request->post['payment_checkout_com_payment_title'])) {
            $data['payment_checkout_com_payment_title'] = $this->request->post['payment_checkout_com_payment_title'];
        } else {
            $data['payment_checkout_com_payment_title'] = $this->config->get('payment_checkout_com_payment_title');
        }

        if (isset($this->request->post['payment_checkout_com_billing_descriptor_status'])) {
            $data['payment_checkout_com_billing_descriptor_status'] = $this->request->post['payment_checkout_com_billing_descriptor_status'];
        } else {
            $data['payment_checkout_com_billing_descriptor_status'] = $this->config->get('payment_checkout_com_billing_descriptor_status');
        }

        if (isset($this->request->post['payment_checkout_com_billing_descriptor_name'])) {
            $data['payment_checkout_com_billing_descriptor_name'] = $this->request->post['payment_checkout_com_billing_descriptor_name'];
        } else {
            $data['payment_checkout_com_billing_descriptor_name'] = $this->config->get('payment_checkout_com_billing_descriptor_name');
        }

        if (isset($this->request->post['payment_checkout_com_billing_descriptor_city'])) {
            $data['payment_checkout_com_billing_descriptor_city'] = $this->request->post['payment_checkout_com_billing_descriptor_city'];
        } else {
            $data['payment_checkout_com_billing_descriptor_city'] = $this->config->get('payment_checkout_com_billing_descriptor_city');
        }

        if (isset($this->request->post['payment_checkout_com_payment_action'])) {
            $data['payment_checkout_com_payment_action'] = $this->request->post['payment_checkout_com_payment_action'];
        } else {
            $data['payment_checkout_com_payment_action'] = $this->config->get('payment_checkout_com_payment_action');
        }

        if (isset($this->request->post['payment_checkout_com_capture_delay'])) {
            $data['payment_checkout_com_capture_delay'] = $this->request->post['payment_checkout_com_capture_delay'];
        } else {
            $data['payment_checkout_com_capture_delay'] = $this->config->get('payment_checkout_com_capture_delay');
        }

        if (isset($this->request->post['payment_checkout_com_3d_secure'])) {
            $data['payment_checkout_com_3d_secure'] = $this->request->post['payment_checkout_com_3d_secure'];
        } else {
            $data['payment_checkout_com_3d_secure'] = $this->config->get('payment_checkout_com_3d_secure');
        }

        if (isset($this->request->post['payment_checkout_com_attempt_non_3d'])) {
            $data['payment_checkout_com_attempt_non_3d'] = $this->request->post['payment_checkout_com_attempt_non_3d'];
        } else {
            $data['payment_checkout_com_attempt_non_3d'] = $this->config->get('payment_checkout_com_attempt_non_3d');
        }

        if (isset($this->request->post['payment_checkout_com_card'])) {
            $data['payment_checkout_com_card'] = $this->request->post['payment_checkout_com_card'];
        } else {
            $data['payment_checkout_com_card'] = $this->config->get('payment_checkout_com_card');
        }

        if (isset($this->request->post['payment_checkout_com_require_cvv'])) {
            $data['payment_checkout_com_require_cvv'] = $this->request->post['payment_checkout_com_require_cvv'];
        } else {
            $data['payment_checkout_com_require_cvv'] = $this->config->get('payment_checkout_com_require_cvv');
        }

        if (isset($this->request->post['payment_checkout_com_geo_zone_id'])) {
            $data['payment_checkout_com_geo_zone_id'] = $this->request->post['payment_checkout_com_geo_zone_id'];
        } else {
            $data['payment_checkout_com_geo_zone_id'] = $this->config->get('payment_checkout_com_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_checkout_com_status'])) {
            $data['payment_checkout_com_status'] = $this->request->post['payment_checkout_com_status'];
        } else {
            $data['payment_checkout_com_status'] = $this->config->get('payment_checkout_com_status');
        }

        if (isset($this->request->post['payment_checkout_com_sort_order'])) {
            $data['payment_checkout_com_sort_order'] = $this->request->post['payment_checkout_com_sort_order'];
        } else {
            $data['payment_checkout_com_sort_order'] = $this->config->get('payment_checkout_com_sort_order');
        }

        if (isset($this->request->post['payment_checkout_com_mada_bin_check_status'])) {
            $data['payment_checkout_com_mada_bin_check_status'] = $this->request->post['payment_checkout_com_mada_bin_check_status'];
        } else {
            $data['payment_checkout_com_mada_bin_check_status'] = $this->config->get('payment_checkout_com_mada_bin_check_status');
        }

        if (isset($this->request->post['payment_checkout_com_display_card_icons_status'])) {
            $data['payment_checkout_com_display_card_icons_status'] = $this->request->post['payment_checkout_com_display_card_icons_status'];
        } else {
            $data['payment_checkout_com_display_card_icons_status'] = $this->config->get('payment_checkout_com_display_card_icons_status');
        }

        if (isset($this->request->post['payment_checkout_com_card_icons'])) {
            $data['payment_checkout_com_card_icons'] = $this->request->post['payment_checkout_com_card_icons'];
        } elseif ($this->config->get('payment_checkout_com_card_icons')) {
            $data['payment_checkout_com_card_icons'] = $this->config->get('payment_checkout_com_card_icons');
        } else {
            $data['payment_checkout_com_card_icons'] = array();
        }

        $data['card_icons'] = array(
            'visa'        => $this->language->get('text_visa'),
            'mastercard'  => $this->language->get('text_mastercard'),
            'amex'        => $this->language->get('text_amex'),
            'dinersclub'  => $this->language->get('text_dinersclub'),
            'discover'    => $this->language->get('text_discover'),
            'jcb'         => $this->language->get('text_jcb'),
            'mada'        => $this->language->get('text_mada')
        );

        // Order Statuses
        if (isset($this->request->post['payment_checkout_com_card_verification_declined_status_id'])) {
            $data['payment_checkout_com_card_verification_declined_status_id'] = $this->request->post['payment_checkout_com_card_verification_declined_status_id'];
        } else {
            $data['payment_checkout_com_card_verification_declined_status_id'] = $this->config->get('payment_checkout_com_card_verification_declined_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_card_verified_status_id'])) {
            $data['payment_checkout_com_card_verified_status_id'] = $this->request->post['payment_checkout_com_card_verified_status_id'];
        } else {
            $data['payment_checkout_com_card_verified_status_id'] = $this->config->get('payment_checkout_com_card_verified_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_approved_status_id'])) {
            $data['payment_checkout_com_approved_status_id'] = $this->request->post['payment_checkout_com_approved_status_id'];
        } else {
            $data['payment_checkout_com_approved_status_id'] = $this->config->get('payment_checkout_com_approved_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_risk_matched_status_id'])) {
            $data['payment_checkout_com_risk_matched_status_id'] = $this->request->post['payment_checkout_com_risk_matched_status_id'];
        } else {
            $data['payment_checkout_com_risk_matched_status_id'] = $this->config->get('payment_checkout_com_risk_matched_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_pending_status_id'])) {
            $data['payment_checkout_com_pending_status_id'] = $this->request->post['payment_checkout_com_pending_status_id'];
        } else {
            $data['payment_checkout_com_pending_status_id'] = $this->config->get('payment_checkout_com_pending_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_declined_status_id'])) {
            $data['payment_checkout_com_declined_status_id'] = $this->request->post['payment_checkout_com_declined_status_id'];
        } else {
            $data['payment_checkout_com_declined_status_id'] = $this->config->get('payment_checkout_com_declined_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_expired_status_id'])) {
            $data['payment_checkout_com_expired_status_id'] = $this->request->post['payment_checkout_com_expired_status_id'];
        } else {
            $data['payment_checkout_com_expired_status_id'] = $this->config->get('payment_checkout_com_expired_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_canceled_status_id'])) {
            $data['payment_checkout_com_canceled_status_id'] = $this->request->post['payment_checkout_com_canceled_status_id'];
        } else {
            $data['payment_checkout_com_canceled_status_id'] = $this->config->get('payment_checkout_com_canceled_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_voided_status_id'])) {
            $data['payment_checkout_com_voided_status_id'] = $this->request->post['payment_checkout_com_voided_status_id'];
        } else {
            $data['payment_checkout_com_voided_status_id'] = $this->config->get('payment_checkout_com_voided_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_void_declined_status_id'])) {
            $data['payment_checkout_com_void_declined_status_id'] = $this->request->post['payment_checkout_com_void_declined_status_id'];
        } else {
            $data['payment_checkout_com_void_declined_status_id'] = $this->config->get('payment_checkout_com_void_declined_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_captured_status_id'])) {
            $data['payment_checkout_com_captured_status_id'] = $this->request->post['payment_checkout_com_captured_status_id'];
        } else {
            $data['payment_checkout_com_captured_status_id'] = $this->config->get('payment_checkout_com_captured_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_capture_declined_status_id'])) {
            $data['payment_checkout_com_capture_declined_status_id'] = $this->request->post['payment_checkout_com_capture_declined_status_id'];
        } else {
            $data['payment_checkout_com_capture_declined_status_id'] = $this->config->get('payment_checkout_com_capture_declined_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_capture_pending_status_id'])) {
            $data['payment_checkout_com_capture_pending_status_id'] = $this->request->post['payment_checkout_com_capture_pending_status_id'];
        } else {
            $data['payment_checkout_com_capture_pending_status_id'] = $this->config->get('payment_checkout_com_capture_pending_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_refunded_status_id'])) {
            $data['payment_checkout_com_refunded_status_id'] = $this->request->post['payment_checkout_com_refunded_status_id'];
        } else {
            $data['payment_checkout_com_refunded_status_id'] = $this->config->get('payment_checkout_com_refunded_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_refund_declined_status_id'])) {
            $data['payment_checkout_com_refund_declined_status_id'] = $this->request->post['payment_checkout_com_refund_declined_status_id'];
        } else {
            $data['payment_checkout_com_refund_declined_status_id'] = $this->config->get('payment_checkout_com_refund_declined_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_refund_pending_status_id'])) {
            $data['payment_checkout_com_refund_pending_status_id'] = $this->request->post['payment_checkout_com_refund_pending_status_id'];
        } else {
            $data['payment_checkout_com_refund_pending_status_id'] = $this->config->get('payment_checkout_com_refund_pending_status_id');
        }

        if (isset($this->request->post['payment_checkout_com_chargeback_status_id'])) {
            $data['payment_checkout_com_chargeback_status_id'] = $this->request->post['payment_checkout_com_chargeback_status_id'];
        } else {
            $data['payment_checkout_com_chargeback_status_id'] = $this->config->get('payment_checkout_com_chargeback_status_id');
        }

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        // Apple Pay
        if (isset($this->request->post['payment_checkout_com_apple_pay_status'])) {
            $data['payment_checkout_com_apple_pay_status'] = $this->request->post['payment_checkout_com_apple_pay_status'];
        } else {
            $data['payment_checkout_com_apple_pay_status'] = $this->config->get('payment_checkout_com_apple_pay_status');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_payment_title'])) {
            $data['payment_checkout_com_apple_pay_payment_title'] = $this->request->post['payment_checkout_com_apple_pay_payment_title'];
        } else {
            $data['payment_checkout_com_apple_pay_payment_title'] = $this->config->get('payment_checkout_com_apple_pay_payment_title');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_merchant_id'])) {
            $data['payment_checkout_com_apple_pay_merchant_id'] = $this->request->post['payment_checkout_com_apple_pay_merchant_id'];
        } else {
            $data['payment_checkout_com_apple_pay_merchant_id'] = $this->config->get('payment_checkout_com_apple_pay_merchant_id');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_certificate'])) {
            $data['payment_checkout_com_apple_pay_certificate'] = $this->request->post['payment_checkout_com_apple_pay_certificate'];
        } else {
            $data['payment_checkout_com_apple_pay_certificate'] = $this->config->get('payment_checkout_com_apple_pay_certificate');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_certificate_key'])) {
            $data['payment_checkout_com_apple_pay_certificate_key'] = $this->request->post['payment_checkout_com_apple_pay_certificate_key'];
        } else {
            $data['payment_checkout_com_apple_pay_certificate_key'] = $this->config->get('payment_checkout_com_apple_pay_certificate_key');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_mada_status'])) {
            $data['payment_checkout_com_apple_pay_mada_status'] = $this->request->post['payment_checkout_com_apple_pay_mada_status'];
        } else {
            $data['payment_checkout_com_apple_pay_mada_status'] = $this->config->get('payment_checkout_com_apple_pay_mada_status');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_button_theme'])) {
            $data['payment_checkout_com_apple_pay_button_theme'] = $this->request->post['payment_checkout_com_apple_pay_button_theme'];
        } else {
            $data['payment_checkout_com_apple_pay_button_theme'] = $this->config->get('payment_checkout_com_apple_pay_button_theme');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_button_type'])) {
            $data['payment_checkout_com_apple_pay_button_type'] = $this->request->post['payment_checkout_com_apple_pay_button_type'];
        } else {
            $data['payment_checkout_com_apple_pay_button_type'] = $this->config->get('payment_checkout_com_apple_pay_button_type');
        }

        if (isset($this->request->post['payment_checkout_com_apple_pay_sort_order'])) {
            $data['payment_checkout_com_apple_pay_sort_order'] = $this->request->post['payment_checkout_com_apple_pay_sort_order'];
        } else {
            $data['payment_checkout_com_apple_pay_sort_order'] = $this->config->get('payment_checkout_com_apple_pay_sort_order');
        }

        // Google Pay
        if (isset($this->request->post['payment_checkout_com_google_pay_status'])) {
            $data['payment_checkout_com_google_pay_status'] = $this->request->post['payment_checkout_com_google_pay_status'];
        } else {
            $data['payment_checkout_com_google_pay_status'] = $this->config->get('payment_checkout_com_google_pay_status');
        }

        if (isset($this->request->post['payment_checkout_com_google_pay_payment_title'])) {
            $data['payment_checkout_com_google_pay_payment_title'] = $this->request->post['payment_checkout_com_google_pay_payment_title'];
        } else {
            $data['payment_checkout_com_google_pay_payment_title'] = $this->config->get('payment_checkout_com_google_pay_payment_title');
        }

        if (isset($this->request->post['payment_checkout_com_google_pay_merchant_id'])) {
            $data['payment_checkout_com_google_pay_merchant_id'] = $this->request->post['payment_checkout_com_google_pay_merchant_id'];
        } else {
            $data['payment_checkout_com_google_pay_merchant_id'] = $this->config->get('payment_checkout_com_google_pay_merchant_id');
        }

        if (isset($this->request->post['payment_checkout_com_google_pay_button_colour'])) {
            $data['payment_checkout_com_google_pay_button_colour'] = $this->request->post['payment_checkout_com_google_pay_button_colour'];
        } else {
            $data['payment_checkout_com_google_pay_button_colour'] = $this->config->get('payment_checkout_com_google_pay_button_colour');
        }

        if (isset($this->request->post['payment_checkout_com_google_pay_button_type'])) {
            $data['payment_checkout_com_google_pay_button_type'] = $this->request->post['payment_checkout_com_google_pay_button_type'];
        } else {
            $data['payment_checkout_com_google_pay_button_type'] = $this->config->get('payment_checkout_com_google_pay_button_type');
        }

        if (isset($this->request->post['payment_checkout_com_google_pay_sort_order'])) {
            $data['payment_checkout_com_google_pay_sort_order'] = $this->request->post['payment_checkout_com_google_pay_sort_order'];
        } else {
            $data['payment_checkout_com_google_pay_sort_order'] = $this->config->get('payment_checkout_com_google_pay_sort_order');
        }

        if (isset($this->request->post['payment_checkout_com_apm_status'])) {
            $data['payment_checkout_com_apm_status'] = $this->request->post['payment_checkout_com_apm_status'];
        } else {
            $data['payment_checkout_com_apm_status'] = $this->config->get('payment_checkout_com_apm_status');
        }

        if (isset($this->request->post['payment_checkout_com_apm_payment_title'])) {
            $data['payment_checkout_com_apm_payment_title'] = $this->request->post['payment_checkout_com_apm_payment_title'];
        } else {
            $data['payment_checkout_com_apm_payment_title'] = $this->config->get('payment_checkout_com_apm_payment_title');
        }

        $data['apm_payment_methods'] = ['alipay', 'paypal', 'bancontact', 'knet', 'qpay'];

        if (isset($this->request->post['payment_checkout_com_apm_payment_method'])) {
            $data['payment_checkout_com_apm_payment_method'] = $this->request->post['payment_checkout_com_apm_payment_method'];
        } elseif ($this->config->get('payment_checkout_com_apm_payment_method')) {
            $data['payment_checkout_com_apm_payment_method'] = $this->config->get('payment_checkout_com_apm_payment_method');
        } else {
            $data['payment_checkout_com_apm_payment_method'] = array();
        }

        if (isset($this->request->post['payment_checkout_com_apm_sort_order'])) {
            $data['payment_checkout_com_apm_sort_order'] = $this->request->post['payment_checkout_com_apm_sort_order'];
        } else {
            $data['payment_checkout_com_apm_sort_order'] = $this->config->get('payment_checkout_com_apm_sort_order');
        }
        
        $data['user_token'] = $this->session->data['user_token'];

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/checkout_com', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/checkout_com')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_checkout_com_public_key']) {
            $this->error['public_key'] = $this->language->get('error_public_key');
        }

        if (!$this->request->post['payment_checkout_com_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        if (!$this->request->post['payment_checkout_com_payment_title']) {
            $this->error['payment_title'] = $this->language->get('error_payment_title');
        }

        if ($this->request->post['payment_checkout_com_billing_descriptor_status']) {
            if (empty($this->request->post['payment_checkout_com_billing_descriptor_name']) || (utf8_strlen($this->request->post['payment_checkout_com_billing_descriptor_name']) > 25)) {
                $this->error['billing_descriptor_name'] = $this->language->get('error_billing_descriptor_name');
            }

            if (empty($this->request->post['payment_checkout_com_billing_descriptor_city']) || (utf8_strlen($this->request->post['payment_checkout_com_billing_descriptor_city']) > 13)) {
                $this->error['billing_descriptor_city'] = $this->language->get('error_billing_descriptor_city');
            }
        }
        
        if (!empty($this->request->post['payment_checkout_com_secret_key'])) {
            try {
                $url = HTTPS_CATALOG . 'index.php?route=extension/payment/checkout_com/webhook';
                
                if ($this->request->post['payment_checkout_com_test']) {
                    $checkout = new CheckoutApi($this->request->post['payment_checkout_com_secret_key']);
                } else {
                    $checkout = new CheckoutApi($this->request->post['payment_checkout_com_secret_key'], false);
                }
                
                $webhooks = $checkout->webhooks()->retrieve();

                $has_webhook = false;
                
                foreach ($webhooks->list as $webhook) {
                    if ($webhook->url == $url) {
                        $has_webhook = true;
                        
                        break;
                    }
                }
                
                if (!$has_webhook) {
                    $webhook = new Webhook($url);
                    
                    $events = [
                        'payment_approved',
                        'payment_pending',
                        'payment_declined',
                        'payment_expired',
                        'payment_canceled',
                        'payment_voided',
                        'payment_void_declined',
                        'payment_captured',
                        'payment_capture_declined',
                        'payment_capture_pending',
                        'payment_refunded',
                        'payment_refund_declined',
                        'payment_refund_pending',
                        'payment_chargeback'
                    ];
                    
                    $checkout->webhooks()->register($webhook, $events);
                }
            } catch (CheckoutHttpException $ex) {
                $error_code = $ex->getCode();

                $this->error['warning'] = $this->language->get('error_webhook');
            } catch (Exception $ex) {
                $this->error['warning'] = $this->language->get('error_webhook');
            }
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('setting/setting');
        $this->load->model('user/user_group');
        $this->load->model('extension/payment/checkout_com');

        $this->model_setting_extension->install('payment', 'checkout_com_apple_pay');
        $this->model_setting_extension->install('payment', 'checkout_com_google_pay');
        $this->model_setting_extension->install('payment', 'checkout_com_apm');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/checkout_com_apple_pay');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/checkout_com_google_pay');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/payment/checkout_com_apm');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/checkout_com_apple_pay');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/checkout_com_google_pay');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/payment/checkout_com_apm');

        $this->model_extension_payment_checkout_com->install();

        $this->load->language('extension/payment/checkout_com');

        $this->model_setting_setting->editSetting('payment_checkout_com', array('payment_checkout_com_payment_title' => $this->language->get('text_payment_title')));
    }

    public function uninstall() {
        $this->load->model('extension/payment/checkout_com');

        $this->model_setting_extension->uninstall('payment', 'checkout_com_apple_pay');
        $this->model_setting_extension->uninstall('payment', 'checkout_com_google_pay');
        $this->model_setting_extension->uninstall('payment', 'checkout_com_apm');

        $this->model_extension_payment_checkout_com->uninstall();
    }

    public function order() {
        if ($this->config->get('payment_checkout_com_status')) {
            $this->load->model('sale/order');
            $this->load->model('extension/payment/checkout_com');

            $checkout_com_order = $this->model_extension_payment_checkout_com->getOrder($this->request->get['order_id']);

            if (!empty($checkout_com_order)) {
                $data = $this->load->language('extension/payment/checkout_com');

                $data['checkout_com_order'] = $checkout_com_order;

                $order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

                if (!in_array($order_info['order_status_id'], $this->config->get('config_complete_status'))) {
                    $data['refresh_enabled'] = true;
                } else {
                    $data['refresh_enabled'] = false;
                }
        
                $data['order_id'] = $this->request->get['order_id'];
                $data['user_token'] = $this->session->data['user_token'];
        
                return $this->load->view('extension/payment/checkout_com_order', $data); 
            }
        }
    }

    public function action() {
        $json = array();

        if ($this->config->get('payment_checkout_com_test')) {
            $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
        } else {
            $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
        }
        
        $this->load->language('extension/payment/checkout_com');

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        
        if (isset($this->request->get['type']) && $this->request->get['type'] == 'refund') {
            $type = 'refund';
        } elseif (isset($this->request->get['type']) && $this->request->get['type'] == 'void') {
            $type = 'void';
        } else {
            $type = 'refresh';
        }

        if (isset($this->request->post['refund_amount'])) {
            $refund_amount = $this->request->post['refund_amount'];
        } else {
            $refund_amount = 0;
        }

        $this->load->model('extension/payment/checkout_com');

        $checkout_com_order_info = $this->model_extension_payment_checkout_com->getOrder($order_id);

        if ($checkout_com_order_info) {
            $payment_id = $checkout_com_order_info['payment_checkout_com_transaction_id'];

            if ($type == 'refund') {
                $refund = new Refund($payment_id);
                $refund->reference = $order_id;
                $refund->amount = (float)$refund_amount * 100;

                try {
                    $details = $checkout->payments()->refund($refund);

                    if ($details->isSuccessful()) {
                        $json['success'] = sprintf($this->language->get('text_refunded'), $checkout_com_order_info['order_id']);
                    } else {
                        $http_code = $details->getCode();

                        $json['error'] = $this->getErrorMessage($http_code, 'refund');
                    }
                } catch (CheckoutHttpException $ex) {
                    $error_code = $ex->getCode();

                    $json['error'] = $this->getErrorMessage($error_code, 'refund');
                } catch (Exception $ex) {
                    $json['error'] = $ex->getMessage();
                }
            } elseif ($type == 'void') {
                $voids = new Voids($payment_id);
                $voids->reference = $order_id;

                try {
                    $details = $checkout->payments()->void($voids);

                    if ($details->isSuccessful()) {
                        $json['success'] = sprintf($this->language->get('text_voided'), $checkout_com_order_info['order_id']);
                    } else {
                        $http_code = $details->getCode();

                        $json['error'] = $this->getErrorMessage($http_code, 'void');
                    }
                } catch (CheckoutHttpException $ex) {
                    $error_code = $ex->getCode();

                    $json['error'] = $this->getErrorMessage($error_code, 'void');
                } catch (Exception $ex) {
                    $json['error'] = $ex->getMessage();
                }
            } elseif ($type == 'refresh') {
                try {
                    $details = $checkout->payments()->details($payment_id);

                    if ($details->isSuccessful()) {
                        $payment_status = $details->getValue('status');
                        $amount = $details->getValue('amount') / 100;
                        $status = '';
                        $order_status_id = 0;
              
                        switch ($payment_status) {
                            case 'Pending':
                                $status = 'pending';
                                $order_status_id = $this->config->get('payment_checkout_com_pending_status_id');
                                break;
                            case 'Authorized':
                                $status = 'approved';
                                $order_status_id = $this->config->get('payment_checkout_com_approved_status_id');
                                break;
                            case 'Card Verified':
                                $status = 'cardverified';
                                $order_status_id = $this->config->get('payment_checkout_com_card_verified_status_id');
                                break;
                            case 'Voided':
                                $status = 'voided';
                                $order_status_id = $this->config->get('payment_checkout_com_voided_status_id');
                                break;
                            case 'Partially Captured':
                                $status = 'captured';
                                $order_status_id = $this->config->get('payment_checkout_com_captured_status_id');
                                break;
                            case 'Captured':
                                $status = 'captured';
                                $order_status_id = $this->config->get('payment_checkout_com_captured_status_id');
                                break;
                            case 'Partially Refunded':
                                $status = 'refunded';
                                $order_status_id = $this->config->get('payment_checkout_com_refunded_status_id');
                                break;
                            case 'Refunded':
                                $status = 'refunded';
                                $order_status_id = $this->config->get('payment_checkout_com_refunded_status_id');
                                break;
                            case 'Declined':
                                $status = 'declined';
                                $order_status_id = $this->config->get('payment_checkout_com_declined_status_id');
                                break;
                            case 'Cancelled':
                                $status = 'canceled';
                                $order_status_id = $this->config->get('payment_checkout_com_canceled_status_id');
                                break;
                            case 'Paid':
                                $status = 'captured';
                                $order_status_id = $this->config->get('payment_checkout_com_captured_status_id');
                                break;
                        }

                        $updated = false;

                        if ($order_status_id && $status) {
                            $comment = 'Checkout.com Payment. Transaction ID: ' . $checkout_com_order_info['checkout_com_transaction_id'];
                
                            $order_history_info = $this->model_extension_payment_checkout_com->getOrderHistory($checkout_com_order_info['order_id'], $order_status_id, $comment);
                            
                            if (!$order_history_info) {
                                $this->model_extension_payment_checkout_com->addOrderHistory($checkout_com_order_info['order_id'], $order_status_id, $comment);

                                $updated = true;
                                
                                $transaction_info = $this->model_extension_payment_checkout_com->getTransaction($checkout_com_order_info['checkout_com_order_id'], $status, $amount);

                                if (!$transaction_info) {
                                    $this->model_extension_payment_checkout_com->addTransaction($checkout_com_order_info['checkout_com_order_id'], $status, $amount);
                                }
                            }
                        }

                        if ($updated) {
                            $json['success'] = sprintf($this->language->get('text_refreshed'), $checkout_com_order_info['order_id']);
                        } else {
                            $json['success'] = sprintf($this->language->get('text_no_updates'), $checkout_com_order_info['order_id']);
                        }
                    } else {
                        $http_code = $details->getCode();

                        $json['error'] = $this->getErrorMessage($http_code, 'void');
                    }
                } catch (CheckoutHttpException $ex) {
                    $error_code = $ex->getCode();
                    
                    $json['error'] = $this->getErrorMessage($error_code, 'void');
                } catch (Exception $ex) {
                    $json['error'] = $ex->getMessage();
                }
            } else {
                $json['error'] = $this->language->get('error_not_found');
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    private function getErrorMessage($code, $type) {
        if ($code == 401) {
            $error_message = $this->language->get('error_unauthorised');
        } elseif ($code == 403) {
            if ($type == 'refund') {
                $error_message = $this->language->get('error_refund_not_allowed');
            } else {
                $error_message = $this->language->get('error_void_not_allowed');
            }
        } elseif ($code == 404) {
            $error_message = $this->language->get('error_payment_not_found');
        } elseif ($code == 422) {
            if ($type == 'refund') {
                $error_message = $this->language->get('error_invalid_refund');
            } else {
                $error_message = $this->language->get('error_invalid_data');
            }
        } elseif ($code == 502) {
            $error_message = $this->language->get('error_bad_gateway');
        } else {
            $error_message = $this->language->get('error_invalid_response');
        }

        return $error_message;
    }
    
    public function demo() {
        $json = array();
        
        $this->load->language('extension/payment/checkout_com');
        
        if (isset($this->request->get['email'])) {
            if ((utf8_strlen($this->request->get['email']) > 96) || !filter_var($this->request->get['email'], FILTER_VALIDATE_EMAIL)) {
                $json['message'] = $this->language->get('error_email');
            }
            
            if (!$json) {
                $mail = new Mail($this->config->get('config_mail_engine'));
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                $mail->setTo('partnerships@checkout.com');
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
                $mail->setSubject('Demo Request from OpenCart Store');
                $mail->setText('You have received a demo request from an OpenCart store - ' . $this->request->get['email']);
                $mail->send();
                
                $json['message'] = $this->language->get('text_demo');
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}