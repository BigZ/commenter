<?php

namespace App\Service;

use Njasm\Soundcloud\SoundcloudFacade;

class Soundcloud
{
    private $comments = [
        'cool music',
        'super moment',
        'the vibe here is cool',
        'i like the vibe',
        'i like the music',
        'so cool !',
        'keep on going',
        'keep on rockin',
        'this one is great',
        'sounds great',
        'amazing',
        'amazing music bro',
        'killa',
        'so much killa',
        'uberkilla \o/',
        'that sounds good',
        'love the music',
        'great atmosphere',
        'breathtaking music',
        'great creation',
        'gogogo !!',
        'it s super',
        'super duper',
        'cool vibe',
        'great moment',
        'crazy',
        'good stuff',
        'great stuff',
        'this rocks',
        'rockin !',
        'cool tune',
        'good one',
        'nice one',
        'like it !',
        'good one',
        'briliant',
        'gooood',
        'good one',
        'nice bro',
        'like it',
        'sweet, dude !',
        'yeah',
        'badass',
        'tuning in',
        'nice part',
        'nice track',
        'i like that :)',
        'great mate',
        'sounds fine',
        'yeah bro',
        'killer track',
        'rockin !',
        'man that sounds cool !',
        'definitely lovin it',
        'crazy sounds in this one',
        'good work on the synths'
    ];

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

        echo "commented $comment on $track->permalink of ".$track->user->permalink." at $timing";
    }

    /**
     * @param int|string $userId
     * @return Object
     */
    public function getFollowers($userId)
    {
        $cursor = null;
        $url = '/users/'.$userId.'/followers';
        $relevantFollowerList = [];
        do {
            $followers = $this
                ->facade
                ->get($url, ['limit' => 200, 'cursor' => $cursor])
                ->request()
                ->bodyObject();

            foreach ($followers->collection as $follower) {
                if ($follower->track_count > 0  && $follower->comments_count > 0) {
                    $relevantFollowerList[] = $follower;
                }
            }

            if (!$followers->next_href) {
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