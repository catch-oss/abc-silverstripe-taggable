<?php
namespace Azt3k\SS\Taggable;
use Azt3k\SS\Classes\AbcDB;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\Forms\SelectionGroup_Item;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldSet;
use SilverStripe\Forms\CheckboxField;
use Azt3k\SS\Classes\DataObjectHelper;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataExtension;
use \Exception;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataList;
use Azt3k\SS\Taggable\Tag;



class Taggable extends DataExtension {
    private static $table_name = 'Taggable';
    // secret stuff
    // ------------

    protected static $cache = [];

    // Framework
    // ---------

    public static $default_num_page_items = 10;
    protected static $tags_page_link = null;

    private static $db = array(
        'Tags' => 'Text',
        'MetaKeywords' => 'Text',
        'ReGenerateTags' => 'Boolean',
        'ReGenerateKeywords' => 'Boolean',
        'RestrictToKnownTags' => 'Boolean',
        'TreatHashTagsAsKnownTags' => 'Boolean',
        'BlockScrape' => 'Boolean',
    );

    private static $defaults = array(
        'ReGenerateTags' => true,
        'ReGenerateKeywords' => true,
        'TreatHashTagsAsKnownTags' => true,
        'RestrictToKnownTags' => false
    );

    private static $indexes = array(
        'Tags'  => array(
            'type' => 'fulltext',
            'columns' => ['Tags']
        )
    );

    /*
    These fields do not display in model admin
    also where is updateCMSFields_forPopup
    */
    public function updateCMSFields(FieldList $fields) {

        $fields->removeByName('BlockScrape');

        if (get_class($fields->fieldByName('Root.Main')) == TabSet::class) {

            $fields->addFieldsToTab('Root.Main.Meta', $this->getTagFields());

        } else if (get_class($fields->fieldByName('Root')) == TabSet::class) {

            $fields->addFieldsToTab('Root.Meta', $this->getTagFields());

        } else if (get_class($fields) == FieldSet::class || get_class($fields) == FieldList::class) {

            foreach ($this->getTagFields() as $f) {
                $fields->push($f);
            }
        }

    }


    // static Methods
    // ---------------

    protected static function get_blacklisted_words() {
        return array(
            'of','a','the','and','an','or','nor',
            'but','is','if','then','else','when',
            'at','from','by','on','off','for',
            'in','out','over','to','into','with',
            'also','back','well','big','when','where',
            'why','who','which', 'it', 'be', 'so', 'far',
            'one', 'our', 'we','only','they','this', 'i',
            'do', 'there', 'just', 'that'
        );
    }

    public static function str_to_tags($str) {
        $tags = array_map('trim', explode(',', $str));
        $out = array();
        foreach ($tags as $tag) {
            if (!in_array(strtolower($tag), static::get_blacklisted_words())) {
                $out[] = trim($tag, ',.!?');
            }
        }
        return $out;
    }

    // Actual Methods
    // --------------

    public function getIncludeInDump(){
        $includeInDump = method_exists($this->owner, 'getIncludeInDump') ? $this->owner->getIncludeInDump() : array();
        $includeInDump = (  !empty($includeInDump) && is_array($includeInDump) ) ? $includeInDump : array() ;
        $includeInDump[] = 'TagURLStr';
        $includeInDump = array_unique($includeInDump);
        return $includeInDump;
    }

    /**
     * @return array
     */
    protected function getTagFields() {
        $fields = new FieldList(
            LiteralField::create('BlockScrapeTitle', '<p>Block tag and meta keywords generation</p>'),
            SelectionGroup::create('BlockScrape', [
                new SelectionGroup_Item(
                    true,
                    [],
                    'Yes'
                ),
                new SelectionGroup_Item(
                    false,
                    [
                        new CheckboxField('ReGenerateTags', 'Regenerate tags on save'),
                        new CheckboxField('ReGenerateKeywords', 'Regenerate keywords on save'),
                        new CheckboxField('RestrictToKnownTags', 'Restrict to known terms when regenerating'),
                        new CheckboxField('TreatHashTagsAsKnownTags', 'Treat hash tags as known tags'),
                    ],
                    'No'
                ),
            ])->addExtraClass('field'),
            new TextField('MetaKeywords', 'Meta Keywords (comma separated)'),
            new TextField('Tags', 'Tags (comma separated)')
        );

        return $fields;
    }

    // need to get these to work properly
    public function getExplodedTags(){
        return static::explode_tags($this->owner->Tags);
    }

    public function setExplodedTags($tags){
        $this->owner->Tags = is_array($tags) ? implode(',', array_map('trim', $tags)) : $tags ;
    }

    public function getTagURLStr(){
        return $this->owner->Tags
            ? self::tags2Links($this->owner->Tags)
            : null ;
    }

    /**
     * extracts hashtags from a string
     * @param  string $str [description]
     * @return array        [description]
     */
    public static function extract_hash_tags($str) {
        $hashtags = [];
        preg_match_all('/(#\w+)/u', $str, $matches);
        if ($matches) {
            $hashtagsArray = array_count_values($matches[0]);
            $hashtags = array_keys($hashtagsArray);
        }
        return $hashtags;
    }

    /**
     * converts a string of tags into an array
     * @param  string $tags [description]
     * @return array        [description]
     */
    public static function explode_tags($tags) {
        if (is_array($tags)) return $tags;
        return array_map('trim', explode(',', $tags ?? ''));
    }

    /**
     * cache proxy method for DataObjectHelper
     * @return [type] [description]
     */
    protected static function extended_classes() {
        $key = 'extended_classes';
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getExtendedClasses('Taggable');
        }
        return static::$cache[$key];
    }

    /**
     * cache proxy method for DataObjectHelper
     * @param  [type] $className [description]
     * @return [type]            [description]
     */
    protected static function table_for_class($className) {
        $key = 'table_for_class' . $className;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getTableForClass($className);
        }
        return static::$cache[$key];
    }

    /**
     * cache proxy method for DataObjectHelper
     * @param  [type] $className [description]
     * @param  [type] $prop      [description]
     * @return [type]            [description]
     */
    protected static function extension_table_for_class_with_property($className, $prop) {
        $key = 'extension_table_for_class_with_property' . $className . $prop;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] =  DataObjectHelper::getExtensionTableForClassWithProperty($className, $prop);
        }
        return static::$cache[$key];
    }

    /**
     * cache proxy method for DataList
     */
    protected static function all_tags() {
        $tKey = 'full-tag-list';
        if (empty(static::$cache[$tKey])) static::$cache[$tKey] = new DataList(Tag::class);
        return static::$cache[$tKey];
    }

    /**
     * cache proxy method for all_tags()->map
     */
    protected static function all_tag_arr() {
        $tKey = 'full-tag-list-arr';
        if (empty(static::$cache[$tKey])) {
            $r = array();
            foreach (static::all_tags() as $tag) {
                $r[] = $tag->Title;
            }
            static::$cache[$tKey] = $r;
        }
        return static::$cache[$tKey];
    }

    /**
     * converts an arg into a safe key for the cache
     * @param  polymorphic $arg [description]
     * @return string           [description]
     */
    protected static function safe_args($arg) {
        if (is_array($arg)) $arg = implode('_', $arg);
        return preg_replace('/[^A-Za-z0-9]/', '_', $arg);
    }

    /**
     * Returns a datalist filtered by tags
     * @param  string       $className  the name of the class to get
     * @param  array|string $tags       description]
     * @param  string $tags $where      an additional SQL fragment to append to the where clause
     * @return DataList                 the data list containing the tagged content
     */
    public static function tagged_with($className, $tags, $where = '', $lookupMode = 'OR') {

        // validate args
        if ($lookupMode != 'AND' && $lookupMode != 'OR')
            throw new Exception('Invalid lookupMode supplied');

        // generate a cache key
        $key = preg_replace('/[^A-Za-z0-9]/', '_', __FUNCTION__) .
               implode(
                    '_',
                    array_map(
                        array(get_called_class(), 'safe_args'),
                        func_get_args()
                    )
                );

        // chache hit?
        if (empty(static::$cache[$key])) {

            // sanity check
            if (!is_array($tags)) $tags = static::explode_tags($tags);

            // set where fragment
            $tWhere = '';

            // build tag filter
            foreach ($tags as $tag) {
                $cleanTag = preg_replace("/[\(\)\']+/", '', Convert::raw2sql($tag));
                $tWhere .= ($tWhere ? $lookupMode : '' ) .
                          ' Tags REGEXP \'(^|,| )+' . $cleanTag . '($|,| )+\' ';
            }

            // allow for AND / OR to be supplied in the $where
            $firstWord = explode(' ', strtoupper(trim($where)))[0];
            if ($where && $firstWord != 'AND' && $firstWord != 'OR') $where = 'AND (' . $where . ')';

            // compile complete where
            $where = '(' . $tWhere . ') ' . $where;

            // store this Datalist for later
            static::$cache[$key] = DataList::create($className)->where($where);
        }

        // return the cached value
        return static::$cache[$key];
    }

    /**
     * ye olde getTaggedWith method - hopefully superceeded by the tagged_with method
     * @param [type]  $tags       [description]
     * @param [type]  $filterSql  [description]
     * @param integer $start      [description]
     * @param integer $limit      [description]
     * @param string  $lookupMode if AND then you get content tagged with all ptovided tags
     *                            if OR then you get content tagged with at least one of the provided tags
     */
    public static function getTaggedWith($tags, $filterSql = null, $start = 0, $limit = 40, $lookupMode = 'OR') {

        // generate a cache key
        $key = preg_replace('/[^A-Za-z0-9]/', '_', __FUNCTION__) .
               implode(
                    '_',
                    array_map(
                        array(get_called_class(), 'safe_args'),
                        func_get_args()
                    )
                );

        // chache hit?
        if (empty(static::$cache[$key])) {

            // clean up input
            if (!is_array($tags)) $tags = static::explode_tags($tags);
            if ($lookupMode != 'AND' && $lookupMode != 'OR') throw new Exception('Invalid lookupMode supplied');

            // Set some vars
            $classes     = static::extended_classes();
            $set         = new ArrayList;
            $db          = AbcDB::getInstance();
            $sql         = '';
            $tables = $joins = $filter = array();

            // Build Query Data
            foreach($classes as $className){

                // Fetch Class Data
                $table      = static::table_for_class($className);
                $extTable   = static::extension_table_for_class_with_property($className, 'Tags');

                // $tables we are working with
                if ($table) $tables[$table] = $table;

                // join
                if ($table && $extTable && $table!=$extTable) {
                    $joins[$table][] = $extTable;
                } elseif($extTable) {
                    $tables[$extTable] = $extTable;
                }

                // Where
                if ($table) $where[$table][] = "LOWER(" .$table . ".ClassName) = '" . strtolower($className) . "'";

                // Tag filter
                // Should be REGEX so we don't get partial matches
                if ($extTable) {
                    foreach ($tags as $tag) {
                        $cleanTag = preg_replace("/[\(\)\']+/", '', Convert::raw2sql($tag));
                        $filter[$table][] = $extTable . ".Tags REGEXP '(^|,| )+" . $cleanTag . "($|,| )+'";
                    }
                }
            }

            // Build Query
            foreach($tables as $table){

                if (array_key_exists($table, $joins)){

                    // Prepare Where Statement
                    $uWhere     = array_unique($where[$table]);
                    $uFilter    = array_unique($filter[$table]);

                    // this lookupMode injection will prob break something in AND mode
                    $wSql         = "(".implode(' OR ',$uWhere).") AND (".implode(' ' . $lookupMode . ' ',$uFilter).")";

                    // Make the rest of the SQL
                    if ($sql) $sql.= "UNION ALL"."\n\n";
                    $rowCountSQL = !$sql ? "SQL_CALC_FOUND_ROWS " : "" ;
                    $sql.= "SELECT " . $rowCountSQL . $table . ".ClassName, " . $table . ".ID" . "\n";
                    $sql.= "FROM " . $table . "\n";

                    // join
                    $join = array_unique($joins[$table]);
                    foreach($join as $j){
                        $sql .= " LEFT JOIN " . $j . " ON " . $table . ".ID = " . $j . ".ID" . "\n";
                    }

                    // Add the WHERE statement
                    $sql .= "WHERE " . $wSql . "\n\n";
                }
            }

            // Add Global Filter to Query
            if ($filterSql) {
                $sql .= (count($tables) == 1 ? "AND " : "WHERE ") . $filterSql;
            }

            // Add Limits to Query
            $sql .= " LIMIT " . $start . "," . $limit;

            // Get Data
            $result = $db->query($sql);
            $result = $result ? $result->fetchAll(PDO::FETCH_OBJ) : array() ;

            // Convert to DOs
            foreach( $result as $entry ){

                // Make the data easier to work with
                $entry         = (object) $entry;
                $className     = $entry->ClassName;

                // this is faster but might not pull in relations
                //$dO = new $className;
                //$dO = DataObjectHelper::populate($dO, $entry);

                // this is slower, but will be more reliable
                $dO = DataObject::get_by_id($className, $entry->ID);

                $set->push($dO);
            }
            $set->unlimitedRowCount = $db->query('SELECT FOUND_ROWS() AS total')->fetch(PDO::FETCH_OBJ)->total;

            static::$cache[$key] = $set;

        }

        return static::$cache[$key];

    }

    // attach specific urls to tags for rendering

    public static function tags2Links($strTags){

        // find the url of the tags page
        if (!$tagsPageURL = self::getTagPageLink()) throw new Exception('There is no page of type TagsPage in the site tree');

        $outputTags = explode(',',$strTags);
        $tempTags = array();

        foreach($outputTags as $oTags){
            array_push($tempTags, "<a href='".$tagsPageURL."tag/".trim($oTags)."'>".trim($oTags)."</a>");
        }

        return implode(', ', $tempTags);
    }

    public static function getTagPageLink(){
        if (!self::$tags_page_link){
            if (!$tagsPage = DataObject::get_one('TagPage')) return false ;
            self::$tags_page_link = $tagsPage->Link();
        }
        return self::$tags_page_link;
    }

    public function getAssociatedLink(){
        if (method_exists($this->owner, 'Link')) return $this->owner->Link();
        return false;
    }

    public function getAssociatedImage(){
        if (method_exists($this->owner, 'getAssociatedImage')) return $this->owner->getAssociatedImage();
        if (method_exists($this->owner, 'getAddImage')) return $this->owner->getAddImage();
        if (method_exists($this->owner, 'Image')) return $this->owner->Image();
        return false;
    }

    // onBeforeWrite
    // ----------------------------------------------------------------------------

    /**
     * we currently always append hashtags
     * this might produce unexpected results if they are using RestrictToKnownTags
     * are hashtags "known tags"?
     * do we need another flag e.g. TreatHashTagsAsKnownTags?
     * appending tags to the Tag table on save?
     * @return void
     */
    public function onBeforeWrite() {

        // call the parent onBeforeWrite
        parent::onBeforeWrite();

        // do nothing if block scrape is set
        if ($this->owner->BlockScrape) return;

        // add some tags if there are none or we are forcing a refresh
        if (
            !$this->owner->Tags ||
            $this->owner->ReGenerateTags ||
            $this->owner->ReGenerateKeywords
        ) {

            // double check to see if there are any meta key words and we aren't forcing a refresh
            if (
                !empty($this->owner->MetaKeywords) &&
                !$this->owner->ReGenerateTags &&
                !$this->owner->ReGenerateKeywords
            ) {

                // if there are keywords and no tags use the keywords
                $this->owner->Tags = $this->owner->MetaKeywords;
            }

            // there were no meta keywords or we are forcing a refresh
            else {

                // get the blacklist
                $exclude = static::get_blacklisted_words();

                // init recievers
                $words = $parsed = array();

                // look at the existing tags
                if ($this->owner->RestrictToKnownTags) {

                    // handle the loading
                    $tags = static::all_tag_arr();

                    // compare each tag with the content
                    foreach ($tags as $tag) {

                        // title weighting x3
                        if (stripos((string) strip_tags((string) $this->owner->Title), (string) $tag) !== false)
                            $words = array_merge($words, array($tag, $tag, $tag));

                        // add the content
                        if (stripos((string) strip_tags((string) $this->owner->Content), (string) $tag) !== false)
                            $words[] = $tag;

                    }
                }

                // analyse the text
                else {

                    // generate words from content
                    $titlePieces = explode(' ', strip_tags((string) $this->owner->Title));

                    // title weighting x3
                    if (!empty($this->owner->Title))
                        $words = array_merge($words, $titlePieces, $titlePieces, $titlePieces);

                    // add the content
                    if (!empty($this->owner->Content))
                        $words = array_merge($words, explode(' ', strip_tags($this->owner->Content)));

                }

                // generate weightings
                foreach($words as $word){
                    $word = strtolower(trim(html_entity_decode(strval($word))));
                    $word = trim($word, ',.!');
                    if ($word && !in_array(strtolower($word),$exclude) && substr($word,0,1) != '&' && strlen($word) > 3)
                        $parsed[$word] = !empty($parsed[$word]) ? ($parsed[$word] + 1) : 1 ;
                }

                // sort by weight and extract the top 15
                arsort($parsed);
                $sample = array_keys(array_slice($parsed, 0, 15));

                // check again
                $dChecked = array();
                foreach ($sample as $value) {
                    $value = strval($value);
                    if (!empty($value) && strlen($value) > 3 ) $dChecked[] = $value;
                }

                // append any hashtags
                if (
                    !$this->owner->RestrictToKnownTags ||
                    (
                        $this->owner->RestrictToKnownTags &&
                        $this->owner->TreatHashTagsAsKnownTags
                    )
                ) {
                    $dChecked = array_merge(
                        $dChecked,
                        static::extract_hash_tags($this->owner->Title . ' ' . $this->owner->Content)
                    );
                }

                // generate string
                $tags = implode(', ', $dChecked);

                // update tags if there are none or we are doing a forced update
                if ($this->owner->ReGenerateTags || !$this->owner->Tags) $this->owner->Tags = $tags;

                // update meta keywords if there are none
                // there's a reconciliation between tags and keywords further down
                if ($this->owner->ReGenerateKeywords) $this->owner->MetaKeywords = $tags;
            }
        }

        // add meta keywords if there are none
        if (!$this->owner->MetaKeywords) {
            if ($this->owner->Tags) $this->owner->MetaKeywords = $this->owner->Tags;
        }

        // lowercase
        $this->owner->Tags = strtolower($this->owner->Tags);
        $this->owner->MetaKeywords = strtolower($this->owner->MetaKeywords);

    }
}
