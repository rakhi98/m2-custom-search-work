<?php

namespace Ced\CustomSearchWork\Plugin\Helper;

use Ced\CsMarketplace\Helper\Data as marketplaceHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Search\Model\QueryFactory;

/**
 * Class Data
 * @package Ced\CustomSearchWork\Plugin\Helper
 */
class Data
{
    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var RequestInterface
     */
    protected $_request;

    protected $_marketplaceHelper;

    /**
     * Data constructor.
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $httpRequest
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $httpRequest,
        marketplaceHelper $marketplaceHelper
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_request = $httpRequest;
        $this->_marketplaceHelper = $marketplaceHelper;
    }

    /**
     * @param \Magento\Search\Helper\Data $subject
     * @param null $query
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundgetResultUrl(
        \Magento\Search\Helper\Data $subject,
        \closure $proceed,
        $query = null
    ) {
           return $this->_urlBuilder->getUrl(
                'vendorcatalogsearch/search',
                ['_query' => [QueryFactory::QUERY_VAR_NAME => $query], '_secure' => $this->_request->isSecure()]
            );
    }
}
