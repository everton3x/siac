<?php

namespace App\Command;

use App\Parser\Parser;
use App\Reader\Reader;
use App\Writer\Factory;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class PadConvertCommand extends Command
{
    protected static $defaultName = 'pad:convert';

    protected function configure()
    {
        $this
            ->setDescription('Converte os TXT do PAD para um formato escolhido.')
            ->addArgument('destiny', InputArgument::REQUIRED, 'Caminho para o resultado da conversão. A extensão informada define o formato de destino da conversão.')
            ->addArgument('origin', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Caminhos para os diretórios dos arquivos *.txt. Se informado mais de um, os dados serão agregados')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            
            $io = new SymfonyStyle($input, $output);

            $io->note(sprintf('Conversão iniciada em %s, às %s', date('d/m/Y'), date('h:i:s')));

            /* Trata os argumentos */
            $argDestiny = $input->getArgument('destiny');
            $argOrigin = $input->getArgument('origin');

            if ($argDestiny) {
                $io->note(sprintf('Destino: %s', $argDestiny));
            } else {
                throw new InvalidArgumentException("Destino inválido.");
            }
            if ($argOrigin) {
//                $io->note('Origens:');
//                foreach ($argOrigin as $origin) {
//                    $io->note(sprintf("\t-> %s", $origin));
//                }
                $io->note(join(PHP_EOL, array_merge(['Origens:'], $argOrigin)));
            }else{
                throw new InvalidArgumentException("Origem inválida.");
            }

            /* Futuramente será possível escolher um caminho com as especificações de conversão */
            $optSpecDir = 'spec/';
            
            /* verifica se o destino já existe */
            if(file_exists($argDestiny)){
                $qhelper = $this->getHelper('question');
                $question = new ConfirmationQuestion("$argDestiny já existe. Deseja excluir? [S,n]", true, '/^s/i');
                if($qhelper->ask($input, $output, $question)){
                    $fs = new Filesystem();
                    $fs->remove($argDestiny);
                }else{
                    throw new Exception("O destino $argDestiny já existe.");
                }
            }
            
            /* Instancia o Reader */
            $reader = new Reader($argOrigin, $optSpecDir);
            
            /* Seleciona o Writer adequadao */
            $writer = Factory::createWriter($argDestiny);
            
            /* Cria o parser e executa */
            $parser = new Parser($reader, $writer, $io);
            $parser->run();
            
            $io->newLine();
            $io->success(sprintf('Conversão concluída em %s, às %s', date('d/m/Y'), date('h:i:s')));

            return 0;
        } catch (Exception $exc) {
            throw $exc;
        }

    }
}
