<?php

namespace Ced\CustomSearchWork\Block\Result;

use Ced\CsHyperlocal\Helper\Data as HyperlocalHelper;
use Ced\CsHyperlocal\Model\Shiparea;
use Ced\CsMarketplace\Helper\Data as MarketplaceHelper;
use Ced\CsMarketplace\Model\ResourceModel\Vendor\CollectionFactory;
use Ced\CsMarketplace\Model\Vendor;
use Ced\CsMarketplace\Model\Vshop;
use Magento\Catalog\Model\Product;
use Magento\CatalogSearch\Helper\Data;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Search\Model\Query;
use Magento\Search\Model\QueryFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ListVendor
 * @package Ced\CustomSearchWork\Block\Result
 */
class ListVendor extends Template
{
    /**
     * @var int
     */
    protected $_defaultColumnCount = 5;

    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'Magento\Catalog\Block\Product\ProductList\Toolbar';

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $_urlHelper;
    /**
     * @var Vendor Collection
     */
    protected $_vendorCollection;
    /**
     * @var Vendor
     */
    protected $_vendor;
    /**
     * @var Data
     */
    protected $_csmarketplaceHelper;
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;
    /**
     * @var QueryFactory
     */
    protected $_queryFactory;

    protected $_hyperlocalHelper;

    /**
     * ListVendor constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param Vendor $vendor
     * @param Data $csmarketplaceHelper
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resourceConnection
     * @param QueryFactory $queryFactory
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        Vendor $vendor,
        MarketplaceHelper $csmarketplaceHelper,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        QueryFactory $queryFactory,
        Data $catalogSearchData,
        HyperlocalHelper $hyperlocalHelper,
        array $data = []
    ) {
        $this->_urlHelper = $urlHelper;
        $this->_vendor = $vendor;
        $this->_csmarketplaceHelper = $csmarketplaceHelper;
        $this->_storeManager = $storeManager;
        $this->_collectionFactory = $collectionFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_queryFactory = $queryFactory;
        $this->catalogSearchData = $catalogSearchData;
        $this->_hyperlocalHelper = $hyperlocalHelper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve loaded category collection
     *
     */
    public function getLoadedVendorCollection()
    {
        return $this->_getVendorCollection();
    }

    /**
     * Retrieve loaded category collection
     *
     */
    protected function _getVendorCollection()
    {
        if (is_null($this->_vendorCollection)) {
            $queryText = $this->getQueryText();
            $vendorShoptable = $this->_resourceConnection->getTableName('ced_csmarketplace_vendor_shop');
            $this->_vendorCollection = $this->_collectionFactory->create();
            $this->_vendorCollection->addAttributeToSelect('*');
            //$this->_vendorCollection->getSelect()->join(['vendor_shop' => $vendorShoptable], 'e.entity_id=vendor_shop.vendor_id AND vendor_shop.shop_disable=' . Vshop::ENABLED, ['shop_disable']);

            $this->_vendorCollection->addAttributeToFilter('meta_keywords', ['like' => '%' . $queryText . '%']);
            if ($this->_csmarketplaceHelper->isSharingEnabled()) {
                $this->_vendorCollection->addAttributeToFilter('website_id', $this->_storeManager->getStore()->getWebsiteId());
            }

            if ($this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::MODULE_ENABLE)) {
                //------------------- Custom Filter----------------[START]

                $savedLocationFromSession = $this->_hyperlocalHelper->getShippingLocationFromSession();
                $filterType = $this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::FILTER_TYPE);
                $radiusConfig = $this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::FILTER_RADIUS);
                $distanceType = $this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::DISTANCE_TYPE);
                $apiKey = $this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::API_KEY);
                $filterProductsBy = $this->_csmarketplaceHelper->getStoreConfig(HyperlocalHelper::FILTER_PRODUCTS_BY);

                if ($filterProductsBy == 'vendor_location' || $filterType == 'distance') {
                    $vendorIds = [0];
                    if ($savedLocationFromSession) {

                        /** Filter Products By Vendor Location */
                        if ($filterType == 'city_state_country') {

                            //------------------- Filter By City,country & state----------------[START]
                            $locationCollection = $this->_hyperlocalHelper->getFilteredlocationByCityStateCountry($savedLocationFromSession);
                            if ($locationCollection) {
                                $vendorIds = $locationCollection->getColumnValues('vendor_id');
                            }

                            //------------------- Filter By City,country & state----------------[END]
                        } elseif ($filterType == 'zipcode' && isset($savedLocationFromSession['filterZipcode'])) {

                            //------------------- Filter By Zipcode----------------[START]
                            $resource = $this->_resourceConnection;
                            $tableName = $resource->getTableName('ced_cshyperlocal_shipping_area');
                            $this->zipcodeCollection->getSelect()->joinLeft($tableName, 'main_table.location_id = ' . $tableName . '.id', ['status', 'is_origin_address']);
                            $this->zipcodeCollection->addFieldToFilter('main_table.zipcode', $savedLocationFromSession['filterZipcode'])
                                ->addFieldToFilter('status', Shiparea::STATUS_ENABLED);
                            $this->zipcodeCollection->getSelect()->where("`is_origin_address` IS NULL OR `is_origin_address` = '0'");
                            $vendorIds = $this->zipcodeCollection->getColumnValues('vendor_id');
                        //------------------- Filter By Zipcode----------------[END]
                        } elseif ($filterType == 'distance') {
                            $tolat = $savedLocationFromSession['latitude'];
                            $tolong = $savedLocationFromSession['longitude'];
                            $vIds = [];
                            if ($tolat != '' && $tolong != '') {
                                $vendorCollection = $this->_collectionFactory->create();
                                $vendorCollection->addAttributeToSelect('*');
                                if ($vendorCollection->count()) {
                                    foreach ($vendorCollection as $vendor) {
                                        $distance = $this->_hyperlocalHelper->calculateDistancebyHaversine($vendor->getLatitude(), $vendor->getLongitude(), $tolat, $tolong);
                                        if ($distance <= $radiusConfig) {
                                            $vendorIds[] = $vendor->getId();
                                        }
                                    }
                                }
                            }
                        }
                        $this->_vendorCollection->addAttributeToFilter('entity_id', ['in' => $vendorIds]);
                    }
                }
                //------------------- Custom Filter ----------------[END]
            }

            $this->prepareSortableFields();
        }
        return $this->_vendorCollection;
    }

    /**
     * @return mixed|string
     */
    protected function getQueryText()
    {
        $query = $this->_getQuery();
        $storeId = $this->getCurrentStoreId();
        $query->setStoreId($storeId);
        return $query->getQueryText();
    }

    /**
     * Retrieve query model object
     *
     * @return Query
     */
    protected function _getQuery()
    {
        return $this->_queryFactory->get();
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }

    /**
     * Prepare Sort By fields from Category Data for Vshops
     * @return $this
     */
    public function prepareSortableFields()
    {
        if (!$this->getAvailableOrders()) {
            $this->setAvailableOrders($this->_getConfig()->getAttributeUsedForSortByArray());
        }
        $cedAvailableOrders = $this->getAvailableOrders();
        if (!$this->getSortBy()) {
            if ($defaultSortBy = $this->_getConfig()->getDefaultSortBy()) {
                if (isset($cedAvailableOrders[$defaultSortBy])) {
                    $this->setSortBy($defaultSortBy);
                }
            }
        }
        return $this;
    }

    /**
     * Retrieve Catalog Config object
     *
     * @return Vendor
     */
    protected function _getConfig()
    {
        return $this->_vendor;
    }

    /**
     * Retrieve current view mode
     *
     * @return string
     */
    public function getMode()
    {
        $currentMode = $this->getChildBlock('toolbar')->getCurrentMode();
        return $currentMode;
    }

    /**
     * Retrieve list toolbar HTML
     *
     * @return string
     */
    public function getToolbarHtml()
    {
        $cedToolbar = $this->getChildHtml('toolbar');
        return $cedToolbar;
    }

    /**
     * @param Set AbstractCollection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->_vendorCollection = $collection;
        return $this;
    }

    /**
     * Retrieve search result count
     *
     * @return string
     */
    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->_getVendorCollection()->getSize();
            $this->_getQuery()->saveNumResults($size);
            $this->setResultCount($size);
        }
        return $this->getData('result_count');
    }

    /**
     * Retrieve No Result or Minimum query length Text
     *
     * @return Phrase|string
     */
    public function getNoResultText()
    {
        if ($this->catalogSearchData->isMinQueryLength()) {
            return __('Minimum Search query length is %1', $this->_getQuery()->getMinQueryLength());
        }
        return $this->_getData('no_result_text');
    }

    /**
     * Retrieve Note messages
     *
     * @return array
     */
    public function getNoteMessages()
    {
        return $this->catalogSearchData->getNoteMessages();
    }

    /**
     * Get search query text
     *
     * @return Phrase
     */
    public function getSearchQueryText()
    {
        return __("Search results for: '%1'", $this->catalogSearchData->getEscapedQueryText());
    }

    /**
     * Get post parameters
     * @param Product $product
     * @return array
     */
    public function getAddToCartPostParams(Product $product)
    {
        $cedUrl = $this->getAddToCartUrl($product);
        return [
            'action' => $cedUrl,
            'data' => [
                'product' => $product->getEntityId(),
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->_urlHelper->getEncodedUrl($cedUrl),
            ]
        ];
    }

    /**
     * @return int
     */
    public function getColumnCount()
    {
        return $this->_defaultColumnCount;
    }

    /**
     * Need use as _prepareLayout - but problem in declaring collection from
     * @return $this
     * @throws LocalizedException
     */
    protected function _beforeToHtml()
    {
        $toolbar = $this->getToolbarBlock();

        // called prepare sortable parameters
        $collection = $this->_getVendorCollection();

        // use sortable parameters
        $cedorders = $this->getAvailableOrders();
        if ($cedorders) {
            $toolbar->setAvailableOrders($cedorders);
        }
        $cedsort = $this->getSortBy();
        if ($cedsort) {
            $toolbar->setDefaultOrder($cedsort);
        }
        $ceddir = $this->getDefaultDirection();
        if ($ceddir) {
            $toolbar->setDefaultDirection($ceddir);
        }
        $cedmodes = $this->getModes();
        if ($cedmodes) {
            $toolbar->setModes($cedmodes);
        }

        // set collection to toolbar and apply sort
        $toolbar->setCollection($collection);

        $this->setChild('toolbar', $toolbar);

        $this->_getVendorCollection()->load();

        return parent::_beforeToHtml();
    }

    /**
     * Retrieve Toolbar block
     * @return BlockInterface
     * @throws LocalizedException
     */
    public function getToolbarBlock()
    {
        $cedBlockName = $this->getToolbarBlockName();
        if ($cedBlockName) {
            $cedBlock = $this->getLayout()->getBlock($cedBlockName);
            if ($cedBlock) {
                return $cedBlockName;
            }
        }

        $cedBlockName = $this->getLayout()->createBlock($this->_defaultToolbarBlock, uniqid(microtime()));
        return $cedBlockName;
    }
}
