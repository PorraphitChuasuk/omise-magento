<?php
class Omise_Gateway_Model_Payment_Creditcard extends Omise_Gateway_Model_Payment
{
    /**
     * @var string
     */
    protected $_code = 'omise_gateway';

    /**
     * @var string
     */
    protected $_formBlockType = 'omise_gateway/form_cc';

    /**
     * @var string
     */
    protected $_infoBlockType = 'payment/info_cc';

    /**
     * Payment Method features
     *
     * @var bool
     */
    protected $_isGateway        = true;
    protected $_canAuthorize     = true;
    protected $_canCapture       = true;
    protected $_canReviewPayment = true;

    /**
     * Authorize payment
     *
     * @param  Varien_Object $payment
     * @param  float         $amount
     *
     * @return self
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        Mage::log('Omise: authorizing payment.');

        $order  = $payment->getOrder();
        $charge = $this->process(
            $payment,
            array(
                'amount'      => $this->getAmountInSubunits($amount, $order->getOrderCurrencyCode()),
                'currency'    => $order->getOrderCurrencyCode(),
                'description' => 'Charge a card from Magento that order id is ' . $order->getIncrementId(),
                'capture'     => false,
                'card'        => $payment->getAdditionalInformation('omise_token'),
                'return_uri'  => ($this->isThreeDSecureNeeded() ? $this->getThreeDSecureCallbackUri() : null)
            )
        );

        if ($charge->isAwaitForPayment() || $charge->isAwaitForCapture()) {
            return $this;
        }

        $this->suspectToBeFailed($payment);
    }

    /**
     * Capture payment
     *
     * @param  Varien_Object $payment
     * @param  float         $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::log('Omise: capturing payment.');

        if ($charge_id = $payment->getAdditionalInformation('omise_charge_id')) {
            return $this->capture_manual($payment, $charge_id);
        }

        $order  = $payment->getOrder();
        $charge = $this->process(
            $payment,
            array(
                'amount'      => $this->getAmountInSubunits($amount, $order->getOrderCurrencyCode()),
                'currency'    => $order->getOrderCurrencyCode(),
                'description' => 'Charge a card from Magento that order id is ' . $order->getIncrementId(),
                'capture'     => true,
                'card'        => $payment->getAdditionalInformation('omise_token'),
                'return_uri'  => ($this->isThreeDSecureNeeded() ? $this->getThreeDSecureCallbackUri() : null)
            )
        );

        if ($charge->isAwaitForPayment() || $charge->isSuccessful()) {
            return $this;
        }

        $this->suspectToBeFailed($payment);
    }

    /**
     * Manual capture an authorized charge.
     *
     * @param  Varien_Object $payment
     * @param  string         $charge_id
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture_manual(Varien_Object $payment, $charge_id)
    {
        $charge = $this->retrieveCharge($charge_id);
        $charge->capture();

        if (! $charge instanceof Omise_Gateway_Model_Api_Charge) {
            $message = isset($charge['message']) ? $charge['message'] : 'Payment failed. Note that your payment and order might (or might not) already has been processed. Please contact our support team to confirm your payment before resubmit.';
            Mage::throwException(Mage::helper('payment')->__($message));
        }

        if ($charge->isFailed()) {
            Mage::throwException(Mage::helper('payment')->__($charge->failure_message));
        }

        if ($charge->isSuccessful()) {
            return $this;
        }

        $this->suspectToBeFailed($payment);
    }

    /**
     * Attempt to accept a payment that us under review
     *
     * @param  Mage_Payment_Model_Info $payment
     *
     * @return bool
     *
     * @throws Mage_Core_Exception
     */
    public function acceptPayment(Mage_Payment_Model_Info $payment)
    {
        parent::acceptPayment($payment);

        return true;
    }

    /**
     * Attempt to deny a payment that us under review
     *
     * @param  Mage_Payment_Model_Info $payment
     *
     * @return bool
     *
     * @throws Mage_Core_Exception
     */
    public function denyPayment(Mage_Payment_Model_Info $payment)
    {
        parent::denyPayment($payment);

        return true;
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     *
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        Mage::log('Assign Data with Omise');

        $result = parent::assignData($data);

        if (is_array($data)) {
            if (! isset($data['omise_token'])) {
                Mage::throwException(Mage::helper('payment')->__('Cannot retrieve your credit card information. Please make sure that you put a proper card information or contact our support team if you have any questions.'));
            }

            Mage::log('Data that assign is Array');
            $this->getInfoInstance()->setAdditionalInformation('omise_token', $data['omise_token']);
        } elseif ($data instanceof Varien_Object) {
            if (! $data->getData('omise_token')) {
                Mage::throwException(Mage::helper('payment')->__('Cannot retrieve your credit card information. Please make sure that you put a proper card information or contact our support team if you have any questions.'));
            }

            Mage::log('Data that assign is Object');
            $this->getInfoInstance()->setAdditionalInformation('omise_token', $data->getData('omise_token'));
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @see app/code/core/Mage/Sales/Model/Quote/Payment.php
     */
    public function getOrderPlaceRedirectUrl()
    {
        if ($this->isThreeDSecureNeeded()) {
            return Mage::getSingleton('checkout/session')->getOmiseAuthorizeUri();
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isThreeDSecureNeeded()
    {
        return Mage::getStoreConfig('payment/omise_gateway/threedsecure') ? true : false;
    }

    /**
     * @param  array $params
     *
     * @return string
     */
    public function getThreeDSecureCallbackUri($params = array())
    {
        return Mage::getUrl(
            'omise/callback_validatethreedsecure',
            array(
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
                '_query'  => $params
            )
        );
    }

    /**
     * @return bool
     */
    public function isOscSupportEnabled()
    {
        return Mage::getStoreConfig('payment/omise_gateway/osc_support') ? true : false;
    }
}
