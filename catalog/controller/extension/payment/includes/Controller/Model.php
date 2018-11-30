<?php
abstract class Controller_Model extends Controller
{

    public $_methodInstance;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language->load('extension/payment/checkoutapipayment');
        $methodType = $this->config->get('payment_checkoutapipayment_integration_type');

        switch ($methodType)
        {
//            case 'pci':
//                $this->setMethodInstance(new Controller_Methods_creditcardpci($registry));
//                break;
//
//            case 'hosted':
//                $this->setMethodInstance(new Controller_Methods_creditcardhosted($registry));
//                break;

            case 'frames':
                $this->setMethodInstance(new Controller_Methods_creditcardframes($registry));
                break;

            default:
                $this->setMethodInstance(new Controller_Methods_creditcardframes($registry));
                break;
        }
    }

    public function index()
    {
        $data = $this->getMethodInstance()->getData();
        switch ($data['integrationType']) {
            case 'framesJs':
                    return $this->load->view('extension/payment/checkoutapi/creditcardframes', $data);
                break;
            
            default:
                
                break;
        }
    }

    public function setMethodInstance($methodInstance)
    {
        $this->_methodInstance = $methodInstance;
    }

    public function getMethodInstance()
    {

        return $this->_methodInstance;
    }

    public function send()
    {
        $this->getMethodInstance()->send();
    }


}