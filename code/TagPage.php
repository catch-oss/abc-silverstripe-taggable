<?php
namespace Azt3k\SS\Taggable;
use Azt3k\SS\Classes\AbcPaginator;
use SilverStripe\Control\Director;
use Azt3k\SS\Taggable\Taggable;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
class TagPage extends Sitetree {

	private static $allowed_children = 'none';

	private static $icon = 'abc-silverstripe-taggable/assets/build/img/icons/tags-page';

	public function getCMSFields() {

		$fields = parent::getCMSFields();
		$fields->removeFieldFromTab( 'Root.Main', 'Content' );

		return $fields;
	}

}

class TagPage_Controller extends Controller {

	/**
	 * An array of actions that can be accessed via a request. Each array element should be an action name, and the
	 * permissions or conditions required to allow the user to access it.
	 *
	 * <code>
	 * array (
	 *     'action', // anyone can access this action
	 *     'action' => true, // same as above
	 *     'action' => 'ADMIN', // you must have ADMIN permissions to access this action
	 *     'action' => '->checkAction' // you can only access this action if $this->checkAction() returns true
	 * );
	 * </code>
	 *
	 * @var array
	 */
	public static $allowed_actions = array (
		'tag'
	);

	public function init() {
		parent::init();
	}


	/*
	 * tag Action
	 */
	public function tag(){

		$this->TagStr = $tag = Director::urlParam('ID');

		// page limits
		$paginator = new AbcPaginator(Taggable::$default_num_page_items);
		$dataSet = Taggable::getTaggedWith($tag, null, $paginator->start, $paginator->limit);

		$this->TagSet = $dataSet;

		// Supply template with pagination data
		$this->Paginator = $paginator->dataForTemplate($dataSet->unlimitedRowCount, 2);

		return array();

	}

}
