<?php

namespace App\Command;

use App\Contest\ContestTesterAbstract;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContestShowProfilesCommand extends Command
{
    protected static $defaultName = 'contest:show-profiles';

    protected function configure()
    {
        $this
            ->setDescription('Lista os perfis disponíveis.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try{
        $io = new SymfonyStyle($input, $output);
            $io->writeln("Os perfis disponíveis são:");
            foreach (ContestTesterAbstract::getProfiles() as $profile){
                $io->writeln($profile);
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        

        return 0;
    }
}
