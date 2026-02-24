<?php

namespace Azt3k\SS\Taggable\Tests;

use Azt3k\SS\Taggable\TagField;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\TextField;

class TagFieldTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testExtendsTextField(): void
    {
        // GIVEN a TagField instance
        $field = TagField::create('TestTags');

        // WHEN we check its class hierarchy
        // THEN it should extend TextField
        $this->assertInstanceOf(TextField::class, $field);
    }

    public function testHasTextExtraClass(): void
    {
        // GIVEN a new TagField
        $field = TagField::create('TestTags');

        // WHEN we check its extra classes
        // THEN it should have the 'text' class
        $this->assertStringContainsString('text', $field->extraClass());
    }

    public function testTableName(): void
    {
        // GIVEN the TagField configuration
        $tableName = TagField::config()->get('table_name');

        // WHEN we check the table name
        // THEN it should be 'TagField'
        $this->assertEquals('TagField', $tableName);
    }

    public function testFieldAcceptsValue(): void
    {
        // GIVEN a TagField with a value
        $field = TagField::create('TestTags', 'Tags', 'php, silverstripe');

        // WHEN we check its value
        // THEN it should contain the set value
        $this->assertEquals('php, silverstripe', $field->getValue());
    }
}
