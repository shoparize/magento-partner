<?php
/**
 * @author Nayem (nayem323@gmail.com)
 * @copyright Copyright (c) 2022 Nayem
 * @package Shoparize_Partner
 */

namespace Shoparize\Partner\Block;

class Shoparize extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;
    
    /**
     * @var \Magento\Framework\Registry
     */
    public $coreRegistry;
    
    /**
     * @var \Magento\Catalog\Helper\Data
     */
    public $catalogHelper;
    
    /**
     * @var Magento\Framework\App\Http\Context
     */
    public $httpContext;
    
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
    public $checkoutSession;
    
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    public $customerFactory;
    
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    public $addressFactory;
    
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    public $regionFactory;
    
    /**
     * Tax config model
     *
     * @var \Magento\Tax\Model\Config
     */
    public $taxConfig;
    
    /**
     * Tax display flag
     *
     * @var null|int
     */
    public $taxDisplayFlag = null;
    
    /**
     * Tax catalog flag
     *
     * @var null|int
     */
    public $taxCatalogFlag = null;
    
    /**
     * Store object
     *
     * @var null|\Magento\Store\Model\Store
     */
    public $store = null;
    
    /**
     * Store ID
     *
     * @var null|int
     */
    public $storeId = null;
    
    /**
     * Base currency code
     *
     * @var null|string
     */
    public $baseCurrencyCode = null;
    
    /**
     * Current currency code
     *
     * @var null|string
     */
    public $currentCurrencyCode = null;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Tax\Model\Config $taxConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->scopeConfig     = $context->getScopeConfig();
        $this->storeManager    = $context->getStoreManager();
        $this->coreRegistry    = $coreRegistry;
        $this->catalogHelper   = $catalogHelper;
        $this->httpContext     = $httpContext;
        $this->productFactory  = $productFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerFactory = $customerFactory;
        $this->addressFactory  = $addressFactory;
        $this->regionFactory   = $regionFactory;
        $this->taxConfig       = $taxConfig;
        $this->urlInterface    = $urlInterface;
        
        parent::__construct($context, $data);
    }
    
    /**
     * Used in .phtml file and returns array of data.
     *
     * @return array
     */
    public function getShoparizeData()
    {
        $data = [];
    
        $data['enable'] = $this->getConfig(
            'partner/general/enable',
            $this->getStoreId()
        );
        $data['customerid'] = $this->getConfig(
            'partner/general/customerid',
            $this->getStoreId()
        );
        $data['full_action_name'] = $this->getRequest()->getFullActionName();
    
        return $data;
    }
    
    /**
     * Returns data needed for purchase tracking.
     *
     * @return array|null
     */
    public function getOrderData()
    {
        $order   = $this->checkoutSession->getLastRealOrder();
        $orderId = $order->getIncrementId();
        
        if ($orderId) {
            $data     = [];
            $products = [];
            $items    = $order->getAllVisibleItems();
            $i        = 0;
            $currency = $order->getOrderCurrencyCode();
            $product  = null;
            
            foreach ($items as $item) {
                $products[$i]['sku'] = $item->getSku();
                $products[$i]['id'] = $item->getProductId();
                $products[$i]['quantity'] = round($item->getQtyOrdered(), 0);
                $products[$i]['name'] = $item->getName();
                $products[$i]['price'] = $this->formatPrice($item->getPrice());
                
                $i++;
            }
            
            $data['id'] = $orderId;
            $data['items'] = $products;
            $data['currency'] = $currency;
            $data['value'] = $this->formatPrice($order->getGrandTotal());
            $data['shipping'] = $this->formatPrice($order->getShippingAmount());
            $data['tax'] = $this->formatPrice($order->getTaxAmount());
            
            return $data;
        } else {
            return null;
        }
    }
    
    /**
     * Based on provided configuration path returns configuration value.
     *
     * @param string $configPath
     * @param string|int $scope
     * @return string
     */
    public function getConfig($configPath, $scope = 'default')
    {
        return $this->scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $scope
        );
    }
    
    /**
     * Add slashes to string and prepares string for javascript.
     *
     * @param string $str
     * @return string
     */
    public function escapeSingleQuotes($str)
    {
        $removedNewLines = str_replace(["\n","\r\n","\r"], '', $str);
        return str_replace("'", "\'", $removedNewLines);
    }
    
    /**
     * Returns store object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        if ($this->store === null) {
            $this->store = $this->storeManager->getStore();
        }
        
        return $this->store;
    }
    
    /**
     * Returns Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->getStore()->getId();
        }
        
        return $this->storeId;
    }
    
    /**
     * Returns base currency code
     * (3 letter currency code like USD, GBP, EUR, etc.)
     *
     * @return string
     */
    public function getBaseCurrencyCode()
    {
        if ($this->baseCurrencyCode === null) {
            $this->baseCurrencyCode = strtoupper(
                $this->getStore()->getBaseCurrencyCode()
            );
        }
        
        return $this->baseCurrencyCode;
    }
    
    /**
     * Returns current currency code
     * (3 letter currency code like USD, GBP, EUR, etc.)
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        if ($this->currentCurrencyCode === null) {
            $this->currentCurrencyCode = strtoupper(
                $this->getStore()->getCurrentCurrencyCode()
            );
        }
        
        return $this->currentCurrencyCode;
    }
    
    /**
     * Returns formated price.
     *
     * @param string $price
     * @param string $currencyCode
     * @return string
     */
    public function formatPrice($price, $currencyCode = '')
    {
        $formatedPrice = number_format($price, 2, '.', '');
        
        if ($currencyCode) {
            return $formatedPrice . ' ' . $currencyCode;
        } else {
            return $formatedPrice;
        }
    }
}
