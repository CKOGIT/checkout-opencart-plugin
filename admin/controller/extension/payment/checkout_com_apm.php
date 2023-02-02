<?php
class ControllerExtensionPaymentCheckoutComApm extends Controller {
    public function index() {
        $this->response->redirect($this->url->link('extension/payment/checkout_com', 'user_token=' . $this->session->data['user_token'], true));
    }

    public function order() {
        return $this->load->controller('extension/payment/checkout_com/order');
    }
}