<?php

namespace Azt3k\SS\Taggable\Tests;

use Azt3k\SS\Taggable\TagPage;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\FieldList;

class TagPageTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testTableName(): void
    {
        // GIVEN the TagPage configuration
        $tableName = TagPage::config()->get('table_name');

        // WHEN we check the table name
        // THEN it should be 'TagPage'
        $this->assertEquals('TagPage', $tableName);
    }

    public function testAllowedChildren(): void
    {
        // GIVEN the TagPage configuration
        $children = TagPage::config()->get('allowed_children');

        // WHEN we check allowed children
        // THEN it should be 'none'
        $this->assertEquals('none', $children);
    }

    public function testGetCMSFieldsRemovesContent(): void
    {
        // GIVEN a TagPage instance
        $page = TagPage::create();
        $page->Title = 'Tags';

        // WHEN we get its CMS fields
        $fields = $page->getCMSFields();

        // THEN it should return a FieldList
        $this->assertInstanceOf(FieldList::class, $fields);

        // AND the Content field should be removed from Root.Main
        $contentField = $fields->dataFieldByName('Content');
        $this->assertNull($contentField, 'Content field should be removed from TagPage CMS fields');
    }

    public function testCreateTagPage(): void
    {
        // GIVEN a new TagPage instance
        $page = TagPage::create();
        $page->Title = 'All Tags';

        // WHEN we write it
        $page->write();

        // THEN it should be persisted
        $fetched = TagPage::get()->filter('Title', 'All Tags')->first();
        $this->assertNotNull($fetched);
        $this->assertInstanceOf(TagPage::class, $fetched);
    }

    public function testIconIsSet(): void
    {
        // GIVEN the TagPage configuration
        $icon = TagPage::config()->get('icon');

        // WHEN we check the icon
        // THEN it should be set to the taggable module icon
        $this->assertNotEmpty($icon);
        $this->assertStringContainsString('tags-page', $icon);
    }
}
