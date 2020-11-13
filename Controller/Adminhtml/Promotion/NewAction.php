<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omise\Payment\Controller\Adminhtml\Promotion;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class NewAction extends \Magento\SalesRule\Controller\Adminhtml\Promo\Quote implements HttpGetActionInterface
{
    /**
     * New promo quote action
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
