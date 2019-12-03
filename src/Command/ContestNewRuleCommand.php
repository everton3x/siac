<?php

namespace App\Command;

use App\Contest\ContestTesterAbstract;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ContestNewRuleCommand extends Command {

    protected static $defaultName = 'contest:new-rule';

    protected function configure() {
        $this
                ->setDescription('Cria um arquivo base de regra para teste de consistência contábil.')
                ->addArgument('rule', InputArgument::REQUIRED, 'Nome do arquivo base da regra (sem extensão).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);
            
            /* processa argumentos e opções */
            $rule = $input->getArgument('rule');
            $rulePath = ContestTesterAbstract::getRuleDir();
            $frule = "$rulePath$rule.ini";
            
            /* verifica se o arquivo de regra já existe */
            if(file_exists($frule)){
                $overwrite = $io->confirm("Regra $rule ($frule) já existe. Deseja sobrescrever?");
                if($overwrite === false){
                    throw new Exception("Abortando...");
                }
            }
            
            /* pede dados complementares */
            $ruleTitle = $io->ask('Título da regra:');
            $ruleDescription = $io->ask('Descrição da regra [""]:');
            $ruleActive = $io->confirm('A regra está ativa?');
            
            /* processa dados complementares */
            if($ruleTitle == false){
                throw new Exception('Um título para a regra é obrigatório.');
            }
            
            /* montando conteúdo do arquivo */
            $ruleData = sprintf('title = "%s"'.PHP_EOL, $ruleTitle);
            $ruleData .= sprintf('description = "%s"'.PHP_EOL, $ruleDescription);
            $ruleData .= sprintf('active = "%s"'.PHP_EOL, (string) $ruleActive);
            $ruleData .= '[total1]'.PHP_EOL;
            $ruleData .= 'value[] = ""'.PHP_EOL;
            $ruleData .= 'value[] = ""'.PHP_EOL;
            $ruleData .= '[total2]'.PHP_EOL;
            $ruleData .= 'value[] = ""'.PHP_EOL;
            $ruleData .= 'value[] = ""'.PHP_EOL;

            /* salvando arquivo */
            if(file_put_contents($frule, $ruleData) === false){
                throw new Exception("Falha ao salvar $frule");
            }
//            print_r(parse_ini_file($frule, true));
            /* fim */
            $io->success(sprintf('Regra %s criada em %s', $rule, $frule));

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
