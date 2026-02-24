<?php

namespace Azt3k\SS\Taggable;

use Azt3k\SS\Classes\AbcDB;
use Azt3k\SS\Classes\DataObjectHelper;
use Exception;
use PDO;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\Forms\SelectionGroup_Item;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;

class Taggable extends Extension
{
    private static string $table_name = 'Taggable';

    protected static array $cache = [];

    protected static int $lastQueryCount = 0;

    public static int $default_num_page_items = 10;

    protected static ?string $tags_page_link = null;

    private static array $db = [
        'Tags' => 'Text',
        'MetaKeywords' => 'Text',
        'ReGenerateTags' => 'Boolean',
        'ReGenerateKeywords' => 'Boolean',
        'RestrictToKnownTags' => 'Boolean',
        'TreatHashTagsAsKnownTags' => 'Boolean',
        'BlockScrape' => 'Boolean',
    ];

    private static array $defaults = [
        'ReGenerateTags' => true,
        'ReGenerateKeywords' => true,
        'TreatHashTagsAsKnownTags' => true,
        'RestrictToKnownTags' => false,
    ];

    private static array $indexes = [
        'Tags' => [
            'type' => 'fulltext',
            'columns' => ['Tags'],
        ],
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName('BlockScrape');

        if (get_class($fields->fieldByName('Root.Main')) == TabSet::class) {
            $fields->addFieldsToTab('Root.Main.Meta', $this->getTagFields());
        } elseif (get_class($fields->fieldByName('Root')) == TabSet::class) {
            $fields->addFieldsToTab('Root.Meta', $this->getTagFields());
        } elseif (get_class($fields) == FieldList::class) {
            foreach ($this->getTagFields() as $f) {
                $fields->push($f);
            }
        }
    }

    protected static function get_blacklisted_words(): array
    {
        return [
            'of', 'a', 'the', 'and', 'an', 'or', 'nor',
            'but', 'is', 'if', 'then', 'else', 'when',
            'at', 'from', 'by', 'on', 'off', 'for',
            'in', 'out', 'over', 'to', 'into', 'with',
            'also', 'back', 'well', 'big', 'when', 'where',
            'why', 'who', 'which', 'it', 'be', 'so', 'far',
            'one', 'our', 'we', 'only', 'they', 'this', 'i',
            'do', 'there', 'just', 'that',
        ];
    }

    public static function str_to_tags(string $str): array
    {
        $tags = array_map('trim', explode(',', $str));
        $out = [];
        foreach ($tags as $tag) {
            if (!in_array(strtolower($tag), static::get_blacklisted_words())) {
                $out[] = trim($tag, ',.!?');
            }
        }
        return $out;
    }

    public function getIncludeInDump(): array
    {
        $includeInDump = method_exists($this->owner, 'getIncludeInDump') ? $this->owner->getIncludeInDump() : [];
        $includeInDump = (!empty($includeInDump) && is_array($includeInDump)) ? $includeInDump : [];
        $includeInDump[] = 'TagURLStr';
        $includeInDump = array_unique($includeInDump);
        return $includeInDump;
    }

    protected function getTagFields(): FieldList
    {
        return new FieldList(
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
    }

    public function getExplodedTags(): array
    {
        return static::explode_tags($this->owner->Tags);
    }

    public function setExplodedTags(array|string $tags): void
    {
        $this->owner->Tags = is_array($tags) ? implode(',', array_map('trim', $tags)) : $tags;
    }

    public function getTagURLStr(): ?string
    {
        return $this->owner->Tags
            ? self::tags2Links($this->owner->Tags)
            : null;
    }

    public static function extract_hash_tags(string $str): array
    {
        $hashtags = [];
        preg_match_all('/(#\w+)/u', $str, $matches);
        if ($matches) {
            $hashtagsArray = array_count_values($matches[0]);
            $hashtags = array_keys($hashtagsArray);
        }
        return $hashtags;
    }

    public static function explode_tags(array|string|null $tags): array
    {
        if (is_array($tags)) return $tags;
        return array_map('trim', explode(',', $tags ?? ''));
    }

    protected static function extended_classes(): array
    {
        $key = 'extended_classes';
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getExtendedClasses('Taggable');
        }
        return static::$cache[$key];
    }

    protected static function table_for_class(string $className): ?string
    {
        $key = 'table_for_class' . $className;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getTableForClass($className);
        }
        return static::$cache[$key];
    }

    protected static function extension_table_for_class_with_property(string $className, string $prop): ?string
    {
        $key = 'extension_table_for_class_with_property' . $className . $prop;
        if (empty(static::$cache[$key])) {
            static::$cache[$key] = DataObjectHelper::getExtensionTableForClassWithProperty($className, $prop);
        }
        return static::$cache[$key];
    }

    protected static function all_tags(): DataList
    {
        $tKey = 'full-tag-list';
        if (empty(static::$cache[$tKey])) static::$cache[$tKey] = new DataList(Tag::class);
        return static::$cache[$tKey];
    }

    protected static function all_tag_arr(): array
    {
        $tKey = 'full-tag-list-arr';
        if (empty(static::$cache[$tKey])) {
            $r = [];
            foreach (static::all_tags() as $tag) {
                $r[] = $tag->Title;
            }
            static::$cache[$tKey] = $r;
        }
        return static::$cache[$tKey];
    }

    protected static function safe_args(mixed $arg): string
    {
        if (is_array($arg)) $arg = implode('_', $arg);
        return preg_replace('/[^A-Za-z0-9]/', '_', $arg);
    }

    public static function tagged_with(string $className, array|string $tags, string $where = '', string $lookupMode = 'OR'): DataList
    {
        if ($lookupMode != 'AND' && $lookupMode != 'OR') {
            throw new Exception('Invalid lookupMode supplied');
        }

        $key = preg_replace('/[^A-Za-z0-9]/', '_', __FUNCTION__) .
            implode(
                '_',
                array_map(
                    [static::class, 'safe_args'],
                    func_get_args()
                )
            );

        if (!empty(static::$cache[$key])) {
            return static::$cache[$key];
        }

        if (!is_array($tags)) $tags = static::explode_tags($tags);

        $tWhere = '';
        foreach ($tags as $tag) {
            $cleanTag = preg_replace("/[\(\)\']+/", '', Convert::raw2sql($tag));
            $tWhere .= ($tWhere ? $lookupMode : '') .
                ' Tags REGEXP \'(^|,| )+' . $cleanTag . '($|,| )+\' ';
        }

        $firstWord = explode(' ', strtoupper(trim($where)))[0];
        if ($where && $firstWord != 'AND' && $firstWord != 'OR') $where = 'AND (' . $where . ')';

        $where = '(' . $tWhere . ') ' . $where;

        static::$cache[$key] = DataList::create($className)->where($where);

        return static::$cache[$key];
    }

    public static function getTaggedWith(
        array|string $tags,
        ?string $filterSql = null,
        int $start = 0,
        int $limit = 40,
        string $lookupMode = 'OR'
    ): ArrayList {
        $key = preg_replace('/[^A-Za-z0-9]/', '_', __FUNCTION__) .
            implode(
                '_',
                array_map(
                    [static::class, 'safe_args'],
                    func_get_args()
                )
            );

        // cache hit
        if (!empty(static::$cache[$key])) {
            static::$lastQueryCount = static::$cache[$key . ':count'] ?? 0;
            return static::$cache[$key];
        }

        if (!is_array($tags)) $tags = static::explode_tags($tags);
        if ($lookupMode != 'AND' && $lookupMode != 'OR') throw new Exception('Invalid lookupMode supplied');

        $classes = static::extended_classes();
        $set     = new ArrayList;
        $db      = AbcDB::getInstance();
        $sql     = '';
        $where   = [];
        $tables  = $joins = $filter = [];

        // Build Query Data
        foreach ($classes as $className) {
            $table    = static::table_for_class($className);
            $extTable = static::extension_table_for_class_with_property($className, 'Tags');

            if ($table) $tables[$table] = $table;

            if ($table && $extTable && $table != $extTable) {
                $joins[$table][] = $extTable;
            } elseif ($extTable) {
                $tables[$extTable] = $extTable;
            }

            if ($table) $where[$table][] = "LOWER(" . $table . ".ClassName) = '" . strtolower($className) . "'";

            if ($extTable) {
                foreach ($tags as $tag) {
                    $cleanTag = preg_replace("/[\(\)\']+/", '', Convert::raw2sql($tag));
                    $filter[$table][] = $extTable . ".Tags REGEXP '(^|,| )+" . $cleanTag . "($|,| )+'";
                }
            }
        }

        // Build Query
        foreach ($tables as $table) {
            if (array_key_exists($table, $joins)) {
                $uWhere  = array_unique($where[$table]);
                $uFilter = array_unique($filter[$table]);

                $wSql = "(" . implode(' OR ', $uWhere) . ") AND (" . implode(' ' . $lookupMode . ' ', $uFilter) . ")";

                if ($sql) $sql .= "UNION ALL" . "\n\n";
                $sql .= "SELECT " . $table . ".ClassName, " . $table . ".ID" . "\n";
                $sql .= "FROM " . $table . "\n";

                $join = array_unique($joins[$table]);
                foreach ($join as $j) {
                    $sql .= " LEFT JOIN " . $j . " ON " . $table . ".ID = " . $j . ".ID" . "\n";
                }

                $sql .= "WHERE " . $wSql . "\n\n";
            }
        }

        if ($filterSql) {
            $sql .= (count($tables) == 1 ? "AND " : "WHERE ") . $filterSql;
        }

        // Count query (replaces deprecated SQL_CALC_FOUND_ROWS)
        $countSql = "SELECT COUNT(*) AS total FROM (" . $sql . ") AS subquery";
        $countResult = $db->query($countSql);
        $unlimitedRowCount = $countResult ? (int) $countResult->fetch(PDO::FETCH_OBJ)->total : 0;

        $sql .= " LIMIT " . $start . "," . $limit;

        $result = $db->query($sql);
        $result = $result ? $result->fetchAll(PDO::FETCH_OBJ) : [];

        foreach ($result as $entry) {
            $entry = (object) $entry;
            $dO = DataObject::get_by_id($entry->ClassName, $entry->ID);
            $set->push($dO);
        }

        static::$lastQueryCount = $unlimitedRowCount;
        static::$cache[$key . ':count'] = $unlimitedRowCount;
        static::$cache[$key] = $set;

        return $set;
    }

    public static function getLastQueryCount(): int
    {
        return static::$lastQueryCount;
    }

    public static function tags2Links(string $strTags): string
    {
        if (!$tagsPageURL = self::getTagPageLink()) {
            throw new Exception('There is no page of type TagPage in the site tree');
        }

        $outputTags = explode(',', $strTags);
        $tempTags = [];

        foreach ($outputTags as $oTags) {
            $tempTags[] = "<a href='" . $tagsPageURL . "tag/" . trim($oTags) . "'>" . trim($oTags) . "</a>";
        }

        return implode(', ', $tempTags);
    }

    public static function getTagPageLink(): string|false
    {
        if (!self::$tags_page_link) {
            if (!$tagsPage = DataObject::get_one(TagPage::class)) return false;
            self::$tags_page_link = $tagsPage->Link();
        }
        return self::$tags_page_link;
    }

    public function getAssociatedLink(): string|false
    {
        if (method_exists($this->owner, 'Link')) return $this->owner->Link();
        return false;
    }

    public function getAssociatedImage(): mixed
    {
        if (method_exists($this->owner, 'getAssociatedImage')) return $this->owner->getAssociatedImage();
        if (method_exists($this->owner, 'getAddImage')) return $this->owner->getAddImage();
        if (method_exists($this->owner, 'Image')) return $this->owner->Image();
        return false;
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if ($this->owner->BlockScrape) return;

        if (
            !$this->owner->Tags ||
            $this->owner->ReGenerateTags ||
            $this->owner->ReGenerateKeywords
        ) {
            if (
                !empty($this->owner->MetaKeywords) &&
                !$this->owner->ReGenerateTags &&
                !$this->owner->ReGenerateKeywords
            ) {
                $this->owner->Tags = $this->owner->MetaKeywords;
            } else {
                $exclude = static::get_blacklisted_words();
                $words = $parsed = [];

                if ($this->owner->RestrictToKnownTags) {
                    $tags = static::all_tag_arr();
                    foreach ($tags as $tag) {
                        if (stripos((string) strip_tags((string) $this->owner->Title), (string) $tag) !== false) {
                            $words = array_merge($words, [$tag, $tag, $tag]);
                        }
                        if (stripos((string) strip_tags((string) $this->owner->Content), (string) $tag) !== false) {
                            $words[] = $tag;
                        }
                    }
                } else {
                    $titlePieces = explode(' ', strip_tags((string) $this->owner->Title));
                    if (!empty($this->owner->Title)) {
                        $words = array_merge($words, $titlePieces, $titlePieces, $titlePieces);
                    }
                    if (!empty($this->owner->Content)) {
                        $words = array_merge($words, explode(' ', strip_tags($this->owner->Content)));
                    }
                }

                foreach ($words as $word) {
                    $word = strtolower(trim(html_entity_decode(strval($word))));
                    $word = trim($word, ',.!');
                    if ($word && !in_array(strtolower($word), $exclude) && !str_starts_with($word, '&') && strlen($word) > 3) {
                        $parsed[$word] = !empty($parsed[$word]) ? ($parsed[$word] + 1) : 1;
                    }
                }

                arsort($parsed);
                $sample = array_keys(array_slice($parsed, 0, 15));

                $dChecked = [];
                foreach ($sample as $value) {
                    $value = strval($value);
                    if (!empty($value) && strlen($value) > 3) $dChecked[] = $value;
                }

                if (
                    !$this->owner->RestrictToKnownTags ||
                    ($this->owner->RestrictToKnownTags && $this->owner->TreatHashTagsAsKnownTags)
                ) {
                    $dChecked = array_merge(
                        $dChecked,
                        static::extract_hash_tags($this->owner->Title . ' ' . $this->owner->Content)
                    );
                }

                $tags = implode(', ', $dChecked);

                if ($this->owner->ReGenerateTags || !$this->owner->Tags) $this->owner->Tags = $tags;
                if ($this->owner->ReGenerateKeywords) $this->owner->MetaKeywords = $tags;
            }
        }

        if (!$this->owner->MetaKeywords) {
            if ($this->owner->Tags) $this->owner->MetaKeywords = $this->owner->Tags;
        }

        $this->owner->Tags = strtolower($this->owner->Tags ?? '');
        $this->owner->MetaKeywords = strtolower($this->owner->MetaKeywords ?? '');
    }
}
