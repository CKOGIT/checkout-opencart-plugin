<?php
class Controller_Methods_creditcardframes extends Controller_Methods_Abstract implements Controller_Interface
{

    public function getData()
    {
        $this->language->load('payment/checkoutapipayment');
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $config['debug'] = false;
        $config['email'] =  $order_info['email'];
        $config['name'] = $order_info['firstname']. ' '.$order_info['lastname'];
        $config['currency'] =  $this->currency->getCode();
        $config['widgetSelector'] =  '.widget-container';
        $paymentTokenArray = $this->generatePaymentToken();
        $localPayment = $this->config->get('localpayment_enable');
        $mode = $this->config->get('test_mode');
        $amount = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;

        if($mode == 'live'){
            $url = 'https://cdn.checkout.com/js/checkout.js';
        } else {
            $url = 'https://cdn.checkout.com/sandbox/js/checkout.js';
        }

        if($localPayment == 'yes'){
            $paymentToken = $this->generatePaymentToken();
            $result = $this->getLocalPaymentProvider($paymentToken['token']);

            $this->session->data['localPayment'] = $result;

            foreach ($result as $i=>$item) {
                $lpName = strtolower(preg_replace('/\s+/', '', $item['name']));
                $this->session->data['lpName'] = $lpName;
                
                if($lpName == 'ideal'){
                    $localPaymentInformation = $this->getLocalPaymentInformation($item['id']);
                    $this->session->data['idealPaymentInfo'] = $localPaymentInformation;
                }  
            } 
        } 

        if($this->config->get('save_card') == 'yes'){

            if($this->customer->getId()){
                $this->session->data['customer_login'] = 'yes';

                $cardList = $this->getCustomerCardList($this->customer->getId());
            
                $this->session->data['cardLists']= '';
                
                if(!empty($cardList)){
                    foreach ($cardList as $key) {
                        $test[] = $key;
                    }

                    $this->session->data['cardLists'] = $test;
                }
            } else {
                $this->session->data['customer_login'] = 'no';
            }
        }

        $billingAddressConfig = array(
            'addressLine1'       =>  $order_info['payment_address_1'],
            'addressLine2'       =>  $order_info['payment_address_2'],
            'postcode'           =>  $order_info['payment_postcode'],
            'country'            =>  $order_info['payment_iso_code_2'],
            'city'               =>  $order_info['payment_city'],
            'phone'              =>  array('number' => $order_info['telephone']),
        );

        $toReturn = array(
            'text_card_details' =>  $this->language->get('text_card_details'),
            'text_wait'         =>  $this->language->get('text_wait'),
            'entry_public_key'  =>  $this->config->get('public_key'),
            'order_email'       =>  $order_info['email'],
            'order_currency'    =>  $this->currency->getCode(),
            'amount'            =>  $amount,
            'title'             =>  $this->config->get('config_name'),
            'publicKey'         =>  $this->config->get('public_key'),
            'url'               =>  'https://cdn.checkout.com/js/frames.js',
            'email'             =>  $order_info['email'],
            'name'              =>  $order_info['firstname']. ' '.$order_info['lastname'],
            'paymentToken'      =>  $paymentTokenArray['token'],
            'message'           =>  $paymentTokenArray['message'],
            'success'           =>  $paymentTokenArray['success'],
            'eventId'           =>  $paymentTokenArray['eventId'],
            'textWait'          =>  $this->language->get('text_wait'),
            'logoUrl'           =>  $this->config->get('logo_url'),
            'themeColor'        =>  $this->config->get('theme_color'),
            'buttonColor'       =>  $this->config->get('button_color'),
            'iconColor'         =>  $this->config->get('icon_color'),
            'currencyFormat'    =>  $this->config->get('currency_format'),
            'button_confirm'    =>  $this->language->get('button_confirm'),
            'trackId'           =>  $order_info['order_id'],
            'addressLine1'      =>  $order_info['payment_address_1'],
            'addressLine2'      =>  $order_info['payment_address_2'],
            'postcode'          =>  $order_info['payment_postcode'],
            'country'           =>  $order_info['payment_iso_code_2'],
            'city'              =>  $order_info['payment_city'],
            'phone'             =>  $order_info['telephone'],
            'alternativePayment'=>  $this->config->get('localpayment_enable'),
            'save_card'         =>  $this->config->get('save_card')
        );

        foreach ($toReturn as $key=>$val) {

            $this->data[$key] = $val;
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/checkoutapi/creditcardframes.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/checkoutapi/creditcardframes.tpl';
        } else {
            $this->template = 'default/template/payment/checkoutapi/creditcardframes.tpl';
        }
 
        $toReturn['tpl'] =   $this->render();
        return $toReturn;
    }

    protected function _createCharge($order_info)
    { 
        if($this->request->post['cko-payment'] == 'alternative_payment' && $this->config->get('localpayment_enable') == 'yes'){
            return $this->createLpCharge($this->request->post, $order_info);
        }

        $config = array();
        $scretKey = $this->config->get('secret_key');
        $config['authorization'] = $scretKey  ;
        $config['timeout'] =  $this->config->get('gateway_timeout');

        $config = $this->getConfigData();
        if($this->request->post['cko-payment'] == 'new_card' && !empty($this->request->post['cko-card-token'])){
            $config['postedParam']['cardToken'] = $this->request->post['cko-card-token'];

        } elseif($this->request->post['cko-payment'] == 'saved_card' && !empty($this->request->post['entity_id'])){

            $getCardId = $this->getCardId($this->request->post['entity_id']);
            $config['postedParam']['cardId'] = $getCardId[0]['card_id'];
        }

        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));

        return $Api->createCharge($config);
    }

    public function getConfigData(){
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('test_mode');
        $config['timeout'] =  $this->config->get('gateway_timeout');

        if($this->config->get('payment_action') =='capture') {
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
            'addressLine1'       =>  $order_info['payment_address_1'],
            'addressLine2'       =>  $order_info['payment_address_2'],
            'postcode'           =>  $order_info['payment_postcode'],
            'country'            =>  $order_info['payment_iso_code_2'],
            'city'               =>  $order_info['payment_city'],
            'phone'              =>  array('number' => $order_info['telephone']),

        );

        $shippingAddressConfig = array(
            'addressLine1'       =>  $order_info['shipping_address_1'],
            'addressLine2'       =>  $order_info['shipping_address_2'],
            'postcode'           =>  $order_info['shipping_postcode'],
            'country'            =>  $order_info['shipping_iso_code_2'],
            'city'               =>  $order_info['shipping_city'],
            'phone'              =>  array('number' => $order_info['telephone']),

        );

        $config['postedParam'] = array_merge($config['postedParam'],array (
            'customerName'       =>  $order_info['payment_firstname']. ' ' .$order_info['payment_lastname'],
            'email'              =>  $order_info['email'],
            'value'              =>  $amountCents,
            'trackId'            =>  $orderId,
            'currency'           =>  $this->currency->getCode(),
            'chargeMode'         =>  $this->config->get('is_3d'),
            'description'        =>  "Order number::$orderId",
            'shippingDetails'    =>  $shippingAddressConfig,
            'products'           =>  $products,
            'billingDetails'     =>  $billingAddressConfig,
            'metadata'           => array(
                'server'            => $this->config->get('config_url'),
                'quoteId'           => $orderId,
                'opencart_version'  => VERSION,
                'plugin_version'    => PLUGIN_VERSION,
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'FramesJs',
                'time'              => date('Y-m-d H:i:s')
            ),

        ));

        return $config;
    }

    public function generatePaymentToken()
    {
        $config = array();
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $productsLoad= $this->cart->getProducts();
        $scretKey = $this->config->get('secret_key');
        $orderId = $this->session->data['order_id'];
        $amountCents = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;
        $config['authorization'] = $scretKey  ;
        $config['mode'] = $this->config->get('test_mode');
        $config['timeout'] =  $this->config->get('gateway_timeout');

        if($this->config->get('payment_action') =='capture') {
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
            'addressLine1'       =>  $order_info['payment_address_1'],
            'addressLine2'       =>  $order_info['payment_address_2'],
            'postcode'           =>  $order_info['payment_postcode'],
            'country'            =>  $order_info['payment_iso_code_2'],
            'city'               =>  $order_info['payment_city'],
            'phone'              =>  array('number' => $order_info['telephone']),

        );

        $shippingAddressConfig = array(
            'recipientName'      =>  $order_info['payment_firstname']. ' ' .$order_info['payment_lastname'],
            'addressLine1'       =>  $order_info['shipping_address_1'],
            'addressLine2'       =>  $order_info['shipping_address_2'],
            'postcode'           =>  $order_info['shipping_postcode'],
            'country'            =>  $order_info['shipping_iso_code_2'],
            'city'               =>  $order_info['shipping_city'],
            'phone'              =>  array('number' => $order_info['telephone']),
            'state'              =>  $order_info['shipping_zone'],

        );

        $config['postedParam'] = array_merge($config['postedParam'],array (
            'customerName'       =>  $order_info['payment_firstname']. ' ' .$order_info['payment_lastname'],
            'email'              =>  $order_info['email'],
            'value'              =>  $amountCents,
            'trackId'            =>  $orderId,
            'currency'           =>  $this->currency->getCode(),
            'description'        =>  "Order number::$orderId",
            'shippingDetails'    =>  $shippingAddressConfig,
            'products'           =>  $products,
            'billingDetails'     =>  $billingAddressConfig,
            'metadata'           => array(
                'server'            => $this->config->get('config_url'),
                'quoteId'           => $orderId,
                'opencart_version'  => VERSION,
                'plugin_version'    => PLUGIN_VERSION,
                'lib_version'       => CheckoutApi_Client_Constant::LIB_VERSION,
                'integration_type'  => 'FramesJs',
                'time'              => date('Y-m-d H:i:s')
            ),

        ));

        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('test_mode')));
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
            $paymentTokenArray['customerEmail']    = $config['postedParam']['email'];

        }else {

            $paymentTokenArray['message']    =    $paymentTokenCharge->getExceptionState()->getErrorMessage();
            $paymentTokenArray['success']    =    false;
            $paymentTokenArray['eventId']    =    $paymentTokenCharge->getEventId();
        }

        return $paymentTokenArray;
    }

    public function getLocalPaymentProvider($paymentToken){
        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('test_mode')));
        $paymentToken = array('paymentToken' => $paymentToken, 'authorization' => $this->config->get('public_key'));
        $result = $Api->getLocalPaymentProviderByPayTok($paymentToken);
        $data = $result->getData();

        foreach ((array)$data as &$value) { 
            return $value;
        }
    }

    /**
    *
    * Get Bank info for Ideal
    *
    **/
    public function getLocalPaymentInformation($lpId){
        $secretKey = $this->config->get('secret_key');
        $mode = $this->config->get('test_mode');
        $url = "https://sandbox.checkout.com/api2/v2/lookups/localpayments/{$lpId}/tags/issuerid";
        
        if($mode == 'live'){
            $url = "https://api2.checkout.com/v2/lookups/localpayments/{$lpId}/tags/issuerid";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_SSL_VERIFYPEER=>  false,
            CURLOPT_HTTPHEADER => array(
              "authorization: ".$secretKey,
              "cache-control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
          WC_Checkout_Non_Pci::log("cURL Error #:" . $err);
        } else {

            $test = json_decode($response);

            foreach ((array)$test as &$value) { 
                foreach ($value as $i=>$item){
                    foreach ($item as  $is=>$items) {
                       
                        return $item->values;
                       
                    }
                }
            }
        }
    }

    public function createLpCharge($data, $order_info){ 
        //$customer = new Customer((int) $cart->id_customer);
        $paymentToken = $this->generatePaymentToken();
        $secretKey = $this->config->get('secret_key');
        $localpayment = $this->getLocalPaymentProvider($paymentToken['token']);

        foreach ($localpayment as $i=>$item) {
           $lpName = strtolower(preg_replace('/\s+/', '', $item['name']));
           if($lpName == strtolower($_POST['cko-lp-lpName'])){
                $lppId = $item['id'];
           }
        }

        $config = array();
        $config['authorization']    = $secretKey;
        $config['postedParam']['email'] = $paymentToken['customerEmail'];
        $config['postedParam']['paymentToken'] = $paymentToken['token'];

        if($data['cko-lp-lpName'] == 'ideal'){
            $config['postedParam']['localPayment'] = array(
                                        "lppId" => $lppId,
                                        "userData" => array(
                                                        "issuerId" => $data['cko-lp-issuerId']
                                        )
            );
        } elseif($data['cko-lp-lpName'] == 'boleto'){
            $config['postedParam']['localPayment'] = array(
                                        "lppId" => $lppId,
                                        "userData" => array(
                                                        "birthDate" => $data['boletoDate'],
                                                        "cpf" => $data['cpf'],
                                                        "customerName" => $data['custName']
                                        )
            );
        } elseif($data['cko-lp-lpName'] == 'qiwi'){

            $config['postedParam']['localPayment'] = array(
                                        "lppId" => $lppId,
                                        "userData" => array(
                                                        "walletId" => $data['walletId'],
                                        )
            );
        } else {
            $config['postedParam']['localPayment'] = array(
                                        "lppId" => $lppId,
            );
        }


        $Api = CheckoutApi_Api::getApi(array('mode' => $this->config->get('test_mode')));
        $result = $Api->createLocalPaymentCharge($config);

        return $result; 
    }

    public function getCustomerCardList($customerId) {
        $sql = "SELECT * FROM ".DB_PREFIX."checkout_customer_cards WHERE customer_id = '".$customerId."' AND card_enabled = '1'";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getCardId($entityId){
        
        $sql = 'SELECT card_id FROM '.DB_PREFIX."checkout_customer_cards WHERE entity_id = '".$entityId."'";

        $query = $this->db->query($sql);

        return $query->rows;
    }


}