<?php

namespace Ced\CustomSearchWork\ViewModel;

use Ced\CsMarketplace\Model\Vendor;
use Magento\Catalog\Block\Product\View;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Ced\CsMarketplace\Model\VendorFactory;
/**
 * Class Data
 * @package Ced\CustomSearchWork\ViewModel
 */
class Data implements ArgumentInterface
{

    /**
     * @var Vendor
     */
    protected $_vendorModel;

    protected $productView;

    protected $productRepository;

    protected $_tagFactory;

    protected $_vendorFactory;

    /**
     * Data constructor.
     * @param Ced\CsMarketplace\Model\Vendor $vendorModel
     */
    public function __construct(Vendor $vendorModel,
                                View $productView,
                            \Magento\Catalog\Model\ProductRepository $productRepository,
                            VendorFactory $vendorFactory,
                            \Ced\CsMarketplace\Model\Session $vendorSession,
                            \Magento\Customer\Model\Session $customerSession
                            )
    {
        $this->_vendorModel = $vendorModel;
        $this->productView = $productView ?: ObjectManager::getInstance()->get(View::class);
        $this->productRepository = $productRepository;
        $this->_vendorFactory = $vendorFactory;
        $this->vendorSession = $vendorSession;
        $this->customerSession = $customerSession;
    }

    /**
     * @return string
     */
    public function getVendorShopUrl($shopUrlKey)
    {
        $this->_vendorModel->setShopUrl($shopUrlKey);
        return $this->_vendorModel->getVendorShopUrl();
    }

    public function getAddToCartQty($_product)
    {
        $product = $this->productRepository->getById($_product->getId());
        return $this->productView->getProductDefaultQty($product);
    }

    public function getVendorId($shopUrl)
    {
        $vendor = $this->_vendorFactory->create()->loadByAttribute('shop_url', $shopUrl);
        if ($vendor)
            return $vendor->getId();
        return false;
    }


    public function getVendorSession()
    {
        return $this->vendorSession->getVendorId();
    }

    public function getCustomerSession()
    {
        return $this->customerSession->getId();
    }
}

