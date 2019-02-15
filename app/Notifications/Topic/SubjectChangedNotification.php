<?php

namespace Coyote\Notifications\Topic;

use Coyote\Notification;
use Coyote\Services\UrlBuilder\UrlBuilder;
use Coyote\User;
use Illuminate\Notifications\Messages\MailMessage;

class SubjectChangedNotification extends AbstractNotification
{
    const ID = Notification::TOPIC_SUBJECT;

    /**
     * @var string
     */
    private $originalSubject;

    /**
     * @param mixed $originalSubject
     * @return $this
     */
    public function setOriginalSubject($originalSubject)
    {
        $this->originalSubject = $originalSubject;

        return $this;
    }

    /**
     * @param User $user
     * @return array
     */
    public function toDatabase(User $user)
    {
        return [
            'object_id'     => $this->objectId(),
            'user_id'       => $user->id,
            'type_id'       => static::ID,
            'subject'       => $this->topic->subject,
            'excerpt'       => $this->originalSubject,
            'url'           => UrlBuilder::topic($this->topic),
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
        return (new MailMessage())
            ->subject($this->getMailSubject())
            ->view('emails.notifications.topic.subject', [
                'sender'        => $this->notifier->name,
                'subject'       => link_to($this->notificationUrl(), $this->topic->subject),
                'original'      => $this->originalSubject
            ]);
    }

    /**
     * @return string
     */
    public function getMailSubject(): string
    {
        return 'Tytuł wątku został zmieniony';
    }
}
