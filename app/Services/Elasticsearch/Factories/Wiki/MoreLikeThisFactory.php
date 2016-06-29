<?php

namespace Coyote\Services\Elasticsearch\Factories\Wiki;

use Coyote\Services\Elasticsearch\Filters\NotTerm;
use Coyote\Services\Elasticsearch\MoreLikeThis;
use Coyote\Services\Elasticsearch\QueryBuilder;
use Coyote\Services\Elasticsearch\QueryBuilderInterface;

class MoreLikeThisFactory
{
    /**
     * @param \Coyote\Wiki $wiki
     * @return QueryBuilderInterface
     */
    public function build($wiki) : QueryBuilderInterface
    {
        $builder = new QueryBuilder();

        $mlt = new MoreLikeThis(['title', 'text', 'excerpt']);
        $mlt->addDoc([
            '_index' => config('elasticsearch.default_index'),
            '_type' => 'wiki',
            '_id' => $wiki->id
        ]);

        $builder->addMoreLikeThis($mlt);
        $builder->addFilter(new NotTerm('id', $wiki->id));

        $builder->setSize(0, 10);

        return $builder;
    }
}