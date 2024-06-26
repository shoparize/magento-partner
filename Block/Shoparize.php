<?php

namespace Shoparize\Partner\Block;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\ProductFactory;
use Magento\Checkout\Model\Session;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\Config;

class Shoparize extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    public ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var Registry
     */
    public Registry $coreRegistry;

    /**
     * @var Data
     */
    public Data $catalogHelper;

    /**
     * @var Magento\Framework\App\Http\Context
     */
    public $httpContext;

    /**
     * @var ProductFactory
     */
    public ProductFactory $productFactory;

    /**
     * @var Session
     */
    public Session $checkoutSession;

    /**
     * @var CustomerFactory
     */
    public CustomerFactory $customerFactory;

    /**
     * @var AddressFactory
     */
    public AddressFactory $addressFactory;

    /**
     * @var RegionFactory
     */
    public RegionFactory $regionFactory;

    /**
     * Tax config model
     *
     * @var Config
     */
    public Config $taxConfig;

    /**
     * @var null|int
     */
    public ?int $taxDisplayFlag = null;

    /**
     * @var null|int
     */
    public ?int $taxCatalogFlag = null;

    /**
     * @var null|Store
     */
    public ?Store $store = null;

    /**
     * @var null|int
     */
    public ?int $storeId = null;

    /**
     * @var null|string
     */
    public ?string $baseCurrencyCode = null;

    /**
     * @var null|string
     */
    public ?string $currentCurrencyCode = null;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Registry $coreRegistry
     * @param Data $catalogHelper
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ProductFactory $productFactory
     * @param Session $checkoutSession
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param RegionFactory $regionFactory
     * @param Config $taxConfig
     * @param UrlInterface $urlInterface
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Registry $coreRegistry,
        Data $catalogHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        ProductFactory $productFactory,
        Session $checkoutSession,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        RegionFactory $regionFactory,
        Config $taxConfig,
        UrlInterface $urlInterface,
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
                $products[$i]['sku'] = addslashes($item->getSku());
                $products[$i]['id'] = $item->getProductId();
                $products[$i]['quantity'] = (int)$item->getQtyOrdered();
                $products[$i]['name'] = addslashes($item->getName());
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
     * @return Store
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
