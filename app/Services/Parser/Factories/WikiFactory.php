<?php

namespace Coyote\Services\Parser\Factories;

use Coyote\Repositories\Contracts\PageRepositoryInterface;
use Coyote\Repositories\Contracts\UserRepositoryInterface;
use Coyote\Repositories\Contracts\WikiRepositoryInterface;
use Coyote\Services\Parser\Container;
use Coyote\Services\Parser\Parsers\Context;
use Coyote\Services\Parser\Parsers\Prism;
use Coyote\Services\Parser\Parsers\Latex;
use Coyote\Services\Parser\Parsers\Link;
use Coyote\Services\Parser\Parsers\Markdown;
use Coyote\Services\Parser\Parsers\Purifier;
use Coyote\Services\Parser\Parsers\Template;

class WikiFactory extends AbstractFactory
{
    /**
     * Parse post
     *
     * @param string $text
     * @return string
     */
    public function parse(string $text) : string
    {
        start_measure('parsing', 'Parsing wiki...');

        $isInCache = $this->cache->has($text);
        if ($isInCache) {
            $text = $this->cache->get($text);
        } else {
            $parser = new Container();

            $text = $this->cache($text, function () use ($parser) {
                $allowedTags = explode(',', config('purifier')['HTML.Allowed']);
                unset($allowedTags['ul']);

                // we add those tags for backward compatibility
                $allowedTags[] = 'div[class]';
                $allowedTags[] = 'ul[class]';
                $allowedTags[] = 'h1';

                $parser->attach(new Template($this->app[WikiRepositoryInterface::class]));
                $parser->attach((new Markdown($this->app[UserRepositoryInterface::class]))->setBreaksEnabled(true)->setEnableUserTagParser(false));
                $parser->attach(new Latex());
                $parser->attach((new Purifier())->set('HTML.Allowed', implode(',', $allowedTags)));
                $parser->attach(new Link($this->app[PageRepositoryInterface::class], $this->request->getHost()));
                $parser->attach(new Context());
                $parser->attach(new Prism());

                return $parser;
            });
        }
        stop_measure('parsing');

        return $text;
    }
}
