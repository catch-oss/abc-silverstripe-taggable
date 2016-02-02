<?php

class Taggable extends DataExtension {

    // secret stuff
    // ------------

    protected static $cache = [];

    // Framework
    // ---------

    public static $default_num_page_items     = 10;
    protected static $tags_page_link        = null;

    private static $db = array(
        'Tags' => 'Text',
        'MetaKeywords' => 'Text',
        'ReGenerateTags' => 'Boolean',
        'ReGenerateKeywords' => 'Boolean',
        'RestrictToKnownTags' => 'Boolean',
    );

    private static $indexes = array(
        'Tags'  => array(
            'type' => 'fulltext',
            'value' => '"Tags"'
        )
    );

    /*
    These fields do not display in model admin
    also where is updateCMSFields_forPopup
    */
    public function updateCMSFields(FieldList $fields) {

        if (get_class($fields->fieldByName('Root.Main')) == 'TabSet') {

            $fields->addFieldsToTab('Root.Main.Metadata', $this->getTagFields());

        } else if (get_class($fields->fieldByName('Root')) == 'TabSet') {

            $fields->addFieldsToTab('Root.Metadata', $this->getTagFields());

        } else if (get_class($fields) == 'FieldSet' || get_class($fields) == 'FieldList') {

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
            new CheckboxField('RestrictToKnownTags', 'Restrict to known terms when regenerating'),
            new TextField('MetaKeywords', 'Meta Keywords (comma separated)'),
            new CheckboxField('ReGenerateKeywords', 'Regenerate Keywords'),
            new TextField('Tags', 'Tags (comma separated)'),
            new CheckboxField('ReGenerateTags', 'Regenerate Tags')
        );

        return $fields;
    }

    // need to get these to work properly
    public function getExplodedTags(){
        return array_map('trim', explode(',', $this->owner->Tags));
    }

    public function setExplodedTags($tags){
        $this->owner->Tags = is_array($tags) ? implode(',', array_map('trim', $tags)) : $tags ;
    }

    public function getTagURLStr(){
        return $this->owner->Tags
            ? self::tags2Links($this->owner->Tags)
            : null ;
    }

    protected static function extended_classes() {
        $key = 'extended_classes';
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getExtendedClasses('Taggable');
        }
        return static::$cache[$key];
    }

    protected static function table_for_class($className) {
        $key = 'table_for_class' . $className;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getTableForClass($className);
        }
        return static::$cache[$key];
    }

    protected static function extension_table_for_class_with_property($className, $prop) {
        $key = 'extension_table_for_class_with_property' . $className . $prop;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] =  DataObjectHelper::getExtensionTableForClassWithProperty($className, $prop);
        }
        return static::$cache[$key];
    }

    protected static function safe_args($arg) {
        if (is_array($arg)) $arg = implode('_', $arg);
        return preg_replace('/[^A-Za-z0-9]/', '_', $arg);
    }

    /**
     * [getTaggedWith description]
     * @param [type]  $tags       [description]
     * @param [type]  $filterSql  [description]
     * @param integer $start      [description]
     * @param integer $limit      [description]
     * @param string  $lookupMode if AND then you get content tagged with all ptovided tags
     *                            if OR then you get content tagged with at least one of the provided tags
     */
    public static function getTaggedWith($tags, $filterSql = null, $start = 0, $limit = 40, $lookupMode = 'OR') {

        $key = preg_replace('/[^A-Za-z0-9]/', '_', __FUNCTION__) .
               implode(
                    '_',
                    array_map(
                        array(get_called_class(), 'safe_args'),
                        func_get_args()
                    )
                );

        if (empty(static::$cache[$key])) {

            // clean up input
            if (!is_array($tags)) $tags = array($tags);
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
                        $filter[$table][] = $extTable . ".Tags REGEXP '(^|,| )+" . Convert::raw2sql($tag) . "($|,| )+'";
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
            // die($sql);
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

    public function onBeforeWrite()
    {
        // call the parent onBeforeWrite
        parent::onBeforeWrite();

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
                    $filter = array_keys($parsed);
                    $tags = new DataList('Tag');

                    // compare each tag with the content
                    foreach ($tags as $tag) {

                        // title weighting x3
                        if (stripos(strip_tags($this->owner->Title), $tag->Title) !== false)
                            $words = array_merge($words, array($tag->Title, $tag->Title, $tag->Title));

                        // add the content
                        if (stripos(strip_tags($this->owner->Content), $tag->Title) !== false)
                            $words[] = $tag->Title;

                    }
                }

                // analyse the text
                else {

                    // generate words from content
                    $titlePieces = explode(' ', strip_tags($this->owner->Title));

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
                $tags = implode(', ', $dChecked);

                if ($this->owner->ReGenerateTags) $this->owner->Tags = $tags;
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

        // unset the Regen Params
        $this->owner->ReGenerateTags = null;
        $this->owner->ReGenerateKeywords = null;
    }

}
