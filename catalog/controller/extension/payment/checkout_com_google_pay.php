<?php
class ControllerExtensionPaymentCheckoutComGooglePay extends Controller {
    public function index() {
        $data = $this->load->language('extension/payment/checkout_com');

        $this->load->model('extension/payment/checkout_com');

        $data['callback_url'] = $this->url->link('extension/payment/checkout_com/makePayment', '', true);
        $data['testmode'] = $this->config->get('payment_checkout_com_test');
        $data['public_key'] = $this->config->get('payment_checkout_com_public_key');

        if ($this->config->get('payment_checkout_com_test')) {
            $data['merchant_id'] = $this->config->get('payment_checkout_com_google_pay_merchant_id');
        } else {
            $data['merchant_id'] = $this->config->get('payment_checkout_com_google_pay_merchant_id');
        }

        $data['button_type'] = $this->config->get('payment_checkout_com_google_pay_button_type');
        $data['button_colour'] = $this->config->get('payment_checkout_com_google_pay_button_colour');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            $this->load->model('localisation/country');

            $data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $data['currency_code'] = $order_info['currency_code'];

            $country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);

            $data['country_code'] = $country_info['iso_code_2'];

            return $this->load->view('extension/payment/checkout_com_google_pay', $data);
        }
    }
}