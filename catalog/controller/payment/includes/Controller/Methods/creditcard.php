<?php
class Controller_Methods_creditcard extends Controller_Methods_Abstract implements Controller_Interface
{

    public function getData()
    {
        $this->language->load('payment/checkoutapipayment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $config['debug'] = false;
        $config['email'] =  $order_info['email'];
        $config['name'] = $order_info['firstname']. ' '.$order_info['lastname'];
        $config['amount'] =  $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)*100;
        $config['currency'] =  $this->currency->getCode();
        $config['widgetSelector'] =  '.widget-container';
        $mode = $this->config->get('checkoutapipayment_test_mode');
        $localPayment = $this->config->get('checkoutapipayment_localpayment_enable');
        $paymentTokenArray    =    $this->generatePaymentToken();

        if($mode == 'live'){
            $url = 'https://www.checkout.com/cdn/js/checkout.js';
        } else {
            $url = 'https://sandbox.checkout.com/js/v1/checkout.js';
        }
        if($localPayment == 'yes'){
            $paymentMode = 'mixed';
        } else {
            $paymentMode = 'card';
        }

        $data = array(
            'text_card_details' =>  $this->language->get('text_card_details'),
            'text_wait'         =>  $this->language->get('text_wait'),
            'entry_public_key'  =>  $this->config->get('checkoutapipayment_public_key'),
            'order_email'       =>  $order_info['email'],
            'order_currency'    =>  $this->currency->getCode(),
            'amount'            =>  $config['amount'],
            'publicKey'         =>  $this->config->get('checkoutapipayment_public_key'),
            'paymentMode'       =>  $paymentMode,
            'url'               =>  $url,
            'email'             =>  $order_info['email'],
            'name'              =>  $order_info['firstname']. ' '.$order_info['lastname'],
            'paymentToken'      =>  $paymentTokenArray['token'],
            'message'           =>  $paymentTokenArray['message'],
            'success'           =>  $paymentTokenArray['success'],
            'eventId'           =>  $paymentTokenArray['eventId'],
            'textWait'          =>  $this->language->get('text_wait'),
            'trackId'           =>  $order_info['order_id'],
            'addressLine1'      =>  $order_info['payment_address_1'],
            'addressLine2'      =>  $order_info['payment_address_2'],
            'postcode'          =>  $order_info['payment_postcode'],
            'country'           =>  $order_info['payment_iso_code_2'],
            'city'              =>  $order_info['payment_city'],
            'phone'             =>  $order_info['telephone'],
            'logoUrl'           =>  $this->config->get('checkoutapipayment_logo_url'),
            'themeColor'        =>  $this->config->get('checkoutapipayment_theme_color'),
            'buttonColor'       =>  $this->config->get('checkoutapipayment_button_color'),
            'iconColor'         =>  $this->config->get('checkoutapipayment_icon_color'),
            'currencyFormat'    =>  $this->config->get('checkoutapipayment_currency_format'),
            'paymentMode'       =>  $paymentMode,
        );

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/checkoutapi/creditcard.tpl')) {
            $tpl = $this->config->get('config_template') . '/template/payment/checkoutapi/creditcard.tpl';

        } else {
            $tpl= 'default/template/payment/checkoutapi/creditcardpci.tpl';
        }

        $data['tpl'] =   $this->load->view($tpl, $data);

        return $data;
    }

    protected function _createCharge($order_info)
    {
        $config = array();
        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $config['authorization'] = $scretKey  ;
        $config['timeout'] =  $this->config->get('checkoutapipayment_gateway_timeout');
        $config['paymentToken']  = $this->request->post['cko_cc_paymenToken'];
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));

        return $Api->verifyChargePaymentToken($config);
    }

    public function generatePaymentToken()
    {
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents =$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)*100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('checkoutapipayment_test_mode');
        $config['timeout'] =  $this->config->get('checkoutapipayment_gateway_timeout');

        if($this->config->get('checkoutapipayment_payment_action') =='authorize_capture') {
            $config = array_merge($config, $this->_captureConfig());

        }else {
            $config = array_merge($config,$this->_authorizeConfig());
        }

        $products = array();
        foreach ($productsLoad as $item ) {

            $products[] = array (
                'name'       =>     $item['name'],
                'sku'        =>     $item['key'],
                'price'      =>     $this->currency->format($item['price'], $this->currency->getCode(), false, false),
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

        $shippingAddressConfig = array(
            'addressLine1'   =>  $order_info['shipping_address_1'],
            'addressLine2'   =>  $order_info['shipping_address_2'],
            'postcode'       =>  $order_info['shipping_postcode'],
            'country'        =>  $order_info['shipping_iso_code_2'],
            'city'           =>  $order_info['shipping_city'],
            'phone'          =>  array('number' => $order_info['telephone']),
            'recipientName'	 =>  $order_info['firstname']. ' '. $order_info['lastname']

        );

        $config['postedParam'] = array_merge($config['postedParam'],array (
            'email'           =>  $order_info['email'],
            'value'           =>  $amountCents,
            'trackId'         =>  $orderId,
            'currency'        =>  $this->currency->getCode(),
            'description'     =>  "Order number::$orderId",
            'shippingDetails' =>  $shippingAddressConfig,
            'products'        =>  $products,
            'billingDetails'  =>  $billingAddressConfig
        ));

        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('checkoutapipayment_test_mode')));

        $paymentTokenCharge = $Api->getPaymentToken($config);

        $paymentTokenArray    =   array(
            'message'   =>    '',
            'success'   =>    '',
            'eventId'   =>    '',
            'token'     =>    '',
        );

        if($paymentTokenCharge->isValid()){
            $paymentTokenArray['token'] = $paymentTokenCharge->getId();
            $paymentTokenArray['success'] = true;

        }else {

            $paymentTokenArray['message']    =    $paymentTokenCharge->getExceptionState()->getErrorMessage();
            $paymentTokenArray['success']    =    false;
            $paymentTokenArray['eventId']    =    $paymentTokenCharge->getEventId();
        }

        return $paymentTokenArray;
    }
}