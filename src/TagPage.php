<?php

namespace Azt3k\SS\Taggable;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;

class TagPage extends SiteTree
{
    private static string $table_name = 'TagPage';

    private static string $allowed_children = 'none';

    private static string $icon = 'abc-silverstripe-taggable/assets/build/img/icons/tags-page';

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();
        $fields->removeFieldFromTab('Root.Main', 'Content');

        return $fields;
    }
}
