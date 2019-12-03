<?php

namespace App\Command;

use App\Contest\ContestTesterAbstract;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContestNewProfileCommand extends Command {

    protected static $defaultName = 'contest:new-profile';

    protected function configure() {
        $this
                ->setDescription('Cria um novo perfil base com todas as regras existentes.')
                ->addArgument('profile', InputArgument::REQUIRED, 'Nome do perfil, sem a extensão.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);

            /* processa argumentos e opções */
            $profile = $input->getArgument('profile');
            $profilePath = ContestTesterAbstract::getProfileDir();
            $fprofile = "$profilePath$profile.profile";

            /* verifica se o arquivo de perfil já existe */
            if (file_exists($fprofile)) {
                $overwrite = $io->confirm("Perfil $profile ($fprofile) já existe. Deseja sobrescrever?");
                if ($overwrite === false) {
                    throw new Exception("Abortando...");
                }
            }

            /* lendo as regras existentes */
            $profileList = ContestTesterAbstract::getRules();
            $numRules = count($profileList);
            $data = join(PHP_EOL, $profileList);
            /* salvando arquivo */
            if (file_put_contents($fprofile, $data) === false) {
                throw new Exception("Falha ao salvar $frule");
            }
//            print_r(parse_ini_file($frule, true));
            /* fim */
            $io->success(sprintf('Perfil %s criado em %s com %s regras', $profile, $fprofile, $numRules));

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
