<?php

namespace Coyote\Repositories\Contracts;

/**
 * @property \Coyote\Repositories\Contracts\Forum\OrderRepositoryInterface $order
 */
interface ForumRepositoryInterface extends RepositoryInterface
{
    /**
     * Gets categories grouped by sections. You need to pass either $userId or $sessionId (for anonymous users)
     *
     * @param string $guestId
     * @param null|int $parentId
     * @return mixed
     */
    public function categories($guestId, $parentId = null);

    /**
     * @param int $userId
     * @return mixed
     */
    public function categoriesOrder($userId);

    /**
     * Sort a list of categories that can be shown in a <select>
     *
     * @return \Coyote\Forum[]
     */
    public function list();

    /**
     * @return array
     */
    public function getTagsCloud();

    /**
     * @param array $tags
     * @return mixed
     */
    public function getTagsWeight(array $tags);

    /**
     * Mark forum as read
     *
     * @param $forumId
     * @param $guestId
     */
    public function markAsRead($forumId, $guestId);
}
