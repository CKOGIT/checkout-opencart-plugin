<?php
class ControllerExtensionPaymentCheckoutComApm extends Controller {
    public function index() {
        $data = $this->load->language('extension/payment/checkout_com');

        $this->load->model('extension/payment/checkout_com_apm');

        $data['payment_methods'] = $this->model_extension_payment_checkout_com_apm->getPaymentMethods();

        $data['testmode'] = $this->config->get('payment_checkout_com_test');
        $data['public_key'] = $this->config->get('payment_checkout_com_public_key');

        $data['text_testmode'] = $this->language->get('text_testmode');
        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('localisation/country');

        $countries = $this->model_localisation_country->getCountries();

        $data['countries'] = array();

        foreach ($countries as $country) {
            if ($country['iso_code_2'] == 'BE') {
                array_unshift($data['countries'] , $country);
            } else {
                $data['countries'][] = $country;
            }
        }

        $this->load->model('checkout/order');

        if (isset($this->session->data['order_id'])) {
            $order_id = $this->session->data['order_id'];
        } else {
            $order_id = 0;
        }

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($order_info) {
            return $this->load->view('extension/payment/checkout_com_apm', $data);
        }
    }
}