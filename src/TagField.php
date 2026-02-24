<?php

namespace Azt3k\SS\Taggable;

use SilverStripe\Forms\Form;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class TagField extends TextField
{
    private static string $table_name = 'TagField';

    public function __construct(string $name, ?string $title = null, string $value = '', ?int $maxLength = null, ?Form $form = null)
    {
        parent::__construct($name, $title, $value, $maxLength, $form);

        Requirements::javascript('azt3k/abc-silverstripe-taggable:assets/build/js/lib.js');
        Requirements::javascript('azt3k/abc-silverstripe-taggable:assets/build/js/tagfield.js');
        Requirements::css('azt3k/abc-silverstripe-taggable:assets/build/css/main.css');

        $this->addExtraClass('text');
    }
}
