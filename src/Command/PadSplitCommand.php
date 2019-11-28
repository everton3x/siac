<?php

namespace App\Command;

use App\Pad\Split\Spliter;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class PadSplitCommand extends Command {

    protected static $defaultName = 'pad:split';

    protected function configure() {
        $this
                ->setDescription('Divide os dados do PAD agregados em arquivos segregados por entidades.')
                ->addArgument('db', InputArgument::REQUIRED, 'Caminho para o banco de dados agregados.')
                ->addOption('saveTo', null, InputOption::VALUE_REQUIRED, 'Caminho para o diretório onde serão salvos os dados segregados. Se omitido, o mesmo diretório dos dados agregados será usado.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);

            $io->writeln(sprintf("Segregação dos dados do PAD iniciada em %s", date('d/m/Y H:i:s')));

            /* prepara opções e argumentos */
            $db = $input->getArgument('db');
            if (!file_exists($db)) {
                throw new Exception("$db não existe.");
            }

            if (!($saveTo = $input->getOption('saveTo'))) {
                $saveTo = dirname($db) . DIRECTORY_SEPARATOR;
            }

            if (!is_dir($saveTo)) {
                throw new Exception("$saveTo não é um diretório.");
            }

            $io->writeln("Origem: $db");
            $io->writeln("Destino: $saveTo");

            /* processa */
            $spliter = new Spliter($db, $saveTo, 'pad-split.ini', $io);
            $spliter->run();

            /* fim */

            $io->success(sprintf("Segregação dos dados do PAD finalizada em %s", date('d/m/Y H:i:s')));

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
