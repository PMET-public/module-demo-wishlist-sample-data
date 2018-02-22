<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\DemoWishlistSampleData\Setup;

use Magento\Framework\Setup;

class Installer implements Setup\SampleData\InstallerInterface
{
    /**
     * @var \Magento\WishlistSampleData\Model\Wishlist
     */
    protected $wishlist;

    /**
     * @param \Magento\WishlistSampleData\Model\Wishlist $wishlist
     */
    public function __construct(\MagentoEse\DemoWishlistSampleData\Model\Wishlist $wishlist) {
        $this->wishlist = $wishlist;
    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
        $this->wishlist->install(['MagentoEse_DemoWishlistSampleData::fixtures\wishlist.csv']);
    }
}
