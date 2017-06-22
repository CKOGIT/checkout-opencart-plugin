<?php
include ('includes/autoload.php');
class ControllerExtensionPaymentcheckoutapipayment extends Controller_Model
{
    public function index()
    {
        return parent::index();
    }

    public function successPage()
    { 
        if(empty($_REQUEST['cko-payment-token'])){
            return http_response_code(400);
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $scretKey = $this->config->get('checkoutapipayment_secret_key');
        $config['authorization'] = $scretKey  ;
        $config['paymentToken']  = $_REQUEST['cko-payment-token'];
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));
        $respondBody = $Api->verifyChargePaymentToken($config);
        $obj = $respondBody->getRawOutput();
        $respondCharge = $Api->chargeToObj($obj);
        $trackId = $respondCharge->getTrackId();

        if( $respondCharge->isValid()) {
            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                if($respondCharge->getResponseCode()==10100){

                    $Message = 'Your transaction has been flagged with transaction id : '.$respondCharge->getId();

                    if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $Message, true); // 1 for pending order
                    }

                    if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $Message, true); // 1 for pending order
                        $this->session->data['fail_transaction'] = false;
                    }
                }else {

                    $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();

                    if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                    }

                    if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_successful_order'), $Message, false);
                        $this->session->data['fail_transaction'] = false;
                    }
                }

                $json= $this->url->link('checkout/success', '', 'SSL');
                $success = $this->url->link('checkout/success', '', 'SSL');
                header("Location: ".$success);

            } else {

                $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();
                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Message, false);
                }

                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Message, false);
                    $this->session->data['fail_transaction'] = true;
                }

                $this->session->data['error'] = 'An error has occured while processing your order. Please check your card details or try with a different card';

                $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));

            }
        } else  {
            $json['error'] = $respondCharge->getExceptionState()->getErrorMessage()  ;
        }
    }

    public function failPage(){
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $Message = 'Transaction has failed or has been canceled';
        if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Message, false);
        }

        if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
            $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('checkoutapipayment_checkout_failed_order'), $Message, false);
            $this->session->data['fail_transaction'] = true;
        }

        $this->session->data['error'] = 'An error has occured while processing your order. Please check your card details or try with a different card';

        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));

    }

    public function webhook()
    {
        if(isset($_GET['chargeId'])) {
            $stringCharge = $this->_process();
        }else {
            $stringCharge = file_get_contents ( "php://input" );
        }

        if(empty($stringCharge)){
            return http_response_code(400);
        }

        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));

        $objectCharge = $Api->chargeToObj($stringCharge);

        if($objectCharge->isValid()) {

            $order_id = $objectCharge->getTrackId();
            $modelOrder = $this->load->model('checkout/order');
            $order_statuses = $this->getOrderStatuses();
            $status_mapped = array();

            foreach($order_statuses as $status){
                $status_mapped[$status['name']] = $status['order_status_id'];
            }

            if ( $objectCharge->getCaptured ()) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $status_mapped['Processing'],
                    "Order status changed by webhook. Captured chargeId : ".$objectCharge->getId(),
                    true
                );

            } elseif ( $objectCharge->getRefunded () ) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $status_mapped['Refunded'],
                    "Order status changed by webhook. Refund chargeId : ".$objectCharge->getId(),
                    true
                );

            }  elseif ( $objectCharge->getVoided () ) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $status_mapped['Voided'],
                    "Order status changed by webhook. Voided chargeId : ".$objectCharge->getId(),
                    true
                );
            }
        }
    }

    private function _process()
    {
        $config['chargeId']    =    $_GET['chargeId'];
        $config['authorization']    =    $this->config->get('checkoutapipayment_secret_key');
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('checkoutapipayment_test_mode')));
        $respondBody    =    $Api->getCharge($config);

        $json = $respondBody->getRawOutput();
        return $json;
    }

    private function getOrderStatuses($data = array()) {

        if ($data) {
            $sql = "SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'";

            $sql .= " ORDER BY name";

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }

                $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
            }

            $query = $this->db->query($sql);

            return $query->rows;
        } else {
            $order_status_data = $this->cache->get('order_status.' . (int)$this->config->get('config_language_id'));

            if (!$order_status_data) {
                $query = $this->db->query("SELECT order_status_id, name FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY name");

                $order_status_data = $query->rows;

                $this->cache->set('order_status.' . (int)$this->config->get('config_language_id'), $order_status_data);
            }

            return $order_status_data;
        }
    }
}