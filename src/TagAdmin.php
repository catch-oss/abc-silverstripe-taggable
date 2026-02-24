<?php

namespace Azt3k\SS\Taggable;

use SilverStripe\Admin\ModelAdmin;

class TagAdmin extends ModelAdmin
{
    private static string $table_name = 'TagAdmin';

    private static array $managed_models = [
        Tag::class,
    ];

    private static string $url_segment = 'Tags';

    private static string $menu_title = 'Tags';
}
