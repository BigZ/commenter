<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Njasm\Soundcloud\SoundcloudFacade;
use App\Service\Soundcloud;

class AppCommenterCommand extends Command
{
    protected static $defaultName = 'app:commenter';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('clientId', InputArgument::REQUIRED, 'client id')
            ->addArgument('clientSecret', InputArgument::REQUIRED, 'client secret')
            ->addArgument('username', InputArgument::REQUIRED, 'username')
            ->addArgument('password', InputArgument::REQUIRED, 'password')
            ->addArgument('artist', InputArgument::REQUIRED, 'artist')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $clientId = $input->getArgument('clientId');
        $clientSecret = $input->getArgument('clientSecret');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $artist = $input->getArgument('artist');

        $souncloud = new Soundcloud($clientId, $clientSecret, $username, $password);
        $user = $souncloud->getUserInfos($artist);
        $toFollow = $souncloud->getFollowers($user->id);

        foreach ($toFollow as $prospect) {
            if ($souncloud->follow($prospect)) {
                $souncloud->commentLastTrack($prospect);
                sleep(20 + mt_rand(0, 100));
            }
        }
    }
}
