<?php

namespace Azt3k\SS\Taggable;

use Azt3k\SS\Classes\AbcPaginator;
use SilverStripe\Model\List\ArrayList;
use PageController;

class TagPageController extends PageController
{
    private static array $allowed_actions = [
        'tag',
    ];

    protected ?string $TagStr = null;

    protected ?ArrayList $TagSet = null;

    protected mixed $Paginator = null;

    public function tag(): array
    {
        $this->TagStr = $this->getRequest()->param('ID');

        $paginator = new AbcPaginator(Taggable::$default_num_page_items);
        $dataSet = Taggable::getTaggedWith(
            $this->TagStr,
            null,
            $paginator->start,
            $paginator->limit
        );

        $this->TagSet = $dataSet;

        // Supply template with pagination data
        $this->Paginator = $paginator->dataForTemplate(Taggable::getLastQueryCount(), 2);

        return [];
    }

    public function getTagStr(): ?string
    {
        return $this->TagStr;
    }

    public function getTagSet(): ?ArrayList
    {
        return $this->TagSet;
    }

    public function getPaginator(): mixed
    {
        return $this->Paginator;
    }
}
