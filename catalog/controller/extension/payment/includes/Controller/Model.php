<?php
abstract class Controller_Model extends Controller
{

    public $_methodInstance;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language->load('extension/payment/checkoutapipayment');
        $methodType = $this->config->get('checkoutapipayment_integration_type');

        switch ($methodType)
        {
            case 'pci':
                $this->setMethodInstance(new Controller_Methods_creditcardpci($registry));
                break;

            case 'hosted':
                $this->setMethodInstance(new Controller_Methods_creditcardhosted($registry));
                break;

            case 'embedded':
                $this->setMethodInstance(new Controller_Methods_creditcardembedded($registry));
                break;

            default:
                $this->setMethodInstance(new Controller_Methods_creditcard($registry));
                break;
        }
    }

    public function index()
    {
        $this->getMethodInstance()->getIndex();
        $data = $this->getMethodInstance()->getData();

        return $this->load->view('extension/payment/checkoutapi/checkoutapipayment.tpl', $data);
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