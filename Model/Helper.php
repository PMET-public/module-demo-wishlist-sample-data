<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DemoWishlistSampleData\Model;

/**
 * Common functionality for installation of sample data for Wishlists
 */
class Helper
{
    private $tableName = 'wishlist_item';
    private $hourShift = -10;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source
     */
    protected $productIndexer;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Helper constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source $productIndexer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Indexer\Eav\Source $productIndexer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->customerFactory = $customerFactory;
        $this->productFactory = $productFactory;
        $this->productIndexer = $productIndexer;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $email
     * @return bool|\Magento\Customer\Model\Customer
     */
    public function getCustomerByEmail($email)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($this->storeManager->getWebsite()->getId());
        $customer->loadByEmail($email);
        if ($customer->getId()) {
            return $customer;
        }
        return false;
    }

    /**
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @param array $productSkuList
     * @return void
     */
    public function addProductsToWishlist(\Magento\Wishlist\Model\Wishlist $wishlist, $productSkuList,$productDateList)
    {
        $shouldSave = false;
        foreach ($productSkuList as $productSku) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $productId = $product->getIdBySku($productSku);
            $product->load($productId);
            if (empty($productId)) {
                continue;
            } elseif (!$shouldSave) {
                $shouldSave = true;
            }
            $buyRequest = ['product' => $productId, 'qty' => 1];
            if (!$product->isVisibleInSiteVisibility()) {
                $parentIds = $this->productIndexer->getRelationsByChild($productId);
                if ($parentIds) {
                    $buyRequest['product'] = $parentIds[0];
                    /** @var \Magento\Catalog\Model\Product $parentProduct */
                    $parentProduct = $this->productFactory->create();
                    $parentProduct->load($buyRequest['product']);
                    $configurableCode = \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE;
                    if ($parentProduct->getTypeId() == $configurableCode) {
                        /** @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable $productType */
                        $productType = $parentProduct->getTypeInstance();
                        $buyRequest['super_attribute'] = [];
                        foreach ($productType->getConfigurableAttributes($parentProduct) as $attribute) {
                            $attributeCode = $attribute->getProductAttribute()->getAttributeCode();
                            $buyRequest['super_attribute'][$attribute->getAttributeId()] = $product
                                ->getData($attributeCode);
                        }
                        $product = $parentProduct;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            $wishlist->addNewItem($product, $buyRequest, true);
        }
        $allItems = $wishlist->getItemCollection();
        $i=0;
        foreach($allItems as $item){
            $item->addData(['added_at'=>date('Y-m-d', strtotime($productDateList[$i]))]);
            $item->save();
            $i++;
        }
        if ($shouldSave) {
            $wishlist->save();
        }
    }
    public function pushDates(){
        $connection = $this->resourceConnection->getConnection();
        $sql = "select DATEDIFF(now(), max(added_at)) * 24 + EXTRACT(HOUR FROM now()) - EXTRACT(HOUR FROM max(added_at)) -1 as hours from ". $this->tableName;
        $result = $connection->fetchAll($sql);
        $dateDiff =  $result[0]['hours']+$this->hourShift;
        $sql = "update " . $this->tableName . " set added_at =  DATE_ADD(added_at,INTERVAL ".$dateDiff." HOUR)";
        $connection->query($sql);
    }
}
