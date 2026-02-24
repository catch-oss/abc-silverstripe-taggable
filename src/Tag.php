<?php

namespace Azt3k\SS\Taggable;

use SilverStripe\ORM\DataObject;

class Tag extends DataObject
{
    private static string $table_name = 'Tag';

    private static array $db = [
        'Title' => 'Varchar(255)',
    ];

    private static array $indexes = [
        'Title' => true,
    ];
}
