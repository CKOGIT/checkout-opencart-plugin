<?php
class Controller_Methods_creditcardhosted extends Controller_Methods_Abstract implements Controller_Interface
{
    public function getData()
    {
        $this->language->load('extension/payment/checkoutapipayment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $config['debug'] = false;
        $config['email'] = $order_info['email'];
        $config['name'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
        $config['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['currency'] = $order_info['currency_code'];
        $config['widgetSelector'] = '.widget-container';
        $mode = $this->config->get('checkoutapipayment_test_mode');
        $paymentMode = $this->config->get('checkoutapipayment_payment_mode');
        $paymentTokenArray = $this->generatePaymentToken();
        $cancelUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutapipayment/failPage';
        $redirectUrl = $this->config->get('config_url').'index.php?route=extension/payment/checkoutapipayment/send';

        if ($mode == 'live') {
            $url = 'https://secure1.checkout.com/payment/';
        } else {
            $url = 'https://secure.checkout.com/sandbox/payment/';
        }

        $data = array(
            'order_currency' => $order_info['currency_code'],
            'amount'         => $config['amount'],
            'publicKey'      => $this->config->get('checkoutapipayment_public_key'),
            'buttonLabel'    => $this->config->get('checkoutapipayment_button_label'),
            'title'          => $this->config->get('checkoutapipayment_title'),
            'paymentMode'    => $paymentMode,
            'url'            => $url,
            'email'          => $order_info['email'],
            'name'           => $order_info['firstname'] . ' ' . $order_info['lastname'],
            'paymentToken'   => $paymentTokenArray['token'],
            'trackId'        => $order_info['order_id'],
            'cancelUrl'      => $cancelUrl,
            'redirectUrl'    => $redirectUrl,
            'logoUrl'        => $this->config->get('checkoutapipayment_logo_url'),
            'themeColor'     => $this->config->get('checkoutapipayment_theme_color'),
            'buttonColor'    => $this->config->get('checkoutapipayment_button_color'),
            'iconColor'      => $this->config->get('checkoutapipayment_icon_color'),
            'currencyFormat' => $this->config->get('checkoutapipayment_currency_format'),
            'paymentMode'    => $paymentMode,
            'button_confirm' => $this->language->get('button_confirm'),
        );


        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . 'extension/payment/checkoutapi/creditcardhosted.tpl')) {
            $tpl = $this->config->get('config_template') . 'extension/payment/checkoutapi/creditcardhosted.tpl';

        } else {  
            $tpl = 'extension/payment/checkoutapi/creditcardhosted.tpl';
        }

        $data['tpl'] = $this->load->view($tpl, $data);


        return $data;
    }

    protected function _createCharge($order_info)
    { 
        $config = parent::_createCharge($order_info);

        $config['postedParam'] = array_merge($config['postedParam'], array(
                'cardToken' => $this->request->post['cko-card-token']
            )
        );

        return $this->_getCharge($config);
    }

    public function generatePaymentToken()
    {
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) * 100;
        $config['authorization'] = $scretKey;
        $config['mode'] = $this->config->get('checkoutapipayment_test_mode');
        $config['timeout'] = $this->config->get('checkoutapipayment_gateway_timeout');

        if ($this->config->get('checkoutapipayment_payment_action') == 'capture') {
            $config = array_merge($config, $this->_captureConfig(), $config);

        } else {
            $config = array_merge($config, $this->_authorizeConfig(), $config);
        }

        $is3D = $this->config->get('checkoutapipayment_3D_secure');
        $chargeMode = 1;

        if($is3D == 'yes'){
            $chargeMode = 2;
        }

        $products = array();
        foreach ($productsLoad as $item ) {

            $products[] = array (
                'name'       =>     $item['name'],
                'sku'        =>     $item['product_id'],
                'price'      =>     $item['price'],
                'quantity'   =>     $item['quantity']
            );
        }

        $billingAddressConfig = array(
            'addressLine1'  =>  $order_info['payment_address_1'],
            'addressLine2'  =>  $order_info['payment_address_2'],
            'postcode'      =>  $order_info['payment_postcode'],
            'country'       =>  $order_info['payment_iso_code_2'],
            'city'          =>  $order_info['payment_city'],
            'phone'         =>  array('number' => $order_info['telephone']),

        );


        if ($order_info['shipping_method'] != '' ){

            $shippingAddressConfig = array(
                'addressLine1'   =>  $order_info['shipping_address_1'],
                'addressLine2'   =>  $order_info['shipping_address_2'],
                'postcode'       =>  $order_info['shipping_postcode'],
                'country'        =>  $order_info['shipping_iso_code_2'],
                'city'           =>  $order_info['shipping_city'],
                'phone'          =>  array('number' => $order_info['telephone']),

            );

            $config['postedParam'] = array_merge($config['postedParam'],array (
                'shippingDetails' => $shippingAddressConfig
            ));
        }

        $config['postedParam'] = array_merge($config['postedParam'], array(
            'email'           => $order_info['email'],
            'value'           => $amountCents,
            'currency'        => $order_info['currency_code'],
            'chargeMode'      =>  $chargeMode,
            'trackId'         =>  $orderId,
            'description'     =>  "Order number::$orderId",
            'products'        =>  $products,
            'billingDetails'  =>  $billingAddressConfig
        ));

        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_test_mode')));

        $paymentTokenCharge = $Api->getPaymentToken($config);

        $paymentTokenArray = array(
            'message' => '',
            'success' => '',
            'eventId' => '',
            'token' => '',
        );

        if ($paymentTokenCharge->isValid()) {
            $paymentTokenArray['token'] = $paymentTokenCharge->getId();
            $paymentTokenArray['success'] = true;

        } else {

            $paymentTokenArray['message'] = $paymentTokenCharge->getExceptionState()->getErrorMessage();
            $paymentTokenArray['success'] = false;
            $paymentTokenArray['eventId'] = $paymentTokenCharge->getEventId();
        }

        return $paymentTokenArray;
    }
}