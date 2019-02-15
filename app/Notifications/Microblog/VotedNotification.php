<?php

namespace Coyote\Notifications\Microblog;

use Coyote\Microblog\Vote;
use Coyote\Services\UrlBuilder\UrlBuilder;
use Coyote\User;
use Illuminate\Notifications\Messages\MailMessage;

class VotedNotification extends AbstractNotification
{
    const ID = \Coyote\Notification::MICROBLOG_VOTE;

    /**
     * @var Vote
     */
    private $vote;

    /**
     * @param Vote $vote
     */
    public function __construct(Vote $vote)
    {
        $this->vote = $vote;

        parent::__construct($vote->microblog);
    }

    /**
     * @return array
     */
    public function sender()
    {
        return [
            'user_id'       => $this->vote->user_id,
            'name'          => $this->vote->user->name
        ];
    }

    /**
     * @param User $user
     * @return array
     */
    public function toDatabase(User $user)
    {
        $url = $this->microblog->parent_id
            ? UrlBuilder::microblogComment($this->microblog->parent, $this->microblog->id)
                : UrlBuilder::microblog($this->microblog);

        return [
            'object_id'     => $this->objectId(),
            'user_id'       => $user->id,
            'type_id'       => static::ID,
            'subject'       => excerpt($this->microblog->parent_id ? $this->microblog->parent->html : $this->microblog->html),  // original excerpt of parent entry
            'excerpt'       => excerpt($this->microblog->html),
            'url'           => $url,
            'guid'          => $this->id
        ];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail()
    {
        return (new MailMessage)
            ->subject($this->getMailSubject())
            ->line(
                sprintf(
                    'Informujemy, ze <strong>%s</strong> docenił Twój wpis na mikroblogu.',
                    $this->vote->user->name
                )
            )
            ->action('Zobacz wpis', url($this->notificationUrl()))
            ->line('Dostajesz to powiadomienie, ponieważ obserwujesz ten wpis.');
    }

    /**
     * @return string
     */
    public function getMailSubject(): string
    {
        return $this->vote->user->name . ' docenił Twój wpis na mikroblogu';
    }
}
