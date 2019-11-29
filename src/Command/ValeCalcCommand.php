<?php

namespace App\Command;

use App\ValeAlim\ValeAlimCalculator;
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

class ValeCalcCommand extends Command {

    protected static $defaultName = 'vale-alim:calc';

    protected function configure() {
        $this
                ->setDescription('Calcula o superavit/deficit da dotação do vale-alimentação.')
                ->addArgument('db', InputArgument::REQUIRED, 'Caminho para o arquivo *.db com os dados do PAD.')
                ->addOption('report', null, InputOption::VALUE_REQUIRED, 'Caminho para salvar um relatório em PDF com o resultado do cálculo.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        try {
            $io = new SymfonyStyle($input, $output);

            $io->note(sprintf("Cálculo do superavit/deficit da dotação de vale-alimentação iniciado em %s", date('d/m/Y H:i:s')));

            /* prepara argumetnos e opções */
            $db = $input->getArgument('db');
            if (!file_exists($db)) {
                throw new Exception("$db não localizado.");
            } else {
                $io->note("Origem: $db");
            }

            /* executa o cálculo */
            $calculator = new ValeAlimCalculator($db);
            $result = $calculator->run();

            /* mostra resultado na tela */
            $io->note("Resultado do cálculo:");
            $io->writeln(sprintf("Data-base:\t\t\t%s", $result['dataBase']->format('d/m/Y')));
            $io->writeln('Entidades:');
            foreach ($result['entidades'] as $entidade) {
                $io->writeln(sprintf("\t%s", $entidade));
            }
            $io->writeln(sprintf("Dotação atualizada:\t\t\t%s", number_format($result['dotacao'], 2, ',', '.')));
            $io->writeln(sprintf("Empenhado até a data-base:\t\t%s", number_format($result['empenhado'], 2, ',', '.')));
            $io->writeln(sprintf("Saldo da dotação:\t\t\t%s", number_format($result['saldo'], 2, ',', '.')));
            $io->writeln(sprintf("Meses empenhados:\t\t\t%s", $result['mesesEmpenhados']));
            $io->writeln(sprintf("Média mensal empenhada:\t\t\t%s", number_format($result['media'], 2, ',', '.')));
            $io->writeln(sprintf("Meses a empenhar:\t\t\t%s", $result['mesesAEmpenhar']));
            $io->writeln(sprintf("Valor a empenhar:\t\t\t%s", number_format($result['valorAEmpenhar'], 2, ',', '.')));
            $io->writeln(sprintf("Suficiência/Insuficiência de dotação:\t%s", number_format($result['resultado'], 2, ',', '.')));

            /* salva o relatório em pdf se for o caso */
            if ($input->getOption('report')) {
                $io->note('Montando o relatório...');
                $loader = new FilesystemLoader('./tpl');
                $twig = new Environment($loader, ['cache' => false]);
                $template = $twig->load('report/vale-alim/calc.html.twig');
                $html = $template->render([
                    'dataBase' => $result['dataBase'],
                    'entidades' => $result['entidades'],
                    'dotacao' => $result['dotacao'],
                    'empenhado' => $result['empenhado'],
                    'saldo' => $result['saldo'],
                    'mesesEmpenhados' => $result['mesesEmpenhados'],
                    'media' => $result['media'],
                    'mesesAEmpenhar' => $result['mesesAEmpenhar'],
                    'valorAEmpenhar' => $result['valorAEmpenhar'],
                    'resultado' => $result['resultado']
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

            $io->success(sprintf("Cálculo do superavit/deficit da dotação de vale-alimentação finalizado em %s", date('d/m/Y H:i:s')));

            return 0;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
