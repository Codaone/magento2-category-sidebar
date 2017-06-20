<?php

namespace Sebwite\Sidebar\Block;


use \Magento\Framework\View\Element\Template;
use \Magento\Framework\Data\Collection;

class Topmenu extends Template {

	/**
	 * Catalog category
	 *
	 * @var \Magento\Catalog\Helper\Category
	 */
	protected $catalogCategory;

	/**
	 * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
	 */
	private $collectionFactory;

	/**
	 * @var \Magento\Store\Model\StoreManagerInterface
	 */
	private $storeManager;

	/**
	 * @var \Magento\Catalog\Model\Layer\Resolver
	 */
	private $layerResolver;

	/**
	 * Initialize dependencies.
	 *
	 * @param \Magento\Catalog\Helper\Category $catalogCategory
	 * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
	 * @param \Magento\Store\Model\StoreManagerInterface $storeManager
	 * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
	 */
	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Catalog\Helper\Category $catalogCategory,
		\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
		\Magento\Catalog\Model\Layer\Resolver $layerResolver,
		array $data = []
	) {
		$this->catalogCategory = $catalogCategory;
		$this->collectionFactory = $categoryCollectionFactory;
		$this->storeManager = $context->getStoreManager();
		$this->layerResolver = $layerResolver;
		parent::__construct($context, $data);
	}

	/**
	 * Get current Category from catalog layer
	 *
	 * @return \Magento\Catalog\Model\Category
	 */
	private function getCurrentCategory()
	{
		$catalogLayer = $this->layerResolver->get();

		if (!$catalogLayer) {
			return null;
		}

		return $catalogLayer->getCurrentCategory();
	}

	private function getTopCategory(){
		$category = $this->_scopeConfig->getValue(
			'sebwite_sidebar/general/top-category'
		);

		if ( $category === null )
		{
			return 1;
		}

		return $category;
	}

	/**
	 * Get Category Tree
	 *
	 * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function getCategoryTree()
	{
		$rootId = $this->storeManager->getStore()->getRootCategoryId();
		$storeId = $this->storeManager->getStore()->getId();
		$topCat = $this->getTopCategory();
		/** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
		$collection = $this->collectionFactory->create();
		$collection->setStoreId($storeId);
		$collection->addAttributeToSelect('name');
		$collection->addFieldToFilter('path', ['like' => '1/' . $rootId . '/' . $topCat . '/%']); //load only from store root
		$collection->addAttributeToFilter('include_in_menu', 1);
		$collection->addIsActiveFilter();
		$collection->addUrlRewriteToResult();
		$collection->addOrder('level', Collection::SORT_ORDER_ASC);
		$collection->addOrder('position', Collection::SORT_ORDER_ASC);
		$collection->addOrder('parent_id', Collection::SORT_ORDER_ASC);
		$collection->addOrder('entity_id', Collection::SORT_ORDER_ASC);

		return $collection;
	}

	public function getHtml($outermostClass = '') {
		$html = '';

		$categories = $this->getCategoryTree();

		$itemPosition = 1;
		$currentCategory = $this->getCurrentCategory();

		foreach ($categories as $cat) {
			$outermostClassCode = ' class="' . $outermostClass . '" ';
			$cat->setClass($outermostClass);

			$html .= '<li ' . $this->_getMenuItemAttributes($cat, $itemPosition, $currentCategory) . '>';
			$html .= '<a href="' . $cat->getUrl() . '" ' . $outermostClassCode . '><span>' . $this->escapeHtml(
					$cat->getName()
				) . '</span></a></li>';
			$itemPosition++;
		}

		return $html;
	}

	/**
	 * Returns array of menu item's attributes
	 *
	 * @return string
	 */
	protected function _getMenuItemAttributes($category, $itemPosition, $currentCategory)
	{
		$classes = [];

		$classes[] = 'level0';
		$classes[] = 'nav-' . $itemPosition;

		if ($category->getId() == $currentCategory->getId()) {
			$classes[] = 'active';
		} elseif (in_array((string)$category->getId(), explode('/', $currentCategory->getPath()), true)) {
			$classes[] = 'has-active';
		}

		if ($category->hasChildren()) {
			$classes[] = 'parent';
		}

		return 'class="' . implode(' ', $classes).'"';
	}

}