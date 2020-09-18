<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement (EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://cedcommerce.com/license-agreement.txt
 *
 * @category    Ced
 * @package     Ced_Booking
 * @author      CedCommerce Core Team <connect@cedcommerce.com >
 * @copyright   Copyright CEDCOMMERCE (https://cedcommerce.com/)
 * @license      https://cedcommerce.com/license-agreement.txt
 */

namespace Ced\CustomSearchWork\Observer;

use Ced\CsMarketplace\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ProductSaveBefore
 * @package Ced\CustomSearchWork\Observer
 */
class ProductSaveBefore implements ObserverInterface
{

    /**
     * ProductSaveBefore constructor.
     * @param RequestInterface $request
     * @param Session $vendorSession
     */
    public function __construct(
        RequestInterface $request,
        Session $vendorSession
    ) {
        $this->_request = $request;
        $this->_vendorSession = $vendorSession;
    }


    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $vendor = $this->_vendorSession->getVendor();
        $product->setVendorPublicName($vendor->getPublicName());
        $product->setVendorShopUrlKey($vendor->getShopUrl());
        return $this;
    }
}
