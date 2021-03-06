<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Statement_Exception;

class WebsiteLink extends AbstractDecorator
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * WebsiteLink constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param DbContext $context
     */
    public function __construct(StoreManagerInterface $storeManager, DbContext $context)
    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return;
        }

        $this->addLinkedWebsiteIds($collection);
        $this->ensureWebsiteLinkedSet($collection);
    }

    /**
     * @return string
     */
    private function getProductWebsiteTable(): string
    {
        return $this->getResources()->getTableName('catalog_product_website');
    }

    /**
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    private function addLinkedWebsiteIds(Collection $collection)
    {
        $select = $this->getConnection()->select()
            ->from($this->getProductWebsiteTable(), ['product_id', 'website_id'])
            ->where('product_id in(' . implode(',', $collection->getIds()) . ')');
        $query = $select->query();

        while ($row = $query->fetch()) {
            $productId = (int)$row['product_id'];
            $collection->get($productId)->addLinkedWebsiteId((int)$row['website_id']);
        }
    }

    /**
     * @param Collection $collection
     */
    private function ensureWebsiteLinkedSet(Collection $collection)
    {
        /** @var ExportEntity $entity */
        foreach ($collection as $entity) {
            $entity->ensureWebsiteLinkedIdsSet();
        }
    }
}