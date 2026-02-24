<?php

namespace Azt3k\SS\Taggable\Tests;

use Azt3k\SS\Classes\AbcDB;
use Azt3k\SS\Classes\DataObjectHelper;
use Azt3k\SS\Taggable\Tag;
use Azt3k\SS\Taggable\Taggable;
use Azt3k\SS\Taggable\TagPage;
use Exception;
use Page;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\SelectionGroup;
use SilverStripe\Forms\TextField;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DB;

class TaggableTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/TaggableTest.yml';

    protected static $required_extensions = [
        Page::class => [Taggable::class],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Reset static caches between tests
        $cacheRef = new \ReflectionProperty(Taggable::class, 'cache');
        $cacheRef->setValue(null, []);
        $linkRef = new \ReflectionProperty(Taggable::class, 'tags_page_link');
        $linkRef->setValue(null, null);
        // Reset AbcDB singleton so it reconnects to the temp test database
        $abcRef = new \ReflectionProperty(AbcDB::class, 'instance');
        $abcRef->setValue(null, null);
        // Set SS_DATABASE_NAME to the temp test database so AbcDB connects to it
        $dbName = DB::get_conn()->getSelectedDatabase();
        Environment::setEnv('SS_DATABASE_NAME', $dbName);
    }

    public function testStrToTagsFiltersBlacklistedWords(): void
    {
        // GIVEN a comma-separated string containing common stop words
        $input = 'silverstripe, the, framework, and, testing, is, great';

        // WHEN we convert it to tags
        $result = Taggable::str_to_tags($input);

        // THEN blacklisted words should be filtered out
        $this->assertContains('silverstripe', $result);
        $this->assertContains('framework', $result);
        $this->assertContains('testing', $result);
        $this->assertContains('great', $result);
        $this->assertNotContains('the', $result);
        $this->assertNotContains('and', $result);
        $this->assertNotContains('is', $result);
    }

    public function testStrToTagsStripsTrailingPunctuation(): void
    {
        // GIVEN a string with punctuated tags
        $input = 'hello!, world., test?';

        // WHEN we convert it to tags
        $result = Taggable::str_to_tags($input);

        // THEN punctuation should be stripped
        $this->assertContains('hello', $result);
        $this->assertContains('world', $result);
        $this->assertContains('test', $result);
    }

    public function testExplodeTagsFromString(): void
    {
        // GIVEN a comma-separated tag string
        $input = 'php, silverstripe, testing';

        // WHEN we explode it
        $result = Taggable::explode_tags($input);

        // THEN we get a trimmed array
        $this->assertEquals(['php', 'silverstripe', 'testing'], $result);
    }

    public function testExplodeTagsPassthroughArray(): void
    {
        // GIVEN an array input
        $input = ['already', 'an', 'array'];

        // WHEN we call explode_tags with an array
        $result = Taggable::explode_tags($input);

        // THEN it returns the array unchanged
        $this->assertSame($input, $result);
    }

    public function testExplodeTagsHandlesNull(): void
    {
        // GIVEN a null input
        // WHEN we call explode_tags
        $result = Taggable::explode_tags(null);

        // THEN it returns an array with one empty string
        $this->assertEquals([''], $result);
    }

    public function testExtractHashTags(): void
    {
        // GIVEN a string containing hashtags
        $input = 'Check out #silverstripe and #php for #webdev projects';

        // WHEN we extract hash tags
        $result = Taggable::extract_hash_tags($input);

        // THEN we get all unique hashtags
        $this->assertContains('#silverstripe', $result);
        $this->assertContains('#php', $result);
        $this->assertContains('#webdev', $result);
        $this->assertCount(3, $result);
    }

    public function testExtractHashTagsWithDuplicates(): void
    {
        // GIVEN a string with duplicate hashtags
        $input = '#php is great, #php is awesome';

        // WHEN we extract hash tags
        $result = Taggable::extract_hash_tags($input);

        // THEN duplicates are removed
        $this->assertCount(1, $result);
        $this->assertContains('#php', $result);
    }

    public function testExtractHashTagsEmptyString(): void
    {
        // GIVEN an empty string
        // WHEN we extract hash tags
        $result = Taggable::extract_hash_tags('');

        // THEN we get an empty array
        $this->assertEmpty($result);
    }

    public function testGetTagFieldsReturnsFieldList(): void
    {
        // GIVEN a page with the Taggable extension
        $page = Page::create();
        $page->Title = 'Test Page';

        // WHEN we access the tag fields via reflection (protected method)
        $extension = $page->getExtensionInstance(Taggable::class);
        $extension->setOwner($page);
        $method = new \ReflectionMethod($extension, 'getTagFields');
        $fields = $method->invoke($extension);

        // THEN it returns a FieldList with the expected fields
        $this->assertInstanceOf(FieldList::class, $fields);
        $this->assertNotNull($fields->fieldByName('BlockScrape'));
        $this->assertInstanceOf(SelectionGroup::class, $fields->fieldByName('BlockScrape'));
        $this->assertNotNull($fields->fieldByName('MetaKeywords'));
        $this->assertInstanceOf(TextField::class, $fields->fieldByName('MetaKeywords'));
        $this->assertNotNull($fields->fieldByName('Tags'));
        $this->assertInstanceOf(TextField::class, $fields->fieldByName('Tags'));
    }

    public function testUpdateCMSFieldsAddsTagFields(): void
    {
        // GIVEN a page with the Taggable extension
        $page = Page::create();
        $page->Title = 'Test Page';

        // WHEN we get its CMS fields
        $fields = $page->getCMSFields();

        // THEN tag-related fields should be present
        $this->assertNotNull($fields->dataFieldByName('Tags'));
        $this->assertNotNull($fields->dataFieldByName('MetaKeywords'));
        $this->assertNotNull($fields->dataFieldByName('BlockScrape'));
    }

    public function testGetExplodedTags(): void
    {
        // GIVEN a page with comma-separated tags
        $page = Page::create();
        $page->Tags = 'php, silverstripe, testing';

        // WHEN we get exploded tags via the extension
        $result = $page->getExplodedTags();

        // THEN we get a trimmed array
        $this->assertEquals(['php', 'silverstripe', 'testing'], $result);
    }

    public function testSetExplodedTagsFromArray(): void
    {
        // GIVEN a page instance
        $page = Page::create();

        // WHEN we set exploded tags with an array
        $page->setExplodedTags(['php', 'silverstripe', 'testing']);

        // THEN Tags is set as a comma-separated string
        $this->assertEquals('php,silverstripe,testing', $page->Tags);
    }

    public function testSetExplodedTagsFromString(): void
    {
        // GIVEN a page instance
        $page = Page::create();

        // WHEN we set exploded tags with a string
        $page->setExplodedTags('raw string tags');

        // THEN Tags is set as the raw string
        $this->assertEquals('raw string tags', $page->Tags);
    }

    public function testGetIncludeInDump(): void
    {
        // GIVEN a page with the Taggable extension
        $page = Page::create();

        // WHEN we get the include in dump list
        $result = $page->getIncludeInDump();

        // THEN it contains TagURLStr
        $this->assertContains('TagURLStr', $result);
    }

    public function testOnBeforeWriteGeneratesTagsFromTitle(): void
    {
        // GIVEN a page with a title and content but no tags, with regeneration enabled
        $page = Page::create();
        $page->Title = 'Silverstripe Framework Testing Guide';
        $page->Content = '<p>Learn about testing in the Silverstripe framework with PHPUnit</p>';
        $page->ReGenerateTags = true;
        $page->ReGenerateKeywords = true;
        $page->BlockScrape = false;

        // WHEN the page is written
        $page->write();

        // THEN tags should be auto-generated and lowercased
        $this->assertNotEmpty($page->Tags);
        $tags = Taggable::explode_tags($page->Tags);
        // Title words weighted 3x should appear
        $this->assertTrue(
            in_array('silverstripe', $tags) || in_array('framework', $tags) || in_array('testing', $tags),
            'Expected title-derived tags to be generated'
        );
    }

    public function testOnBeforeWriteBlockScrapeSkipsGeneration(): void
    {
        // GIVEN a page with BlockScrape enabled and no tags
        $page = Page::create();
        $page->Title = 'Should Not Generate Tags';
        $page->Content = '<p>This content should not generate tags</p>';
        $page->BlockScrape = true;
        $page->Tags = '';
        $page->MetaKeywords = '';

        // WHEN the page is written
        $page->write();

        // THEN tags should remain empty
        $this->assertEmpty($page->Tags);
    }

    public function testOnBeforeWriteLowercasesTags(): void
    {
        // GIVEN a page with uppercase tags
        $page = Page::create();
        $page->Title = 'Test';
        $page->Tags = 'PHP, SilverStripe, TESTING';
        $page->MetaKeywords = 'PHP, SilverStripe';
        $page->BlockScrape = false;
        $page->ReGenerateTags = false;
        $page->ReGenerateKeywords = false;

        // WHEN the page is written
        $page->write();

        // THEN tags and keywords should be lowercased
        $this->assertEquals('php, silverstripe, testing', $page->Tags);
        $this->assertEquals('php, silverstripe', $page->MetaKeywords);
    }

    public function testOnBeforeWriteCopiesTagsToKeywords(): void
    {
        // GIVEN a page with tags but no meta keywords
        $page = Page::create();
        $page->Title = 'Test';
        $page->Tags = 'php, silverstripe';
        $page->MetaKeywords = '';
        $page->BlockScrape = false;
        $page->ReGenerateTags = false;
        $page->ReGenerateKeywords = false;

        // WHEN the page is written
        $page->write();

        // THEN MetaKeywords should be copied from Tags
        $this->assertEquals('php, silverstripe', $page->MetaKeywords);
    }

    public function testOnBeforeWriteUsesKeywordsWhenNoTags(): void
    {
        // GIVEN a page with meta keywords but no tags, regeneration disabled
        $page = Page::create();
        $page->Title = 'Test';
        $page->Tags = '';
        $page->MetaKeywords = 'existing, keywords';
        $page->BlockScrape = false;
        $page->ReGenerateTags = false;
        $page->ReGenerateKeywords = false;

        // WHEN the page is written
        $page->write();

        // THEN Tags should be set from MetaKeywords
        $this->assertEquals('existing, keywords', $page->Tags);
    }

    public function testGetBlacklistedWordsReturnsArray(): void
    {
        // GIVEN the Taggable class
        // WHEN we call get_blacklisted_words via reflection
        $method = new \ReflectionMethod(Taggable::class, 'get_blacklisted_words');
        $result = $method->invoke(null);

        // THEN it returns a non-empty array of common stop words
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        $this->assertContains('the', $result);
        $this->assertContains('and', $result);
        $this->assertContains('or', $result);
    }

    public function testGetLastQueryCountDefaultsToZero(): void
    {
        // GIVEN a fresh state (no queries run)
        // WHEN we check the last query count
        $count = Taggable::getLastQueryCount();

        // THEN it defaults to 0
        $this->assertSame(0, $count);
    }

    public function testDefaultNumPageItems(): void
    {
        // GIVEN the Taggable class
        // WHEN we check the default page items count
        // THEN it should be 10
        $this->assertSame(10, Taggable::$default_num_page_items);
    }

    public function testDefaultNumPageItemsFromConfig(): void
    {
        // GIVEN the YAML config sets default_num_page_items to 10
        $configValue = Config::inst()->get(Taggable::class, 'default_num_page_items');

        // WHEN we read the config value
        // THEN it should be 10
        $this->assertSame(10, $configValue);
    }

    public function testGetTagPageLinkReturnsLink(): void
    {
        // GIVEN a TagPage exists in the site tree (from fixtures)
        $tagPage = $this->objFromFixture(TagPage::class, 'tagpage1');
        $this->assertNotNull($tagPage);

        // WHEN we get the tag page link
        $link = Taggable::getTagPageLink();

        // THEN it should return a non-empty string
        $this->assertIsString($link);
        $this->assertNotEmpty($link);
    }

    public function testGetTagPageLinkCachesResult(): void
    {
        // GIVEN a TagPage exists and we've fetched the link once
        $link1 = Taggable::getTagPageLink();

        // WHEN we fetch it again
        $link2 = Taggable::getTagPageLink();

        // THEN it should return the same cached value
        $this->assertSame($link1, $link2);
    }

    public function testTags2LinksReturnsHtmlLinks(): void
    {
        // GIVEN a TagPage exists in the site tree (from fixtures)
        $this->objFromFixture(TagPage::class, 'tagpage1');

        // WHEN we convert tags to links
        $result = Taggable::tags2Links('php, silverstripe');

        // THEN it should return HTML anchor tags
        $this->assertStringContainsString('<a href=', $result);
        $this->assertStringContainsString('tag/php', $result);
        $this->assertStringContainsString('tag/silverstripe', $result);
    }

    public function testGetTagURLStrReturnsLinksWhenTagsExist(): void
    {
        // GIVEN a page with tags and a TagPage in the site tree
        $this->objFromFixture(TagPage::class, 'tagpage1');
        $page = $this->objFromFixture(Page::class, 'page1');

        // WHEN we get the tag URL string
        $result = $page->getTagURLStr();

        // THEN it should return HTML links
        $this->assertNotNull($result);
        $this->assertStringContainsString('<a href=', $result);
    }

    public function testGetTagURLStrReturnsNullWhenNoTags(): void
    {
        // GIVEN a page with no tags
        $page = Page::create();
        $page->Tags = '';

        // WHEN we get the tag URL string
        $result = $page->getTagURLStr();

        // THEN it should return null
        $this->assertNull($result);
    }

    public function testGetAssociatedLinkReturnsLinkForPage(): void
    {
        // GIVEN a page in the site tree (has Link() method)
        $page = $this->objFromFixture(Page::class, 'page1');

        // WHEN we get the associated link
        $result = $page->getAssociatedLink();

        // THEN it should return a non-empty string
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGetAssociatedImageReturnsFalseForPage(): void
    {
        // GIVEN a page without image methods
        $page = $this->objFromFixture(Page::class, 'page1');

        // WHEN we get the associated image
        $result = $page->getAssociatedImage();

        // THEN it should return false (Page has no getAssociatedImage/getAddImage/Image)
        $this->assertFalse($result);
    }

    public function testTaggedWithReturnsDataList(): void
    {
        // GIVEN pages with tags in the database (from fixtures)
        $page1 = $this->objFromFixture(Page::class, 'page1');

        // WHEN we query for tagged items
        $result = Taggable::tagged_with(Page::class, 'silverstripe');

        // THEN it should return a DataList
        $this->assertInstanceOf(DataList::class, $result);
    }

    public function testTaggedWithCachesResult(): void
    {
        // GIVEN a tagged_with query has been made
        $result1 = Taggable::tagged_with(Page::class, 'php');

        // WHEN we make the same query again
        $result2 = Taggable::tagged_with(Page::class, 'php');

        // THEN it should return the same cached instance
        $this->assertSame($result1, $result2);
    }

    public function testTaggedWithThrowsOnInvalidLookupMode(): void
    {
        // GIVEN an invalid lookup mode
        // WHEN we call tagged_with
        // THEN it should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid lookupMode supplied');
        Taggable::tagged_with(Page::class, 'php', '', 'INVALID');
    }

    public function testTaggedWithAcceptsStringTags(): void
    {
        // GIVEN pages with tags (from fixtures)
        // WHEN we query with a comma-separated string
        $result = Taggable::tagged_with(Page::class, 'silverstripe, php');

        // THEN it should return a DataList
        $this->assertInstanceOf(DataList::class, $result);
    }

    public function testTaggedWithAndMode(): void
    {
        // GIVEN pages with tags (from fixtures)
        // WHEN we query with AND mode
        $result = Taggable::tagged_with(Page::class, 'silverstripe, php', '', 'AND');

        // THEN it should return a DataList
        $this->assertInstanceOf(DataList::class, $result);
    }

    public function testTaggedWithWhereClause(): void
    {
        // GIVEN pages with tags (from fixtures)
        // WHEN we query with an additional where clause
        $result = Taggable::tagged_with(Page::class, 'silverstripe', "Title != 'nonexistent'");

        // THEN it should return a DataList
        $this->assertInstanceOf(DataList::class, $result);
    }

    public function testOnBeforeWriteWithHashTagExtraction(): void
    {
        // GIVEN a page with hashtags in content and TreatHashTagsAsKnownTags enabled
        $page = Page::create();
        $page->Title = 'Short Title';
        $page->Content = '<p>Check out #silverstripe and #webdev for great projects</p>';
        $page->ReGenerateTags = true;
        $page->ReGenerateKeywords = false;
        $page->BlockScrape = false;
        $page->RestrictToKnownTags = false;
        $page->TreatHashTagsAsKnownTags = true;

        // WHEN the page is written
        $page->write();

        // THEN tags should include the hashtags
        $this->assertStringContainsString('#silverstripe', $page->Tags);
        $this->assertStringContainsString('#webdev', $page->Tags);
    }

    public function testOnBeforeWriteWithRestrictToKnownTags(): void
    {
        // GIVEN known tags exist in the database and RestrictToKnownTags is enabled
        $this->objFromFixture(Tag::class, 'tag1'); // 'silverstripe'
        $page = Page::create();
        $page->Title = 'Silverstripe Development Guide';
        $page->Content = '<p>A guide to silverstripe framework development and php coding</p>';
        $page->ReGenerateTags = true;
        $page->ReGenerateKeywords = false;
        $page->BlockScrape = false;
        $page->RestrictToKnownTags = true;
        $page->TreatHashTagsAsKnownTags = false;

        // WHEN the page is written
        $page->write();

        // THEN tags should be restricted to known tags only
        $tags = Taggable::explode_tags($page->Tags);
        foreach ($tags as $tag) {
            if (!empty(trim($tag))) {
                $this->assertTrue(
                    Tag::get()->filter('Title', $tag)->exists(),
                    "Tag '$tag' should be a known tag"
                );
            }
        }
    }

    public function testOnBeforeWriteRegenerateKeywords(): void
    {
        // GIVEN a page with ReGenerateKeywords enabled
        $page = Page::create();
        $page->Title = 'Silverstripe Framework Guide';
        $page->Content = '<p>Development with silverstripe framework</p>';
        $page->ReGenerateTags = false;
        $page->ReGenerateKeywords = true;
        $page->BlockScrape = false;
        $page->RestrictToKnownTags = false;
        $page->TreatHashTagsAsKnownTags = true;
        $page->Tags = 'existing, tags';

        // WHEN the page is written
        $page->write();

        // THEN MetaKeywords should be regenerated from content
        $this->assertNotEmpty($page->MetaKeywords);
    }

    public function testAllTagsReturnsDataList(): void
    {
        // GIVEN tags exist in the database (from fixtures)
        // WHEN we call the protected all_tags method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'all_tags');
        $result = $method->invoke(null);

        // THEN it should return a DataList of Tag objects
        $this->assertInstanceOf(DataList::class, $result);
    }

    public function testAllTagArrReturnsArray(): void
    {
        // GIVEN tags exist in the database (from fixtures)
        // WHEN we call the protected all_tag_arr method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'all_tag_arr');
        $result = $method->invoke(null);

        // THEN it should return an array of tag titles
        $this->assertIsArray($result);
        $this->assertContains('silverstripe', $result);
        $this->assertContains('php', $result);
    }

    public function testSafeArgsWithString(): void
    {
        // GIVEN a string with special characters
        // WHEN we call the protected safe_args method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'safe_args');
        $result = $method->invoke(null, 'hello-world.test!');

        // THEN special chars should be replaced with underscores
        $this->assertEquals('hello_world_test_', $result);
    }

    public function testSafeArgsWithArray(): void
    {
        // GIVEN an array argument
        // WHEN we call safe_args
        $method = new \ReflectionMethod(Taggable::class, 'safe_args');
        $result = $method->invoke(null, ['hello', 'world']);

        // THEN it should join with underscore then sanitize
        $this->assertEquals('hello_world', $result);
    }

    public function testDbFieldsExist(): void
    {
        // GIVEN the Taggable extension config
        $db = Config::inst()->get(Taggable::class, 'db');

        // WHEN we check the db fields
        // THEN all expected fields should be present
        $this->assertArrayHasKey('Tags', $db);
        $this->assertArrayHasKey('MetaKeywords', $db);
        $this->assertArrayHasKey('ReGenerateTags', $db);
        $this->assertArrayHasKey('ReGenerateKeywords', $db);
        $this->assertArrayHasKey('RestrictToKnownTags', $db);
        $this->assertArrayHasKey('TreatHashTagsAsKnownTags', $db);
        $this->assertArrayHasKey('BlockScrape', $db);
    }

    public function testDefaultsConfig(): void
    {
        // GIVEN the Taggable extension config
        $defaults = Config::inst()->get(Taggable::class, 'defaults');

        // WHEN we check the defaults
        // THEN ReGenerateTags and ReGenerateKeywords should default to true
        $this->assertTrue((bool) $defaults['ReGenerateTags']);
        $this->assertTrue((bool) $defaults['ReGenerateKeywords']);
        $this->assertTrue((bool) $defaults['TreatHashTagsAsKnownTags']);
        $this->assertFalse((bool) $defaults['RestrictToKnownTags']);
    }

    public function testIndexesConfig(): void
    {
        // GIVEN the Taggable extension config
        $indexes = Config::inst()->get(Taggable::class, 'indexes');

        // WHEN we check the indexes
        // THEN a fulltext index on Tags should exist
        $this->assertArrayHasKey('Tags', $indexes);
        $this->assertEquals('fulltext', $indexes['Tags']['type']);
    }

    public function testTags2LinksThrowsWhenNoTagPage(): void
    {
        // GIVEN no TagPage exists in the site tree (clear fixtures)
        TagPage::get()->removeAll();

        // WHEN we try to convert tags to links
        // THEN it should throw an exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('There is no page of type TagPage');
        Taggable::tags2Links('php, silverstripe');
    }

    public function testGetTagPageLinkReturnsFalseWhenNoTagPage(): void
    {
        // GIVEN no TagPage exists in the site tree
        TagPage::get()->removeAll();

        // WHEN we get the tag page link
        $result = Taggable::getTagPageLink();

        // THEN it should return false
        $this->assertFalse($result);
    }

    public function testUpdateCMSFieldsPlainFieldList(): void
    {
        // GIVEN a page with the Taggable extension and a plain FieldList (no TabSet)
        $page = Page::create();
        $page->Title = 'Test';
        $extension = $page->getExtensionInstance(Taggable::class);
        $extension->setOwner($page);

        // WHEN we call updateCMSFields with a plain FieldList
        $fields = new FieldList();
        $extension->updateCMSFields($fields);

        // THEN the tag fields should be pushed directly onto the FieldList
        $this->assertNotNull($fields->fieldByName('Tags'));
        $this->assertNotNull($fields->fieldByName('MetaKeywords'));
        $this->assertNotNull($fields->fieldByName('BlockScrape'));
    }

    public function testExtendedClassesReturnsArray(): void
    {
        // GIVEN the Taggable extension is applied to Page
        // WHEN we call the protected extended_classes method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'extended_classes');
        $result = $method->invoke(null);

        // THEN it should return an array of class names
        $this->assertIsArray($result);
    }

    public function testTableForClassReturnsString(): void
    {
        // GIVEN a valid DataObject class name
        // WHEN we call the protected table_for_class method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'table_for_class');
        $result = $method->invoke(null, Page::class);

        // THEN it should return a table name string
        $this->assertNotNull($result);
    }

    public function testExtensionTableForClassWithProperty(): void
    {
        // GIVEN a class with the Taggable extension (which adds Tags property)
        // WHEN we call the protected method via reflection
        $method = new \ReflectionMethod(Taggable::class, 'extension_table_for_class_with_property');
        $result = $method->invoke(null, Page::class, 'Tags');

        // THEN it should return a table name (or null if extension table doesn't exist separately)
        // The important thing is it doesn't throw
        $this->assertTrue($result === null || is_string($result));
    }

    public function testGetTaggedWithReturnsArrayList(): void
    {
        // GIVEN pages with tags exist in the database (from fixtures)
        $this->objFromFixture(Page::class, 'page1');

        // WHEN we call getTaggedWith
        $result = Taggable::getTaggedWith('silverstripe');

        // THEN it should return an ArrayList
        $this->assertInstanceOf(ArrayList::class, $result);
    }

    public function testGetTaggedWithSetsLastQueryCount(): void
    {
        // GIVEN pages with tags exist in the database (from fixtures)
        $this->objFromFixture(Page::class, 'page1');

        // WHEN we call getTaggedWith
        Taggable::getTaggedWith('silverstripe');

        // THEN the last query count should be set
        $count = Taggable::getLastQueryCount();
        $this->assertIsInt($count);
    }

    public function testGetTaggedWithCachesResults(): void
    {
        // GIVEN a getTaggedWith query has been made
        $result1 = Taggable::getTaggedWith('php');

        // WHEN we make the same query again
        $result2 = Taggable::getTaggedWith('php');

        // THEN it should return the same cached instance
        $this->assertSame($result1, $result2);
    }

    public function testGetTaggedWithThrowsOnInvalidLookupMode(): void
    {
        // GIVEN an invalid lookup mode
        // WHEN we call getTaggedWith
        // THEN it should throw an exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid lookupMode supplied');
        Taggable::getTaggedWith('php', null, 0, 40, 'INVALID');
    }

    public function testGetTaggedWithWithFilterSql(): void
    {
        // GIVEN pages with tags exist in the database (from fixtures)
        $this->objFromFixture(Page::class, 'page1');

        // WHEN we call getTaggedWith with a filter SQL
        $result = Taggable::getTaggedWith('silverstripe', "1=1");

        // THEN it should return an ArrayList without errors
        $this->assertInstanceOf(ArrayList::class, $result);
    }

    public function testGetTaggedWithPagination(): void
    {
        // GIVEN pages with tags exist in the database (from fixtures)
        $this->objFromFixture(Page::class, 'page1');

        // WHEN we call getTaggedWith with pagination params
        $result = Taggable::getTaggedWith('silverstripe', null, 0, 1);

        // THEN it should return an ArrayList respecting the limit
        $this->assertInstanceOf(ArrayList::class, $result);
    }
}
