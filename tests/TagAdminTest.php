<?php

namespace Azt3k\SS\Taggable\Tests;

use Azt3k\SS\Taggable\Tag;
use Azt3k\SS\Taggable\TagAdmin;
use SilverStripe\Dev\SapphireTest;

class TagAdminTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testManagedModels(): void
    {
        // GIVEN the TagAdmin ModelAdmin configuration
        $models = TagAdmin::config()->get('managed_models');

        // WHEN we check the managed models
        // THEN it should manage the Tag class
        $this->assertContains(Tag::class, $models);
    }

    public function testUrlSegment(): void
    {
        // GIVEN the TagAdmin configuration
        $segment = TagAdmin::config()->get('url_segment');

        // WHEN we check the URL segment
        // THEN it should be 'Tags'
        $this->assertEquals('Tags', $segment);
    }

    public function testMenuTitle(): void
    {
        // GIVEN the TagAdmin configuration
        $title = TagAdmin::config()->get('menu_title');

        // WHEN we check the menu title
        // THEN it should be 'Tags'
        $this->assertEquals('Tags', $title);
    }

    public function testTableName(): void
    {
        // GIVEN the TagAdmin configuration
        $tableName = TagAdmin::config()->get('table_name');

        // WHEN we check the table name
        // THEN it should be 'TagAdmin'
        $this->assertEquals('TagAdmin', $tableName);
    }
}
