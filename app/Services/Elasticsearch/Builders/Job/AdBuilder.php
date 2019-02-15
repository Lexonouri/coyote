<?php

namespace Coyote\Services\Elasticsearch\Builders\Job;

use Coyote\Services\Elasticsearch\Filters\Term;
use Coyote\Services\Elasticsearch\Functions\Random;
use Coyote\Services\Elasticsearch\QueryBuilder;
use Coyote\Services\Elasticsearch\QueryString;

class AdBuilder extends SearchBuilder
{
    /**
     * @param array $tags
     */
    public function boostTags(array $tags)
    {
        $this->should(new QueryString(implode(' ', $tags), ['title^4', 'tags^2', 'description'], 3));
    }

    /**
     * @return array
     */
    public function build()
    {
        // only premium offers
        $this->must(new Term('is_ads', true));

        $this->score(new Random());
        $this->size(0, 4);

        $this->source([
            'id',
            'title',
            'slug',
            'is_remote',
            'remote_range',
            'firm.*',
            'locations',
            'tags',
            'currency_symbol',
            'salary_from',
            'salary_to'
        ]);

        return QueryBuilder::build();
    }
}
