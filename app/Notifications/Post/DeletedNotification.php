<?php

namespace Coyote\Notifications\Post;

use Coyote\Services\UrlBuilder\UrlBuilder;
use Coyote\User;
use Illuminate\Notifications\Messages\MailMessage;

class DeletedNotification extends AbstractNotification
{
    const ID = \Coyote\Notification::POST_DELETE;

    /**
     * @var string
     */
    private $reasonName;

    /**
     * @var string
     */
    private $reasonText;

    /**
     * @return string
     */
    public function getReasonName()
    {
        return $this->reasonName ?: '(moderator nie podał powodu)';
    }

    /**
     * @param string|null $reasonName
     * @return $this
     */
    public function setReasonName(?string $reasonName)
    {
        $this->reasonName = $reasonName;

        return $this;
    }

    /**
     * @return string
     */
    public function getReasonText()
    {
        return $this->reasonText ?: '(moderator nie podał powodu)';
    }

    /**
     * @param string|null $reasonText
     * @return $this
     */
    public function setReasonText(?string $reasonText)
    {
        $this->reasonText = $reasonText;

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
            'subject'       => $this->post->topic->subject,
            'excerpt'       => $this->getReasonName(),
            'url'           => UrlBuilder::post($this->post),
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
            ->view('emails.notifications.post.delete', [
                'sender'        => $this->notifier->name,
                'subject'       => link_to(UrlBuilder::post($this->post), $this->post->topic->subject),
                'reason_name'   => $this->getReasonName(),
                'reason_text'   => $this->getReasonText()
            ]);
    }

    /**
     * @return string
     */
    protected function getMailSubject(): string
    {
        return 'Post został usunięty przez ' . $this->notifier->name;
    }
}
