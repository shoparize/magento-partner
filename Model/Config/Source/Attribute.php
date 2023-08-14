<?php

namespace Shoparize\Partner\Model\Config\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;

use const _PHPStan_532094bc1\__;

class Attribute implements OptionSourceInterface
{
    private $attributeRepository;
    private $searchCriteria;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder $searchCriteria
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteria
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteria = $searchCriteria;
    }


    public function toOptionArray(): array
    {
        $searchCriteria = $this->searchCriteria->create();
        $attributeRepository = $this->attributeRepository->getList(
            'catalog_product',
            $searchCriteria
        );

        $options = [
            [
                'label' => __('not selected'),
                'value' => 0,
            ]
        ];
        foreach ($attributeRepository->getItems() as $attribute) {
            $options[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return $options;
    }
}
