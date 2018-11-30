<?php
include ('includes/autoload.php');

class ControllerExtensionPaymentcheckoutcom extends Controller_Model
{
    public function index() {
        return parent::index();
    }

    public function successPage() {
        if(empty($_REQUEST['cko-session-id'])) {
            return http_response_code(400);
        }

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $secretKey = $this->config->get('payment_checkoutcom_secret_key');
        $ckoSessionId  = $_REQUEST['cko-session-id'];
        $endPointMode = $this->config->get('payment_checkoutcom_test_mode');
        $verifyPaymentUrl = "https://api.sandbox.checkout.com/payments/".$ckoSessionId;

        if($endPointMode == 'live'){
            $verifyPaymentUrl = "https://api.checkout.com/payments/".$ckoSessionId;
        }

        // Verify session id to get full payment response.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$verifyPaymentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json;charset=UTF-8',
            'Authorization:	'.$secretKey));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output);
        $responseCharge = (array) $response;

        // Verify if risk settings was triggered
        if($responseCharge['risk']->flagged){

            // Add flagged message to order
            $message = 'Your payment has been flagged. Payment Id : ' . $responseCharge['id'];

        } elseif ($responseCharge['status'] == 'Authorized') {
            // Process normal payment flow
            $message = 'Your payment has been successfully authorized with Payment Id : ' . $responseCharge['id'];
        }

        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $message, false);

        $successUrl = $this->url->link('checkout/success', '', 'SSL');
        //Redirect to success page
        header("Location: ".$successUrl);

    }

    public function failPage() { 
        if(empty($_REQUEST['cko-session-id'])){
            return http_response_code(400);
        }
        
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $secretKey = $this->config->get('payment_checkoutcom_secret_key');
        $ckoSessionId  = $_REQUEST['cko-session-id'];
        $endPointMode = $this->config->get('payment_checkoutcom_test_mode');
        $verifyPaymentUrl = "https://api.sandbox.checkout.com/payments/".$ckoSessionId;

        if($endPointMode == 'live'){
            $verifyPaymentUrl = "https://api.checkout.com/payments/".$ckoSessionId;
        }

        // Verify session id to get full payment response.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$verifyPaymentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type:application/json;charset=UTF-8',
            'Authorization:	'.$secretKey));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output);
        $responseCharge = (array) $response;

        $errorMessage = 'Payment failed. Payment Id : '.$responseCharge['id'] .' - OrderId : '. $this->session->data['order_id']. ' - Reason : ';
        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 10, $errorMessage, true);
        $this->log->write($errorMessage);
        $this->session->data['fail_transaction'] = true;

        $message = 'An error has occurred while processing your order. Please check your card details or try with a different card'  ;

        $json['error'] = $message;
        $this->session->data['error'] = $message;

        $this->response->redirect($this->url->link('checkout/checkout', '', 'SSL'));

    }

    public function webhook() {
        $stringCharge = file_get_contents ( "php://input" );
        $data = json_decode($stringCharge);

        if(!$data){
            return false;
        }

        $eventType = $data->type;
        $order_id = $data->data->reference;
        $paymentId = $data->data->id;
        $paymentAmount = $data->data->amount;
        $createdOn = $data->created_on;

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $orderAmountCents = ($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false))*100;


        if($data) {
            $Message = 'Broadcast Webhook received from Checkout.com. '.date("h:i:sa").'</br>' ;
            if($eventType == 'payment_approved'){

                $Message .= 'Payment has been authorised. PaymentId : '.$paymentId;
                $this->model_checkout_order->addOrderHistory($order_id, 1, $Message, true);

            } elseif ($eventType == 'payment_capture_pending') {

                $Message .= 'Payment pending capture. PaymentId : '.$paymentId;
                $this->model_checkout_order->addOrderHistory($order_id, 1, $Message, true);

            } elseif ($eventType == 'payment_captured') {

                // Verify if captured webhook amount is equal to order amount
                if($paymentAmount == $orderAmountCents){
                    $Message .= 'Payment has been captured. PaymentId : '.$paymentId;
                } elseif ($paymentAmount < $orderAmountCents) {
                    $Message .= 'Payment has been partially captured. PaymentId : '.$paymentId;
                } else {
                    $Message .= 'Payment captured amount is more than order amount. PaymentId : '.$paymentId;
                }
                
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_checkoutcom_order_status_id') , $Message, true);

            } elseif ($eventType == 'payment_refunded') {

                // Verify if refund webhook amount is equal to order amount
                if($paymentAmount == $orderAmountCents){
                    $Message .= 'Payment has been fully refunded. PaymentId : '.$paymentId;
                    $this->model_checkout_order->addOrderHistory($order_id, 11, $Message, true);
                } elseif ($paymentAmount < $orderAmountCents) {
                    $Message .= 'Payment has been partially refunded. PaymentId : '.$paymentId;
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_checkoutcom_order_status_id'), $Message, true);
                }
                
                

            } elseif($eventType == 'payment_voided' || $eventType == 'payment_canceled') {

                $Message .= 'Payment has been voided/cancelled. PaymentId : '.$paymentId;
                $this->model_checkout_order->addOrderHistory($order_id, 16, $Message, true);

            }  elseif($eventType == 'payment_declined' ) {

                $Message .= 'Payment failed. PaymentId : '.$paymentId;
                $this->model_checkout_order->addOrderHistory($order_id, 10, $Message, true);

            } elseif($eventType == 'payment_expired' ) {

                $Message .= 'Payment failed. PaymentId : '.$paymentId;
                $this->model_checkout_order->addOrderHistory($order_id, 14, $Message, true);

            }
        }

        return true;
    }
}