<?php

namespace App\Command;

use App\Pad\Convert\Parser\Parser;
use App\Pad\Convert\Reader\Reader;
use App\Pad\Convert\Writer\Factory;
use InvalidArgumentException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addArgument('destiny', InputArgument::OPTIONAL, 'Caminho para o resultado da conversão. A extensão informada define o formato de destino da conversão.')
            ->addArgument('origin', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Caminhos para os diretórios dos arquivos *.txt. Se informado mais de um, os dados serão agregados')
            ->addOption('salvar', 's', InputOption::VALUE_NONE, 'Salva os parâmetros de conversão para serem carregados com --carregar')
            ->addOption('carregar', 'c', InputOption::VALUE_NONE, 'Executa a conversão a partir dos parâmetros salvos por --salvar')
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

            /* se a opção --carregar for fornecida */
            if($input->getOption('carregar')){
                if(($params = parse_ini_file('param_convert.ini')) == false){
                    throw new Exception("Falha ao carregar os parâmetros da conversão.");
                }else{
                    $argDestiny = $params['destiny'];
                    $argOrigin = $params['origin'];
                    $optSpecDir = $params['spec'];
                }
            }
            
            if ($argDestiny) {
                $io->note(sprintf('Destino: %s', $argDestiny));
            } else {
                throw new InvalidArgumentException("Destino inválido.");
            }
            if ($argOrigin) {
                $io->note(join(PHP_EOL, array_merge(['Origens:'], $argOrigin)));
            }else{
                throw new InvalidArgumentException("Origem inválida.");
            }

            /* Futuramente será possível escolher um caminho com as especificações de conversão */
            $optSpecDir = 'spec/';
            
            
            /* Salva os parâmetros de conversão para uso futuro */
            if($input->getOption('salvar')){
                $params = "destiny=\"$argDestiny\"".PHP_EOL;
                $params .= "spec=\"$optSpecDir\"".PHP_EOL;
                foreach ($argOrigin as $i => $value){
                    $params .= "origin[$i]=\"$value\"".PHP_EOL;
                }
                if(file_put_contents('param_convert.ini', $params)){
                    $io->note('Parâmetros salvos em param_convert.ini');
                }else{
                    $io->error('Falha ao salvar os parêmtros em param_convert.ini');
                }
            }
            
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
