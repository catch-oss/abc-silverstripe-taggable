<?php

namespace Azt3k\SS\Taggable\Tests;

use Azt3k\SS\Taggable\Tag;
use SilverStripe\Dev\SapphireTest;

class TagTest extends SapphireTest
{
    protected static $fixture_file = 'fixtures/TaggableTest.yml';

    public function testTagHasTitleField(): void
    {
        // GIVEN the Tag DataObject schema
        $dbFields = Tag::config()->get('db');

        // WHEN we check for the Title field
        // THEN it should exist as Varchar(255)
        $this->assertArrayHasKey('Title', $dbFields);
        $this->assertEquals('Varchar(255)', $dbFields['Title']);
    }

    public function testTagHasUniqueIndex(): void
    {
        // GIVEN the Tag DataObject schema
        $indexes = Tag::config()->get('indexes');

        // WHEN we check for the Title index
        // THEN it should exist
        $this->assertArrayHasKey('Title', $indexes);
    }

    public function testTagTableName(): void
    {
        // GIVEN the Tag DataObject
        // WHEN we check its table name
        // THEN it should be 'Tag'
        $this->assertEquals('Tag', Tag::config()->get('table_name'));
    }

    public function testCreateTag(): void
    {
        // GIVEN a new Tag instance
        $tag = Tag::create();
        $tag->Title = 'newtag';

        // WHEN we write it
        $tag->write();

        // THEN it should be persisted and retrievable
        $fetched = Tag::get()->filter('Title', 'newtag')->first();
        $this->assertNotNull($fetched);
        $this->assertEquals('newtag', $fetched->Title);
    }

    public function testLoadTagFromFixture(): void
    {
        // GIVEN the fixture tag 'tag1'
        $tag = $this->objFromFixture(Tag::class, 'tag1');

        // WHEN we read its Title
        // THEN it should match the fixture
        $this->assertEquals('silverstripe', $tag->Title);
    }
}
