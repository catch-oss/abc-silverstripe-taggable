<?php
namespace Azt3k\SS\Taggable;
use SilverStripe\Admin\ModelAdmin;
use Azt3k\SS\Taggable\Tag;

class TagAdmin extends ModelAdmin {
    private static $table_name = 'TagAdmin';
    /**
     * [$managed_models description]
     * @var array
     */
    private static $managed_models = array(
        Tag::class
    );

    /**
     * [$url_segment description]
     * @var string
     */
    private static $url_segment = 'Tags';

    /**
     * [$menu_title description]
     * @var string
     */
    private static $menu_title = 'Tags';
}
