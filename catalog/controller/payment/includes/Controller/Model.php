<?php
abstract class Controller_Model extends Controller
{

    public $_methodInstance;

    public function __construct($registry)
    {

        parent::__construct($registry);
        $this->language->load('payment/checkoutapipayment');
         $methodType = $this->config->get('integration_type');

        switch ($methodType)
        {
            case 'checkoutjs':
                $this->setMethodInstance(new Controller_Methods_creditcard($registry));
                break;

            case 'frames':
                $this->setMethodInstance(new Controller_Methods_creditcardframes($registry));
                break;

            default:
                $this->setMethodInstance(new Controller_Methods_creditcard($registry));
                break;
        }

    }

    protected function index()
    {
        $this->getMethodInstance()->getIndex();
        $data = $this->getMethodInstance()->data;

        foreach ($data as $key=>$val) {

            $this->data[$key] = $val;
        }

        $this->template = $this->getMethodInstance()->template;


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