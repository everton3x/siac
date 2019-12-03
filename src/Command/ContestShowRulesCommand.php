<?php

namespace App\Command;

use App\Contest\ContestTesterAbstract;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContestShowRulesCommand extends Command
{
    protected static $defaultName = 'contest:show-rules';

    protected function configure()
    {
        $this
            ->setDescription('Lista as regras disponíveis.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try{
        $io = new SymfonyStyle($input, $output);
            $io->writeln("As regras disponíveis são:");
            foreach (ContestTesterAbstract::getRules() as $rule){
                $io->writeln($rule);
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        

        return 0;

        return 0;
    }
}
