<?php

namespace App\Command;

use App\Pessoal\DotacaoCalculator;
use Exception;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\Config\Definition\Exception\Exception as Exception2;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PessoalDotacaoCommand extends Command {

    protected static $defaultName = 'pessoal:dotacao';

    protected function configure() {
        $this
                ->setDescription('Add a short description for your command')
                ->addArgument('db', InputArgument::REQUIRED, 'Caminho para o arquivo */.db com os dados do PAD.')
                ->addOption('report', null, InputOption::VALUE_REQUIRED, 'Se fornecido, é o caminho para salvar o relatório em PDF.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);
            $io->note(sprintf("Cálculo do superavit/deficit da dotação da folha iniciado em %s", date('d/m/Y H:i:s')));

            /* prepara argumetnos e opções */
            $db = $input->getArgument('db');
            if (!file_exists($db)) {
                throw new Exception2("$db não localizado.");
            } else {
                $io->note("Origem: $db");
            }

            /* executa o cálculo */
            $calculator = new DotacaoCalculator($db);
            $resultMedia = $calculator->calcMediaMensal();

            /* mostra resultado na tela */
            $io->note("Resultado do cálculo - média mensal empenhada:");
            $io->writeln(sprintf("Data-base:\t\t\t%s", $resultMedia['dataBase']->format('d/m/Y')));
            $io->writeln('Entidades:');
            foreach ($resultMedia['entidades'] as $entidade) {
                $io->writeln(sprintf("\t%s", $entidade));
            }
//            print_r($resultMedia);
            $io->writeln(sprintf("Dotação atualizada:\t\t\t\t\t%s", number_format($resultMedia['dotacaoAtualizada'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Reserva para suplementar diferente de folha:\t%s", number_format($resultMedia['reserva'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Dotação atualizada das deduções:\t\t\t%s", number_format($resultMedia['dotAtualizDeducoes'], 2, ',', '.')));
            $io->writeln(sprintf("(=) Dotação líquida:\t\t\t\t\t%s", number_format($resultMedia['dotacaoLiquida'], 2, ',', '.')));
            $io->newLine();
            $io->writeln(sprintf("Empenhado até o mês:\t\t\t\t\t%s", number_format($resultMedia['empenhadoAteMes'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Empenhado nas deduções até o mês:\t\t\t%s", number_format($resultMedia['empenhadoAteMesDeducoes'], 2, ',', '.')));
            $io->writeln(sprintf("(=) Empenhado líquido até o mês:\t\t\t%s", number_format($resultMedia['empenhadoLiquido'], 2, ',', '.')));
            $io->writeln(sprintf("Meses empenhados:\t\t\t\t\t%s", $resultMedia['mesesEmpenhados']));
            $io->writeln(sprintf("Média mensal empenhada:\t\t\t\t\t%s", number_format($resultMedia['mediaMensal'], 2, ',', '.')));
            $io->newLine();
            $io->writeln(sprintf("Meses a empenhar:\t\t\t\t\t%s", $resultMedia['mesesAEmpenharMedia']));
            $io->writeln(sprintf("Valor a empenhar:\t\t\t\t\t%s", number_format($resultMedia['valorAEmpenharMedia'], 2, ',', '.')));
            $io->writeln(sprintf("Suficiência/Insuficiência de dotação:\t\t\t%s", number_format($resultMedia['resultadoMedia'], 2, ',', '.')));
            
            
            $resultUltimo = $calculator->calcUltimoMes();
            $io->note("Resultado do cálculo - último mês empenhado:");
            $io->writeln(sprintf("Data-base:\t\t\t%s", $resultMedia['dataBase']->format('d/m/Y')));
            $io->writeln('Entidades:');
            foreach ($resultMedia['entidades'] as $entidade) {
                $io->writeln(sprintf("\t%s", $entidade));
            }
//            print_r($resultMedia);
            $io->writeln(sprintf("Dotação atualizada:\t\t\t\t\t%s", number_format($resultUltimo['dotacaoAtualizada'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Reserva para suplementar diferente de folha:\t%s", number_format($resultUltimo['reserva'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Dotação atualizada das deduções:\t\t\t%s", number_format($resultUltimo['dotAtualizDeducoes'], 2, ',', '.')));
            $io->writeln(sprintf("(=) Dotação líquida:\t\t\t\t\t%s", number_format($resultUltimo['dotacaoLiquida'], 2, ',', '.')));
            $io->newLine();
            $io->writeln(sprintf("Empenhado até o mês:\t\t\t\t\t%s", number_format($resultUltimo['empenhadoAteMes'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Empenhado nas deduções até o mês:\t\t\t%s", number_format($resultUltimo['empenhadoAteMesDeducoes'], 2, ',', '.')));
            $io->writeln(sprintf("(=) Empenhado líquido até o mês:\t\t\t%s", number_format($resultUltimo['empenhadoLiquido'], 2, ',', '.')));
            $io->newLine();
            $io->writeln(sprintf("Empenhado no último mês:\t\t\t\t%s", number_format($resultUltimo['empenhadoUltimoMes'], 2, ',', '.')));
            $io->writeln(sprintf("(-) Deduções empenhadas no último mês:\t\t\t%s", number_format($resultUltimo['empenhadoUltimoMesDeducoes'], 2, ',', '.')));
            $io->writeln(sprintf("(=) Líquido empenhado no último mês:\t\t\t%s", number_format($resultUltimo['empenhadoUltimoMesLiquido'], 2, ',', '.')));
            $io->newLine();
            $io->writeln(sprintf("Meses a empenhar:\t\t\t\t\t%s", $resultUltimo['mesesAEmpenhar']));
            $io->writeln(sprintf("Valor a empenhar:\t\t\t\t\t%s", number_format($resultUltimo['valorAEmpenharUltimoMes'], 2, ',', '.')));
            $io->writeln(sprintf("Suficiência/Insuficiência de dotação:\t\t\t%s", number_format($resultUltimo['resultadoUltimoMes'], 2, ',', '.')));

            /* salva o relatório em pdf se for o caso */
            if ($input->getOption('report')) {
                $io->note('Montando o relatório...');
                $loader = new FilesystemLoader('./tpl');
                $twig = new Environment($loader, ['cache' => false]);
                $template = $twig->load('report/pessoal/dotacao.html.twig');
                $html = $template->render([
                    'dataBase' => $resultMedia['dataBase'],
                    'entidades' => $resultMedia['entidades'],
                    'media' => $resultMedia,
                    'ultimo' => $resultUltimo
                ]);

                $io->note('Salvando o relatório em PDF...');
                /* mpdf */
                error_reporting(E_ALL ^ E_NOTICE); //necessário para esconder um erro do mpdf
                $mpdf = new Mpdf([
                    'format' => 'A4'
                ]);
                $mpdf->SetFooter(sprintf('Gerado em %s|SIAC|{PAGENO}', date('d/m/Y, às H:i:s')));
                $mpdf->WriteHTML(file_get_contents('vendor/semantic/ui/dist/semantic.css'), HTMLParserMode::HEADER_CSS);
                $mpdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
                $mpdf->Output($input->getOption('report'), Destination::FILE);
            }

            $io->success(sprintf("Cálculo do superavit/deficit da dotação da folha finalizado em %s", date('d/m/Y H:i:s')));
            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
