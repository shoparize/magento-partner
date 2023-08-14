<?php

namespace Shoparize\Partner\Controller\Products;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Interceptor;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Bundle\Api\ProductLinkManagementInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\CatalogInventory\Model\Stock;

use Shoparize\PartnerPluginProductApi\Responses\FeedResponse as ShoparizeProductApiResponse;
use Shoparize\PartnerPluginProductApi\Responses\FeedItem;
use Shoparize\PartnerPluginProductApi\Responses\FeedShipping;

/**
 * Class Index
 */
class Index implements HttpGetActionInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var PageFactory
     */
    protected PageFactory $pageFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;
    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $productCollectionFactory;

    public const AVAILABILITY_IN_STOCK = 'in_stock';

    public const AVAILABILITY_OUT_OF_STOCK = 'out_of_stock';

    /**
     * @var StockItemRepository
     */
    protected StockItemRepository $stockItemRepository;
    /**
     * @var ProductFactory
     */
    protected ProductFactory $productFactory;

    public const CONDITION_NEW = 'new';
    public const CONDITION_USED = 'used';

    /**
     * @var Config
     */
    protected Config $shippingConfig;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $productCollectionFactory
     * @param PageFactory $pageFactory
     * @param RequestInterface $request
     * @param StockItemRepository $stockItemRepository
     * @param ProductFactory $productFactory
     * @param Config $shippingConfig
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context $context,
        CollectionFactory $productCollectionFactory,
        PageFactory $pageFactory,
        RequestInterface $request,
        StockItemRepository $stockItemRepository,
        ProductFactory $productFactory,
        Config $shippingConfig,
        JsonFactory $jsonFactory
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->storeManager = $context->getStoreManager();
        $this->productCollectionFactory = $productCollectionFactory;
        $this->pageFactory = $pageFactory;
        $this->request = $request;
        $this->stockItemRepository = $stockItemRepository;
        $this->productFactory = $productFactory;
        $this->shippingConfig = $shippingConfig;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute()
    {
        if (!$this->isAllow()) {
            http_response_code(400);
            exit;
        }

        $page = $this->request->getParam('page', 1);
        $limit = $this->request->getParam('limit', 100);
        $updatedAfter = $this->request->getParam('updated_after', '');

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->setFlag('has_stock_status_filter', false);
        if (!empty($updatedAfter)) {
            $collection->addAttributeToFilter('updated_at', ['gteq' => $updatedAfter]);
        }
        $collection->setPage($page, $limit);

        $response = new ShoparizeProductApiResponse();
        /**
         * @var Interceptor $item
         */
        foreach ($collection as $item) {
            $feedItem = new FeedItem();
            $feedItem->setId($item->getId());
            $feedItem->setTitle($item->getName());
            $feedItem->setDescription(strip_tags($item->getDescription() ?? ''));
            $feedItem->setLink($item->getProductUrl());
            $feedItem->setMobileLink($item->getProductUrl());

            $stockItem = $this->stockItemRepository->get($item->getId());
            $feedItem->setAvailability($stockItem->getIsInStock() ? self::AVAILABILITY_IN_STOCK : self::AVAILABILITY_OUT_OF_STOCK);
            $feedItem->setPrice((float)$item->getPrice());
            $feedItem->setSalePrice((float)$item->getFinalPrice());
            $feedItem->setCurrencyCode($this->storeManager->getStore()->getCurrentCurrencyCode());
            $feedItem->setCurrencyCode($item->getStore()->getCurrentCurrencyCode());

            $feedItem->setSizeUnit($item->getSizeUnit());
            $feedItem->setShippingWidth($item->getShippingWidth());
            $feedItem->setShippingLength($item->getShippingLength());
            $feedItem->setShippingHeight($item->getShippingHeight());
            $feedItem->setBrand($item->getBrand());
            $feedItem->setGtin($item->getGtin());

            $product = $this->productFactory->create()->load($item->getId());
            foreach ($product->getMediaGalleryImages() as $image) {
                $feedItem->setImage($image['url']);
            }

            if ($item->getData('new')) {
                $feedItem->setCondition(self::CONDITION_NEW);
            }

            $colorAttrName = $this->scopeConfig->getValue(
                'partner/general/color',
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
            $sizeAttrName = $this->scopeConfig->getValue(
                'partner/general/size',
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );
            $weightUnit = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            );

            if ($item->getData($colorAttrName)) {
                $feedItem->setColors([$item->getAttributeText($colorAttrName)]);
            }
            if ($item->getData($sizeAttrName)) {
                $feedItem->setSizes([$item->getAttributeText($sizeAttrName)]);
            }

            switch ($item->getTypeId()) {
                case 'configurable':
                    $feedItem->setPrice($item->getFinalPrice());

                    $variants = $item->getTypeInstance()->getUsedProducts($item);

                    $sizes = [];
                    $colors = [];
                    foreach ($variants as $variant) {
                        $sizeValue = $variant->getAttributeText($sizeAttrName);
                        if (!in_array($sizeValue, $sizes)) {
                            $sizes[] = $sizeValue;
                        }

                        $colorValue = $variant->getAttributeText($colorAttrName);
                        if (!in_array($colorValue, $colors)) {
                            $colors[] = $colorValue;
                        }
                    }

                    $feedItem->setSizes($sizes);
                    $feedItem->setColors($colors);
                    break;
                case 'bundle':
                    $feedItem->setPrice(
                        $item->getPriceInfo()->getPrice('regular_price')->getMinimalPrice()->getValue()
                    );
                    $feedItem->setSalePrice(
                        $item->getPriceInfo()->getPrice('final_price')->getMinimalPrice()->getValue()
                    );

                    $sizes = [];
                    $colors = [];
                    $childrenOptions = $item->getTypeInstance()->getChildrenIds($item->getId());
                    foreach ($childrenOptions as $children) {
                        foreach ($children as $childId) {
                            $childProduct = $this->productFactory->create()->load($childId);
                            $sizeValue = $childProduct->getAttributeText($sizeAttrName);
                            if (!in_array($sizeValue, $sizes)) {
                                $sizes[] = $sizeValue;
                            }

                            $colorValue = $childProduct->getAttributeText($colorAttrName);
                            if (!in_array($colorValue, $colors)) {
                                $colors[] = $colorValue;
                            }
                        }
                    }

                    $feedItem->setSizes($sizes);
                    $feedItem->setColors($colors);
                    break;
            }

            $feedItem->setShippingWeight($item->getWeight());
            $feedItem->setWeightUnit($weightUnit);

            $activeCarriers = $this->shippingConfig->getActiveCarriers();
            foreach ($activeCarriers as $carrierCode => $carrierModel) {
                if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                    $carrierTitle = $this->scopeConfig->getValue(
                        'carriers/'.$carrierCode.'/title',
                        ScopeInterface::SCOPE_STORE,
                        $this->storeManager->getStore()->getId()
                    );
                    $carrierPrice = $this->scopeConfig->getValue(
                        'carriers/'.$carrierCode.'/price',
                        ScopeInterface::SCOPE_STORE,
                        $this->storeManager->getStore()->getId()
                    );
                    $country = $this->scopeConfig->getValue(
                        'general/country/default',
                        ScopeInterface::SCOPE_STORE,
                        $this->storeManager->getStore()->getId()
                    );

                    $shipping = new FeedShipping();
                    $shipping->setService($carrierTitle);
                    $shipping->setPrice((float)$carrierPrice);
                    $shipping->setCountry($country);

                    $feedItem->setShipping($shipping);

                    break;
                }
            }

            $response->setItem($feedItem);
        }

        return $this->jsonFactory->create()->setData($response);
    }

    public function isAllow(): bool
    {
        $header = strtoupper(str_replace('-', '_', 'Shoparize-Partner-Key'));
        $shopId = $_SERVER['HTTP_' . $header] ?? null;
        if ($shopId != $this->scopeConfig->getValue(
                'partner/general/customerid',
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            )) {
            return false;
        }

        return true;
    }
}
