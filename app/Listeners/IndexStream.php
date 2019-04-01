<?php

namespace Coyote\Listeners;

use Coyote\Events\StreamSaved;
use Illuminate\Contracts\Queue\ShouldQueue;

class IndexStream implements ShouldQueue
{
    /**
     * Postpone this job to make sure that postgresql transaction was completed before indexing this record in elasticsearch.
     *
     * @var int
     */
    public $delay = 30;

    /**
     * @param StreamSaved $event
     * @throws \Exception
     */
    public function handle(StreamSaved $event)
    {
        $event->stream->putToIndex();
    }
}
