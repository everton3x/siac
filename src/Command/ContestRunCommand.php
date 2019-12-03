<?php

namespace App\Command;

use App\Contest\RuleTester;
use Exception;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class ContestRunCommand extends Command {

    protected static $defaultName = 'contest:run';

    protected function configure() {
        $this
                ->setDescription('Executa testes de consistência contábil.')
                ->addArgument('db', InputArgument::REQUIRED, 'Caminho para o arquivo *.db com os dados do PAD.')
                ->addOption('rule', null, InputOption::VALUE_REQUIRED, 'Executa uma determinada regra.')
                ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Executa um determinado perfil.')
                ->addOption('report', null, InputOption::VALUE_REQUIRED, 'Caminho para salvar o relatório PDF.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);

            $io->note(sprintf('Teste de consistência contábil iniciado em %s, às %s', date('d/m/Y'), date('H:i:s')));

            /* pega os argumentos e opções */
            $db = $input->getArgument('db');
            if (file_exists($db) === false) {
                throw new Exception("Banco de dados $db não encontrado.");
            }
            $rule = $input->getOption('rule');
            $profile = $input->getOption('profile');
            $report = $input->getOption('report');

            /* executa o testador adequado */
            $result = [];
            if ($rule) {
                $tester = new RuleTester($db);
                $testResult = $tester->run($rule);
                if ($testResult) {
                    $result = [
                        $rule => $testResult
                    ];
                }
                $profile = 'Nenhum';
            } elseif ($profile) {
                
            } else {
                throw new Exception('É necessário informar uma regra (--rule=nome_da_regra) ou perfil (--profile=nome_do_perfil).');
            }
            /* mostra o resultado na tela */
//            print_r($result);
            $io->success("Resultado do processamento par ao perfil: $profile");
            $totRules = 0;
            $totFails = 0;
            foreach ($result as $rule => $test){
                $totRules++;
                $fail = false;
                foreach ($test['total'] as $total){
                    if($total['diference'] <> 0){
                        $fail = true;
                        $totFails++;
                    }
                }
                
                if($fail){
                    $io->error("Regra $rule falhou!");
                }else{
                    $io->success("Regra $rule passou!");
                }
            }
            
            $io->note("Total de regras processadas: $totRules");
            $io->note("Total de regras com falha: $totFails");
            
            /* salva o relatório se for o caso */
            if ($report) {
                $io->note('Montando o relatório...');
                $loader = new FilesystemLoader('./tpl');
                $twig = new Environment($loader, ['cache' => false]);


                $template = $twig->load('report/contest/report.html.twig');
                $html = $template->render([
                    'dataBase' => $tester->getDataBase(),
                    'entidades' => $tester->getEntidades(),
                    'perfil' => $profile,
                    'resultado' => $result,
                ]);

//            file_put_contents('teste.html', $html);exit();
//            print_r($html);exit();
                $io->note('Salvando o relatório em PDF...');
                /* mpdf */
                error_reporting(E_ALL ^ E_NOTICE); //necessário para esconder um erro do mpdf
                $mpdf = new Mpdf([
                    'format' => 'A4-L'
                ]);
                $mpdf->SetFooter(sprintf('Gerado em %s|SIAC|{PAGENO}', date('d/m/Y, às H:i:s')));
                $mpdf->WriteHTML(file_get_contents('vendor/semantic/ui/dist/semantic.css'), HTMLParserMode::HEADER_CSS);
                $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
                $mpdf->Output($report, Destination::FILE);
            }

            /* fim */
            $io->success(sprintf('Teste de consistência contábil terminado em %s, às %s', date('d/m/Y'), date('H:i:s')));

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
