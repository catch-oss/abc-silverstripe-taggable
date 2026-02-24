<?php
namespace Azt3k\SS\Taggable;
use SilverStripe\ORM\DataObject;

class Tag extends DataObject {
    private static $table_name = 'Tag';
    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $indexes = array(
        'Title' => true
    );
}
