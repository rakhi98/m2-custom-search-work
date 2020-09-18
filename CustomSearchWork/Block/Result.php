<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Ced\CustomSearchWork\Block;

use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\CatalogSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;

/**
 * Product search result block
 *
 * @api
 * @since 100.0.2
 *
 */
class Result extends Template
{
    /**
     * Catalog Product collection
     *
     * @var Collection
     */
    protected $productCollection;

    /**
     * Catalog search data
     *
     * @var Data
     */
    protected $catalogSearchData;

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $catalogLayer;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @param Context $context
     * @param LayerResolver $layerResolver
     * @param Data $catalogSearchData
     * @param QueryFactory $queryFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->catalogLayer = $layerResolver->get();
        $this->_urlInterface = $urlInterface;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve additional blocks html
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        return $this->getLayout()->getBlock('vendorcatalogsearch_search_result')->getChildHtml('additional');
    }

    public function getVendorListHtml()
    {
        return $this->getChildHtml('vendorcatalogsearch_search_result_listvendor');
    }

    public function getDishesSearchUrl()
    {
        return $this->getSearchUrl() . '&show=dishes';
    }

    public function getSearchUrl()
    {
        $currentUrl = $this->_urlInterface->getCurrentUrl();
        $splitUrl = explode('?', $currentUrl);
        if (isset($splitUrl[1])) {
            $query = explode('&', $splitUrl[1]);
            if (isset($query[0])) {
                $currentUrl = $splitUrl[0] . '?' . $query[0];
            }
        }
        return $currentUrl;
    }

    public function showProductListHtml()
    {
        /*if ($this->getRequest()->getParam('show') && $this->getRequest()->getParam('show') == 'dishes') {
            return true;
        }*/
        return true;
    }

    /**
     * Retrieve Search result list HTML output
     *
     * @return string
     */
    public function getProductListHtml()
    {
        return $this->getChildHtml('vendorcatalogsearch_search_result_listproduct');
    }


    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $title = $this->getSearchQueryText();
        $this->pageConfig->getTitle()->set($title);
        // add Home breadcrumb
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');
        if ($breadcrumbs) {
            $breadcrumbs->addCrumb(
                'home',
                [
                    'label' => __('Home'),
                    'title' => __('Go to Home Page'),
                    'link' => $this->_storeManager->getStore()->getBaseUrl()
                ]
            )->addCrumb(
                'search',
                ['label' => $title, 'title' => $title]
            );
        }

        return parent::_prepareLayout();
    }
}
