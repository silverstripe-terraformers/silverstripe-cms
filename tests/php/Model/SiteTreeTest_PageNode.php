<?php

namespace SilverStripe\CMS\Tests\Model;

use SilverStripe\Dev\TestOnly;
use SilverStripe\CMS\Model\SiteTree;

class SiteTreeTest_PageNode extends SiteTree implements TestOnly
{
    private static $table_name = 'SiteTreeTest_PageNode';
}
