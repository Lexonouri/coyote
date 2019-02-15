<?php

namespace Coyote\Services\Parser\Factories;

use Coyote\Repositories\Contracts\PageRepositoryInterface;
use Coyote\Repositories\Contracts\UserRepositoryInterface;
use Coyote\Services\Parser\Container;
use Coyote\Services\Parser\Parsers\Prism;
use Coyote\Services\Parser\Parsers\Link;
use Coyote\Services\Parser\Parsers\Markdown;
use Coyote\Services\Parser\Parsers\Purifier;
use Coyote\Services\Parser\Parsers\Smilies;

class PmFactory extends AbstractFactory
{
    /**
     * Parse microblog
     *
     * @param string $text
     * @return string
     */
    public function parse(string $text) : string
    {
        start_measure('parsing', 'Parsing private message...');

        $parser = new Container();

        // we don't want to cache user's private messages
        $parser->attach((new Markdown($this->app[UserRepositoryInterface::class]))->setBreaksEnabled(true));
        $parser->attach(new Purifier());
        $parser->attach(new Link($this->app[PageRepositoryInterface::class], $this->request->getHost()));
        $parser->attach(new Prism());

        if ($this->isSmiliesAllowed()) {
            $parser->attach(new Smilies());
        }

        $text = $parser->parse($text);
        stop_measure('parsing');

        return $text;
    }
}
