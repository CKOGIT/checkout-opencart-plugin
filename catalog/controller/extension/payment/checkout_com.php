<?php
require_once(DIR_SYSTEM . 'library/vendor/checkout_com/checkout.php');

use Checkout\CheckoutApi;
use Checkout\Library\CheckoutConfiguration;
use Checkout\Library\Exceptions\CheckoutException;
use Checkout\Library\Exceptions\CheckoutHttpException;
use Checkout\Library\Exceptions\CheckoutModelException;
use Checkout\Models\Address;
use Checkout\Models\Event;
use Checkout\Models\Phone;
use Checkout\Models\Payments\AlipaySource;
use Checkout\Models\Payments\BancontactSource;
use Checkout\Models\Payments\BillingDescriptor;
use Checkout\Models\Payments\Customer;
use Checkout\Models\Payments\IdSource;
use Checkout\Models\Payments\KnetSource;
use Checkout\Models\Payments\Metadata;
use Checkout\Models\Payments\Payment;
use Checkout\Models\Payments\PaypalSource;
use Checkout\Models\Payments\QpaySource;
use Checkout\Models\Payments\Risk;
use Checkout\Models\Payments\Shipping;
use Checkout\Models\Payments\ThreeDs;
use Checkout\Models\Payments\TokenSource;
use Checkout\Models\Tokens\ApplePay;
use Checkout\Models\Tokens\ApplePayHeader;
use Checkout\Models\Tokens\Card;
use Checkout\Models\Tokens\GooglePay;

class ControllerExtensionPaymentCheckoutCom extends Controller {
    private $mada = false;

    public function index() {
        $data = $this->load->language('extension/payment/checkout_com');

        $this->load->model('extension/payment/checkout_com');

        $data['action'] = $this->url->link('extension/payment/checkout_com/callback', '', true);
        $data['testmode'] = $this->config->get('payment_checkout_com_test');
        $data['public_key'] = $this->config->get('payment_checkout_com_public_key');
        $data['save_cards'] = $this->config->get('payment_checkout_com_card');
        $data['require_cvv'] = $this->config->get('payment_checkout_com_require_cvv');
        $data['is_logged'] = $this->customer->isLogged();
        $data['is_mada_bin_check_enabled'] = $this->config->get('payment_checkout_com_mada_bin_check_status');

        if ($this->customer->isLogged()) {
            $data['saved_cards'] = $this->model_extension_payment_checkout_com->getCards($this->customer->getId());
        } else {
            $data['saved_cards'] = array();
        }

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
            $this->model_extension_payment_checkout_com->updatePaymentMethodName($order_info['order_id']);

            return $this->load->view('extension/payment/checkout_com', $data);
        }
    }

    public function makePayment() {
        $json = array();

        $this->load->language('extension/payment/checkout_com');

        $this->load->model('extension/payment/checkout_com');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && (isset($this->request->post['cko-card-token']) || isset($this->request->post['saved_card']) || isset($this->request->post['apple_pay_token']) || (isset($this->request->post['google_pay_token'])))) {
            $this->load->model('checkout/order');

            if (isset($this->session->data['order_id'])) {
                $order_id = $this->session->data['order_id'];
            } else {
                $order_id = 0;
            }

            if ($this->config->get('payment_checkout_com_mada_bin_check_status') && isset($this->request->get['mada']) && $this->request->get['mada']) {
                $this->mada = true;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                if ($this->config->get('payment_checkout_com_test')) {
                    $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
                } else {
                    $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
                }
                
                if (isset($this->request->post['cko-card-token'])) {
                    $method = new TokenSource($this->request->post['cko-card-token']);
                } elseif (isset($this->request->post['saved_card'])) {
                    $card_info = $this->model_extension_payment_checkout_com->getCard($this->request->post['saved_card']);

                    $method = new IdSource($card_info['source_id']);

                    if ($this->config->get('payment_checkout_com_require_cvv')) {
                        if (isset($this->request->post['cvv']) && $this->request->post['cvv']) {
                            $method->cvv = $this->request->post['cvv'];
                        } else {
                            $json['error'] = $this->language->get('error_cvv');
                        }
                    }
                } elseif (isset($this->request->post['google_pay_token'])) {
                    if ($this->config->get('payment_checkout_com_test')) {
                        $checkout_token = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
                    } else {
                        $checkout_token = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
                    }
                    
                    $checkout_token->configuration()->setPublicKey($this->config->get('payment_checkout_com_public_key'));

                    $token_data = $this->request->post['google_pay_token'];

                    $protocolVersion = $token_data['protocolVersion'];
                    $signature = $token_data['signature'];
                    $signedMessage = html_entity_decode($token_data['signedMessage']);

                    $googlepay = new GooglePay($protocolVersion, $signature, $signedMessage);

                    try {
                        $token_details = $checkout_token->tokens()->request($googlepay);

                        $method = new TokenSource($token_details->getValue('token'));
                    } catch (CheckoutHttpException $ex) {
                        $json['error'] = $this->getErrorMessage($ex->getCode());
                    } catch (Exception $ex) {
                        $json['error'] = $ex->getMessage();
                    }
                } elseif (isset($this->request->post['apple_pay_token'])) {
                    if ($this->config->get('payment_checkout_com_test')) {
                        $checkout_token = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
                    } else {
                        $checkout_token = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
                    }
                    
                    $checkout_token->configuration()->setPublicKey($this->config->get('payment_checkout_com_public_key'));

                    $data = $this->request->post['apple_pay_token']['data'];
                    $signature = $this->request->post['apple_pay_token']['signature'];
                    $version = $this->request->post['apple_pay_token']['version'];
                    $header_info = $this->request->post['apple_pay_token']['header'];

                    $header = new ApplePayHeader($header_info['transactionId'], $header_info['publicKeyHash'], $header_info['ephemeralPublicKey']);
                    $applepay = new ApplePay($version, $signature, $data, $header);
                    
                    try {
                        $token_details = $checkout_token->tokens()->request($applepay);
                        $method = new TokenSource($token_details->getValue('token'));

                        if ($token_details->getValue('bin') == '506968') {
                            $this->mada = true;
                        }
                    } catch (CheckoutHttpException $ex) {
                        $json['error'] = $this->getErrorMessage($ex->getCode());
                    } catch (Exception $ex) {
                        $json['error'] = $ex->getMessage();
                    }
                }

                if (!$json) {
                    $payment = $this->generatePaymentDetails($method, $order_info);

                    if (isset($this->request->post['save_card']) && $this->request->post['save_card']) {
                        $payment->success_url = $this->url->link('extension/payment/checkout_com/callback&save_card=1', '', true);
                        $payment->failure_url = $this->url->link('extension/payment/checkout_com/callback&save_card=1', '', true);
                    } else {
                        $payment->success_url = $this->url->link('extension/payment/checkout_com/callback', '', true);
                        $payment->failure_url = $this->url->link('extension/payment/checkout_com/callback', '', true);
                    }

                    try {
                        $details = $checkout->payments()->request($payment);

                        $redirection = $details->getRedirection();

                        if ($redirection) {
                            $json['redirect'] = $redirection;
                        }

                        if (!$json) {
                            if (isset($this->request->post['save_card']) && $this->request->post['save_card']) {
                                $result = $this->handlePaymentDetails($details, true);
                            } else {
                                $result = $this->handlePaymentDetails($details);
                            }

                            if (isset($result['error'])) {
                                $json['error'] = $result['error'];
                            } else {
                                $json['redirect'] = $this->url->link('checkout/success', '', true); 
                            }
                        }
                    } catch (CheckoutHttpException $ex) {
                        $json['error'] = $this->getErrorMessage($ex->getCode());
                    } catch (Exception $ex) {
                        $json['error'] = $ex->getMessage();
                    }
                }
            }
        } else {
            $this->session->data['error'] = $this->language->get('error_unknown');
            $json['redirect'] = $this->url->link('checkout/checkout', '', true); 
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function requestPayment() {
        $json = array();

        $this->load->model('extension/payment/checkout_com');

        $this->load->language('extension/payment/checkout_com');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && (isset($this->request->post['checkout_com_apm']))) {
            $this->load->model('checkout/order');

            if (isset($this->session->data['order_id'])) {
                $order_id = $this->session->data['order_id'];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $payment_method = $this->request->post['checkout_com_apm'];

                if ($this->config->get('payment_checkout_com_test')) {
                    $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
                } else {
                    $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
                }

                if ($payment_method == 'paypal') {
                    $apm = new PaypalSource($order_info['order_id'] . '-' . time());
                } elseif ($payment_method == 'alipay') {
                    $apm = new AlipaySource();
                } elseif ($payment_method == 'bancontact') {
                    if (!empty($this->request->post['checkout_com_holder_name']) && !empty($this->request->post['checkout_com_country_issued'])) {
                        $apm = new BancontactSource($this->request->post['checkout_com_holder_name'], $this->request->post['checkout_com_country_issued']);
                    } else {
                        $json['error'] = $this->language->get('error_bancontact');
                    }
                } elseif ($payment_method == 'knet') {
                    $apm = new KnetSource('en');
                } elseif ($payment_method == 'qpay') {
                    $apm = new QpaySource($this->config->get('config_name') . ' (Order ID: ' . $order_info['order_id'] . ')');
                } else {
                    $json['error'] = $this->language->get('error_payment_not_found');
                }

                $this->model_extension_payment_checkout_com->editOrderPayment($order_info['order_id'], $this->language->get('text_' . $payment_method));

                if (!$json) {
                    $json['redirect'] = false;
                    
                    $payment = $this->generatePaymentDetails($apm, $order_info);

                    $payment->success_url = $this->url->link('extension/payment/checkout_com/callback', '', true);
                    $payment->failure_url = $this->url->link('extension/payment/checkout_com/callback', '', true);

                    try {
                        $details = $checkout->payments()->request($payment);

                        $redirection = $details->getRedirection();

                        if ($redirection) {
                            $json['redirect'] = $redirection;
                        } else {
                            $json['error'] = $this->language->get('error_unknown');
                        }
                    } catch (CheckoutHttpException $ex) {
                        $json['error'] = $this->getErrorMessage($ex->getCode());
                    } catch (Exception $ex) {
                        $json['error'] = $this->language->get('error_unknown');
                        $this->log->write($ex);
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback() {
        $token = '';

        if (isset($this->request->get['cko-session-id'])) {
            $token = $this->request->get['cko-session-id'];
        } elseif ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->post['type']) && isset($this->request->post['token'])) {
            $token = $this->request->post['token'];
        }

        if ($token) {
            if ($this->config->get('payment_checkout_com_test')) {
                $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
            } else {
                $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
            }

            try {
                $details = $checkout->payments()->details($token);

                if (isset($this->request->get['save_card']) && $this->request->get['save_card']) {
                    $result = $this->handlePaymentDetails($details, true);
                } else {
                    $result = $this->handlePaymentDetails($details);
                }

                if (isset($result['error'])) {
                    $this->session->data['error'] = $result['error'];

                    $this->response->redirect($this->url->link('checkout/checkout', '', true));
                } else {
                    $this->response->redirect($this->url->link('checkout/success', '', true));
                }
            } catch (CheckoutHttpException $ex) {
                $this->session->data['error'] = $this->getErrorMessage($ex->getCode());
            } catch (Exception $ex) {
                $this->session->data['error'] = $ex->getMessage();
            }
        }
        
        $this->response->redirect($this->url->link('checkout/checkout', '', true));
    }

    public function webhook() {
        $details = json_decode(file_get_contents('php://input'), true);

        if (isset($details['id']) && isset($details['type'])) {
            $this->load->model('checkout/order');
            $this->load->model('extension/payment/checkout_com');

            $transaction_id = $details['data']['id'];

            if (isset($details['data']['reference'])) {
                $order_id = explode('-', $details['data']['reference'])[0];
            } else {
                $order_id = 0;
            }

            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info) {
                $order_info['checkout_com_transaction_id'] = $transaction_id;

                $checkout_com_order_info = $this->model_extension_payment_checkout_com->getCheckoutComOrder($order_info['order_id'], $transaction_id);

                if ($checkout_com_order_info) {
                    $checkout_com_order_id = $checkout_com_order_info['checkout_com_order_id'];
                } else {
                    $checkout_com_order_id = $this->model_extension_payment_checkout_com->addOrder($order_info);
                }

                $amount = $details['data']['amount'] / 100;
                $event_type = $details['type'];
                $order_status_id = 0;
                $status = '';
                $success = false;

                switch ($event_type) {
                    case 'card_verification_declined':
                        $status = 'cardverifydeclined';
                        $order_status_id = $this->config->get('payment_checkout_com_card_verification_declined_status_id');
                        break;
                    case 'card_verified':
                        $status = 'cardverified';
                        $order_status_id = $this->config->get('payment_checkout_com_card_verified_status_id');
                        break;
                    case 'payment_approved':
                        $status = 'approved';
                        $order_status_id = $this->config->get('payment_checkout_com_approved_status_id');
                        $success = true;
                        break;
                    case 'payment_risk_matched':
                        $status = 'riskmatched';
                        $order_status_id = $this->config->get('payment_checkout_com_risk_matched_status_id');
                        break;
                    case 'payment_pending':
                        $status = 'pending';
                        $order_status_id = $this->config->get('payment_checkout_com_pending_status_id');
                        $success = true;
                        break;
                    case 'payment_declined':
                        $status = 'declined';
                        $order_status_id = $this->config->get('payment_checkout_com_declined_status_id');
                        break;
                    case 'payment_expired':
                        $status = 'expired';
                        $order_status_id = $this->config->get('payment_checkout_com_expired_status_id');
                        break;
                    case 'payment_canceled':
                        $status = 'canceled';
                        $order_status_id = $this->config->get('payment_checkout_com_canceled_status_id');
                        break;
                    case 'payment_voided':
                        $status = 'voided';
                        $order_status_id = $this->config->get('payment_checkout_com_voided_status_id');
                        break;
                    case 'payment_void_declined':
                        $status = 'voiddeclined';
                        $order_status_id = $this->config->get('payment_checkout_com_void_declined_status_id');
                        break;
                    case 'payment_captured':
                        $status = 'captured';
                        $order_status_id = $this->config->get('payment_checkout_com_captured_status_id');
                        $success = true;
                        break;
                    case 'payment_capture_declined':
                        $status = 'capturedeclined';
                        $order_status_id = $this->config->get('payment_checkout_com_capture_declined_status_id');
                        break;
                    case 'payment_capture_pending':
                        $status = 'capturepending';
                        $order_status_id = $this->config->get('payment_checkout_com_capture_pending_status_id');
                        $success = true;
                        break;
                    case 'payment_refunded':
                        $status = 'refunded';
                        $order_status_id = $this->config->get('payment_checkout_com_refunded_status_id');
                        break;
                    case 'payment_refund_declined':
                        $status = 'refunddeclined';
                        $order_status_id = $this->config->get('payment_checkout_com_refund_declined_status_id');
                        break;
                    case 'payment_refund_pending':
                        $status = 'refundpending';
                        $order_status_id = $this->config->get('payment_checkout_com_refund_pending_status_id');
                        break;
                    case 'chargeback':
                        $status = 'chargeback';
                        $order_status_id = $this->config->get('payment_checkout_com_chargeback_status_id');
                        break;
                    default:
                        $status = '';
                        $order_status_id = 0;
                }

                if ($checkout_com_order_id) {
                    $this->model_extension_payment_checkout_com->addTransaction($checkout_com_order_id, $status, $amount);
                }
                
                $comment = 'Checkout.com Payment. Transaction ID: ' . $transaction_id;
    
                if ((!$order_info['order_status_id'] && $success) || $order_info['order_status_id']) {
                    if ($status == 'refunded') {
                        $comment .= '. Refunded Amount: ' . $details['data']['currency'] . ' ' . $this->currency->format($amount, $order_info['currency_code'], $order_info['currency_value'], false);
                    }
                    
                    $order_history_info = $this->model_extension_payment_checkout_com->getOrderHistory($order_info['order_id'], $order_status_id, $comment);
        
                    if (!$order_history_info || $status == 'refunded') {
                        $this->model_checkout_order->addOrderHistory($order_info['order_id'], $order_status_id, $comment, true);
                    }
                } else {
                    $this->model_extension_payment_checkout_com->addOrderHistory($order_info['order_id'], $order_status_id, $comment);
                }
            }
        }
    }
    
    private function generatePaymentDetails($method, $order_info) {
        $payment = new Payment($method, $order_info['currency_code']);

        $customer = new Customer();
        $customer->email = $order_info['email'];
        $customer->name = trim($order_info['firstname'] . ' ' . $order_info['lastname']);

        $payment->customer = $customer;

        $address = new Address();
        $address->address_line1 = $order_info['shipping_address_1'];
        $address->address_line2 = $order_info['shipping_address_2'];
        $address->city = $order_info['shipping_city'];
        $address->state = $order_info['shipping_zone'];
        $address->zip = $order_info['shipping_iso_code_2'];

        $phone = new Phone();
        $phone->number = (int)$order_info['telephone'];

        if (!$phone->number) {
            $payment->shipping = new Shipping($address);
        } else {
            $payment->shipping = new Shipping($address, $phone);
        }

        $payment->amount = $this->amountToDecimal($this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), $order_info['currency_code']);

        if (!$this->mada) {
            if ($this->config->get('payment_checkout_com_payment_action')) {
                $payment->capture = false;
            } else {
                $payment->capture = true;
                
                if (is_numeric($this->config->get('payment_checkout_com_capture_delay'))) {
                    $datetime = new DateTime();
                    $datetime->add(new DateInterval('PT' . $this->config->get('payment_checkout_com_capture_delay') * 60 . 'M'));
                    $payment->capture_on = $datetime->format(DateTime::ATOM);
                }
            }

            if ($this->config->get('payment_checkout_com_3d_secure')) {
                $payment->threeDs = new ThreeDs(true);
    
                if ($this->config->get('payment_checkout_com_attempt_non_3d')) {
                    $payment->threeDs->attempt_n3d = true;
                }
            } else {
                $payment->threeDs = new ThreeDs(false);
            }
        } else {
            $payment->threeDs = new ThreeDs(true);
        }

        $payment->reference = $order_info['order_id'] . '-' . time();

        if ($this->config->get('payment_checkout_com_billing_descriptor_status') && $this->config->get('payment_checkout_com_billing_descriptor_name') && $this->config->get('payment_checkout_com_billing_descriptor_city')
            && !(utf8_strlen($this->config->get('payment_checkout_com_billing_descriptor_name') > 25)) && !(utf8_strlen($this->config->get('payment_checkout_com_billing_descriptor_city') > 13))) {
            
            $payment->billing_descriptor = new BillingDescriptor($this->config->get('payment_checkout_com_billing_descriptor_name'), $this->config->get('payment_checkout_com_billing_descriptor_city'));
        }

        $payment->payment_ip = $order_info['ip'];

        $metadata = new Metadata();
        $metadata->udf5 = 'OPENCART';

        if ($this->mada) {
            $metadata->udf1 = 'mada';
        }

        $payment->metadata = $metadata;

        return $payment;
    }

    private function handlePaymentDetails($details, $save_card = false) {
        $data = array();

        $this->load->model('checkout/order');
        $this->load->model('extension/payment/checkout_com');

        $this->load->language('extension/payment/checkout_com');

        $order_id = explode('-', $details->getValue('reference'))[0];

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if ($details->isSuccessful() && $order_info) {
            $payment_status = $details->getValue('status');
            $success = false;

            switch ($payment_status) {
                case 'Pending':
                    $success = true;
                    break;
                case 'Authorized':
                    $success = true;
                    break;
                case 'Card Verified':
                    $success = true;
                    break;
                case 'Voided':
                    $success = false;
                    break;
                case 'Partially Captured':
                    $success = true;
                    break;
                case 'Captured':
                    $success = true;
                    break;
                case 'Partially Refunded':
                    $success = false;
                    break;
                case 'Refunded':
                    $success = false;
                    break;
                case 'Declined':
                    $success = false;
                    break;
                case 'Cancelled':
                    $success = false;
                    break;
                case 'Paid':
                    $success = true;
                    break;
            }

            if ($details->isFlagged()) {
                $success = false;
            }

            if ((!$order_info['order_status_id'] && $success) || $order_info['order_status_id']) {
                if ($save_card) {
                    $source = $details->getValue('source');

                    if (isset($source['id'])) {
                        $card_data = array(
                            'customer_id'   => $order_info['customer_id'],
                            'digits'        => $source['last4'],
                            'expiry'        => $source['expiry_month'] . '/' . $source['expiry_year'],
                            'source_id'     => $source['id'],
                            'scheme'        => $source['scheme']
                        );

                        $this->model_extension_payment_checkout_com->addCard($card_data);
                    }
                }

                $data['success'] = true;
            } else {
                $data['error'] = sprintf($this->language->get('error_payment'), $payment_status);
            }
        } else {
            if ($this->config->get('payment_checkout_com_test')) {
                $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'));
            } else {
                $checkout = new CheckoutApi($this->config->get('payment_checkout_com_secret_key'), false);
            }

            try {
                $response = (Array)$checkout->payments()->actions($details->getId());

                $response_code = '';
                $response_summary = '';

                if (isset($response['list'][0])) {
                    $action = $response['list'][0];

                    $response_code = $action->getValue('response_code');
                    $response_summary = $action->getValue('response_summary');
                }

                if ($response_code && $response_summary) {
                    $data['error'] = sprintf($this->language->get('error_payment'), $response_summary . '. Error Code: ' . $response_code);
                } else {
                    $data['error'] = sprintf($this->language->get('error_payment'), $details->getValue('status'));
                }
            } catch (CheckoutHttpException $ex) {
                $data['error'] = $this->getErrorMessage($ex->getCode());
            } catch (Exception $ex) {
                $data['error'] = $ex->getMessage();
            }
        }

        return $data;
    }

    private function amountToDecimal($amount, $currency_code) {
        $zero_decimal_currencies = array('BIF', 'CLF', 'DJF', 'GNF', 'ISK', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VUV', 'VND', 'XAF', 'XOF', 'XPF');
        $three_decimal_currencies = array('BHD', 'IQD', 'JOD', 'KWD', 'LYD', 'OMR', 'TND');

        if (in_array($currency_code, $three_decimal_currencies)) {
            $value = (int) ($amount * 1000);
        } elseif (in_array($currency_code, $zero_decimal_currencies)) {
            $value = floor($amount);
        } else {
            $value = round($amount * 100);
        }

        return $value;
    }

    private function getErrorMessage($code) {
        if ($code == 401) {
            $error_message = $this->language->get('error_unauthorised');
        } elseif ($code == 404) {
            $error_message = $this->language->get('error_payment_not_found');
        } elseif ($code == 422) {
            $error_message = $this->language->get('error_invalid_data');
        } elseif ($code == 429) {
            $error_message = $this->language->get('error_duplicate_requests');
        } elseif ($code == 502) {
            $error_message = $this->language->get('error_bad_gateway');
        } else {
            $error_message = $this->language->get('error_invalid_response');
        }

        return $error_message;
    }
}