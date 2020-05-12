<?php
class Omise_Gateway_Model_Payment_Offlinepaynow extends Omise_Gateway_Model_Payment_Offline_Payment
{

    /**
     * @var string
     */
    protected $_code = 'omise_offline_paynow';

    /**
     * @var string
     */
    protected $_formBlockType = 'omise_gateway/form_default';

    /**
     * @var string
     */
    protected $_infoBlockType = 'payment/info';

    /**
     * @var string
     */
    protected $_callbackUrl = 'omise/callback_validateofflinepaynow';

    /**
     * @var array
     */
    protected $_currencies = array('SGD');

    /**
     * @var string
     */
    protected $_type = 'paynow';

    /**
     * @var string
     */
    protected $_successUrl = 'omise/checkout_paynow';

    /**
     * @var string
     */
    protected $_emailTemplateId ='omise_gateway_email_paynow_orderconfirmation';

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_isGateway          = true;
    protected $_canReviewPayment   = true;
    protected $_isInitializeNeeded = true;
}