<?php
class Omise_Gateway_Callback_ValidateofflinekonbiniController extends Omise_Gateway_Controller_Base
{
    const PAYMENT_TITLE = 'Konbini Store';
    /**
     * @return Mage_Core_Controller_Varien_Action|void
     * @throws Mage_Core_Exception
     */
    public function indexAction()
    {
        return $this->validate();
    }
}