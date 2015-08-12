<?php
include ('includes/autoload.php');
class ControllerPaymentcheckoutapipayment extends Controller_Model
{
    protected function index()
    {
        parent::index();

        $this->render();
    }

    public function successPage()
    {

        $this->load->model('checkout/order');
        $trackId = $this->model_checkout_order->getOrder($_POST['cko-track-id']);
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $scretKey = $this->config->get('secret_key');
        $config['authorization'] = $scretKey  ;
        $config['paymentToken']  = $_POST['cko-payment-token'];
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
        $respondBody = $Api->verifyChargePaymentToken($config);
        $json = $respondBody->getRawOutput();
        $respondCharge = $Api->chargeToObj($json);

        $amount = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;

        $toValidate = array(
            'currency' => $this->currency->getCode(),
            'value' => $amount,
            'trackId' => $this->session->data['order_id'],
        );

        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
        $validateRequest = $Api::validateRequest($toValidate,$respondCharge);

        if( $respondCharge->isValid()) {

            if (preg_match('/^1[0-9]+$/', $respondCharge->getResponseCode())) {

                $Message = 'Your transaction has been  ' .strtolower($respondCharge->getStatus()) .' with transaction id : '.$respondCharge->getId();

                if(!$validateRequest['status']){
                    foreach($validateRequest['message'] as $errormessage){
                        $Message .= '. '.$errormessage . '. ';
                    }
                }

                $this->model_checkout_order->confirm($_POST['cko-track-id'], $this->config->get('checkout_successful_order'), $Message, true);
                $success = $this->data['continue'] = $this->url->link('checkout/success');

                header("Location: ".$success);

            } else {

                $Payment_Error = 'Transaction failed : '.$respondCharge->getErrorMessage(). ' with response code : '.$respondCharge->getResponseCode();

                if(!isset($this->session->data['fail_transaction']) || $this->session->data['fail_transaction'] == false) {
                    $this->model_checkout_order->confirm($_POST['cko-track-id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                if(isset($this->session->data['fail_transaction']) && $this->session->data['fail_transaction']) {
                    $this->model_checkout_order->update($_POST['cko-track-id'], $this->config->get('checkout_failed_order'), $Payment_Error, true);
                }
                $json['error'] = 'We are sorry, but you transaction could not be processed. Please verify your card information and try again.'  ;
                $this->session->data['fail_transaction'] = true;
            }

        } else  {
            $json['error'] = $respondCharge->getExceptionState()->getErrorMessage()  ;
        }
    }

    public function webhook()
    {
        if(isset($_GET['chargeId'])) {
            $stringCharge = $this->_process();
        }else {
            $stringCharge = file_get_contents ( "php://input" );
        }
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));

        $objectCharge = $Api->chargeToObj($stringCharge);

        $this->load->model('checkout/order');
        $order_id = $objectCharge->getTrackId();

        $order_info = $this->model_checkout_order->getOrder($order_id);

        $amount = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;

        $toValidate = array(
            'currency' => $this->currency->getCode(),
            'value' => $amount,
            'trackId' => $order_id,
        );

        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
        $validateRequest = $Api::validateRequest($toValidate,$objectCharge);

        if($objectCharge->isValid()) {

            $Message = 'Webhook received from Checkout.com. ' ;

            if(!$validateRequest['status']){
                foreach($validateRequest['message'] as $errormessage){
                    $Message .= $errormessage . '. ';
                }
            }

            if ( $objectCharge->getCaptured ()) {

                $Message .= ' Order has been captured';
                $this->model_checkout_order->update($order_id, $this->config->get('checkout_successful_order'), $Message, true);

            } elseif ( $objectCharge->getRefunded () ) {

                $Message .= ' Order has been refunded';
                $this->model_checkout_order->update($order_id, $this->config->get('checkout_successful_order'), $Message, true);


            } elseif(!$objectCharge->getAuthorised()) {

                $Message .= ' Order has been Authorised';
                $this->model_checkout_order->update($order_id, $this->config->get('checkout_successful_order'), $Message, true);

            }

        }

    }

    private function _process()
    {
        $config['chargeId']    =    $_GET['chargeId'];
        $config['authorization']    =    $this->config->get('secret_key');
        $Api = CheckoutApi_Api::getApi(array('mode'=> $this->config->get('test_mode')));
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
