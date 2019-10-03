<?php

namespace App\Service;

use Njasm\Soundcloud\SoundcloudFacade;

class Soundcloud
{
    private $comments = [];

    /**
     * @var SoundcloudFacade
     */
    private $facade;

    /**
     * @var string
     */
    private $username;

    /**
     * Soundcloud constructor.
     * @param string $clientId
     * @param string $clientSecret
     * @param string $username
     * @param string $password
     */
    public function __construct($clientId, $clientSecret, $username, $password)
    {
        $facade = new SoundcloudFacade($clientId, $clientSecret);
        $facade->userCredentials($username, $password);
        $this->facade = $facade;
        $this->comments = explode("\n", file_get_contents(__DIR__ . '/../../comments.txt'));
    }

    /**
     * @param string $artistName
     * @return Object
     */
    public function getUserInfos($artistName)
    {
        $artistUrl = sprintf('http://soundcloud.com/%s', $artistName);
        $location = $this->facade->get('/resolve', ['url' => $artistUrl])->request()->bodyObject();
        $apiLocation = $this->getRelativePath($location->location);
        $user = $this->facade->get($apiLocation)->request()->bodyObject();

        return $user;
    }

    /**
     * Follow someone new. return true on succes, false if you were already following him.
     * @param $prospect
     * @return bool
     */
    public function follow($prospect)
    {
        try {
            $this->facade->get('/me/followings/'.$prospect->id, array())->request()->bodyObject();
        } catch (\Exception $exception) {
            // not following yet
            // @TODO: this might also means that we couldn't follow
            // for instance because we followed too many people lastly
            // check on error code
            $this->facade->put('/me/followings/'.$prospect->id, array())->request();

            dump(sprintf('Following: %s', $prospect->username));
            return true;
        }

        return false;
    }

    /**
     * @param object $user soundcloud user
     * @return soundcloud track|false if not found
     */
    public function getUserLastTrack($user)
    {
        $tracks = $this
            ->facade
            ->get('/users/'.$user->id.'/tracks', ['limit' => 5])
            ->request()
            ->bodyObject();

        if (is_array($tracks) && $tracks) {
            return $tracks[0];
        }

        return false;
    }

    /**
     * @param object $user Soundcloud user
     * @return bool
     */
    public function commentLastTrack($user)
    {
        // Do not comment your own tracks.
        if ($user->username === $this->username) {
            return false;
        }
        
        $lastTrack = $this->getUserLastTrack($user);
        if (!$lastTrack || !$lastTrack->commentable) {
            return false;
        }

        $this->comment($lastTrack, $this->getRandomComment(), $this->getRandomTiming($lastTrack->duration));
    }

    /**
     * @param object $track Soundcloud track
     * @param string $comment
     * @param int $timing
     */
    public function comment($track, $comment, $timing)
    {
        $this->facade->post(
            '/tracks/' . $track->id . '/comments',
            [
                'comment' => ['body' => $comment, 'timestamp' => $timing]
            ]
        )->request()->bodyObject();

        dump("commented \"$comment\" on \"$track->permalink\" of \"".$track->user->permalink."\" at \"$timing\"\n");
    }

    /**
     * @param int|string $userId
     * @return Object
     */
    public function getFollowers($userId)
    {
        $cursor = null;
        $relevantFollowerList = [];
        do {
            $followers = $this
                ->facade
                ->get('/users/'.$userId.'/followers', ['limit' => 200, 'cursor' => $cursor])
                ->request()
                ->bodyObject();

            foreach ($followers->collection as $follower) {
                if ($follower->track_count > 0  && $follower->comments_count > 0) {
                    $relevantFollowerList[] = $follower;
                }
            }

            if (!$followers->next_href || count($relevantFollowerList) > 500) {
                break;
            }

            parse_str(parse_url($followers->next_href)['query'], $params);
            $cursor = $params['cursor'];
        } while (42);

        return $relevantFollowerList;
    }

    private function getRelativePath($path)
    {
        return str_replace('https://api.soundcloud.com', '', $path);
    }

    private function getRandomComment()
    {
        return $this->comments[mt_rand(0, count($this->comments) - 1)];
    }

    private function getRandomTiming($duration)
    {
        return mt_rand(10000, $duration - 10000);
    }
}