<?php
namespace Azt3k\SS\Taggable;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\TextField;

class TagField extends TextField {
	private static $table_name = 'TagField';
	public function __construct($name, $title = null, $value = '', $maxLength = null, $form = null) {

		parent::__construct($name, $title, $value, $maxLength, $form);

		Requirements::javascript(TAGGABLE_DIR . '/assets/build/js/lib.js');
		Requirements::javascript(TAGGABLE_DIR . '/assets/build/js/tagfield.js');
		Requirements::css(TAGGABLE_DIR . '/assets/build/css/main.css');

		$this->addExtraClass('text');
	}
}
