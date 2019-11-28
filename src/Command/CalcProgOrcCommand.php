<?php

namespace App\Command;

use App\ProgOrc\ProgOrcCalculator;
use Exception;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class CalcProgOrcCommand extends Command {

    protected static $defaultName = 'prog-orc:calc';

    protected function configure() {
        $this
                ->setDescription('Faz a avaliação da programação orçamentária com base nos dados do SIAPC/PAD.')
                ->addArgument('data', InputArgument::REQUIRED, 'Caminho para o arquivo *.db com os dados do PAD.')
                ->addArgument('report', InputArgument::REQUIRED, 'Caminho para o arquivo PDF do relatório.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);

            /* preparação dos argumentos e opções */
            $argData = $input->getArgument('data');
            $argReport = $input->getArgument('report');

            if (!file_exists($argData)) {
                $io->error(sprintf('Arquivo de origem de dados não encontrado: %s', $argData));
            }
            
            $io->note(sprintf('Origem: %s', $argData));
            $io->note(sprintf('Relatório: %s', $argReport));

            $calculator = new ProgOrcCalculator($argData, $io);
            $io->note(sprintf('Data-base: %s', $calculator->getDataBase()->format('d/m/Y')));
            $io->note('Entidades incluídas: ');
            foreach ($calculator->getEntidades() as $entidade){
                $io->note(sprintf("\t-> %s", $entidade['nome']));
            }
            
            /* inicia o cálculo */
            $io->note('Calculando avaliação...');
            $calculator->run();
            $result = $calculator->getResult();
            
            $result = ProgOrcCalculator::clearZeroLines($result);
//            print_r($result);exit();
            
            $io->newLine();
            $io->note('Calculando saldo de recurso livre não comprometido...');
            $saldoLivre = $calculator->getSaldoLivre($result);
            
            /* gera o relatório */
            $io->note('Montando o relatório...');
            $loader = new FilesystemLoader('./tpl');
//            $twig = new Environment($loader, ['cache' => './cache']);
            $twig = new Environment($loader, ['cache' => false]);
            
            
            $template = $twig->load('report/prog-orc/analitic.html.twig');
            $html = $template->render([
                'dataBase' => $calculator->getDataBase(),
                'entidades' => $calculator->getEntidades(),
                'dados' => $result,
                'saldoLivre' => $saldoLivre
            ]);
            
//            file_put_contents('teste.html', $html);exit();
//            print_r($html);exit();
            $io->note('Salvando o relatório em PDF...');
            /* mpdf */
            error_reporting(E_ALL ^E_NOTICE);//necessário para esconder um erro do mpdf
            $mpdf = new Mpdf([
                'format' => 'A4-L'
            ]);
            $mpdf->SetFooter(sprintf('Gerado em %s|SIAC|{PAGENO}', date('d/m/Y, às H:i:s')));
            $mpdf->WriteHTML(file_get_contents('vendor/semantic/ui/dist/semantic.css'), HTMLParserMode::HEADER_CSS);
            $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
            $mpdf->Output($argReport, Destination::FILE);
            
            /* dompdf */
//            $dompdf = new Dompdf();
//            $dompdf->setOptions(new Options([
//                'isHtml5ParserEnabled' => true,
//                'defaultMediaType' => 'print',
//                'pdfBackend' => 'auto'
//                ]));
//            $dompdf->loadHtml($html);
//            $dompdf->setPaper('A4', 'landscape');
//            $dompdf->render();
//            file_put_contents($argReport, $dompdf->output());
            
            /* fim */
            $io->success('Comando concluído com sucesso!');

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
