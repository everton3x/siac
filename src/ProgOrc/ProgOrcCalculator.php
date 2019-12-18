<?php

namespace App\ProgOrc;

use DateTime;
use Exception;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Executa a avaliação da programação financeira
 *
 * @author everton
 */
class ProgOrcCalculator {

    /**
     *
     * @var \PDO Banco de dados criado com siac pad:convert
     */
    protected $pdo = null;

    /**
     *
     * @var Array Resultado da valiação
     */
    protected $result = [];
    protected $io = null;

    /**
     * 
     * @param string $db Caminho para o banco de dados criado com siac pad:convert
     * @throws Exception
     */
    public function __construct(string $db, SymfonyStyle $io) {
        try {
            $this->pdo = new PDO("sqlite:$db");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->io = $io;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Calcula a avaliação da programação
     */
    public function run() {
        try {
            $vinculos = $this->getVinculos();
//            print_r($vinculos);exit();

            ProgressBar::setFormatDefinition('custom', '<info>%message%</info> [%bar%] %percent:3s%% [%current% / %max%]');
            $progress = $this->io->createProgressBar(count($vinculos));
            $progress->setBarCharacter('|');
            $progress->setProgressCharacter('|');
            $progress->setEmptyBarCharacter(' ');
            $progress->setBarWidth(80);
            $progress->setFormat('custom');
            $progress->setMessage('Calculando...');
            $progress->start();

            foreach ($vinculos as $row) {
                $progress->setMessage("Recurso {$row['cod']}: ");
                $result = $this->calc((int) $row['cod']);
                if ($this->check($result)) {
                    $this->result[] = $result;
                }
                $progress->advance();
            }//fim do loop dos vínculos
            $progress->finish();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Retorna a data de geração dos arquivos.
     * 
     * @param bool $format
     * @return string[YYYY-mm-dd]|DateTime
     * @throws Exception
     */
    public function getDataBase(bool $format = false) {
        try {
            $stmt = $this->pdo->query("SELECT data_final FROM meta");
            foreach ($stmt as $row) {
                $dt = \date_create_from_format('Y-m-d', $row['data_final']);
                if ($format) {
                    return $dt->format('Y-m-d');
                } else {
                    return $dt;
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Retorna o número do mês de uma data.
     * @param DateTime $dt
     * @return int
     */
    protected function getMes(DateTime $dt): int {
        return $dt->format('n');
    }

    /**
     * Retorna o ano para uma data.
     * @param DateTime $dt
     * @return int
     */
    protected function getAno(DateTime $dt): int {
        return $dt->format('Y');
    }

    /**
     * Retorna o bimestre de uma data.
     * @param int $mes
     * @return int
     */
    protected function getBimestre(int $mes): int {

        $bim = [
            1 => 1,
            2 => 1,
            3 => 2,
            4 => 2,
            5 => 3,
            6 => 3,
            7 => 4,
            8 => 4,
            9 => 5,
            10 => 5,
            11 => 6,
            12 => 6,
        ];

        return $bim[$mes];
    }

    /**
     * Calcula a avaliação.
     * 
     * @param int $rv Código do vínculo
     * @return array
     */
    protected function calc(int $rv): array {
        try {
            $result = [
                'rv' => $rv,
                'saldoFinanceiro' => $this->getSaldoFinanceiro($rv),
                'aArrecadar' => $this->getAArrecadar($rv),
                'aEmpenhar' => $this->getAEmpenhar($rv),
                'limitado' => $this->getLimitado($rv),
                'recomposto' => $this->getRecomposto($rv),
                'aLiquidar' => $this->getALiquidar($rv),
                'aPagar' => $this->getAPagar($rv),
                'restosAPagar' => $this->getRestosAPagar($rv),
                'extraAPagar' => $this->getExtraAPagar($rv),
            ];
        } catch (Exception $ex) {
            throw $ex;
        }

        return $result;
    }

    protected function getRestosAPagar(int $rv): float {
        try {
            $result = 0.0;
//            $rpEmpenhado = 0.0;
//            $rpPago = 0.0;
//            $pago = 0.0;
//            $sql = "SELECT SUM(valor) AS valor FROM empenho WHERE rv = $rv AND ano_empenho <> {$this->getAno($this->getDataBase())}";
//            $calc = $this->pdo->query($sql);
//            foreach ($calc as $row) {
//                $rpEmpenhado += (float) $row['valor'];
//            }
//            $sql = "SELECT SUM(pagament.valor) AS valor FROM pagament, empenho WHERE empenho.rv = $rv AND empenho.ano_empenho <> {$this->getAno($this->getDataBase())} AND pagament.nr_empenho = empenho.nr_empenho";
//            $calc = $this->pdo->query($sql);
//            foreach ($calc as $row) {
//                $rpPago += (float) $row['valor'];
//            }
//
//            $result = $rpEmpenhado - $rpPago;
            $empenhadoTotal = 0.0;
            $empenhadoAno = 0.0;
            $pagoTotal = 0.0;
            $pagoAno = 0.0;
            $sql = "SELECT SUM(valor) AS valor FROM empenho WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $empenhadoTotal += (float) $row['valor'];
            }
            $sql = "SELECT SUM(empenhado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $empenhadoAno += (float) $row['valor'];
            }
            $sql = "SELECT SUM(pagament.valor) AS valor FROM pagament WHERE $rv = (SELECT rv FROM empenho WHERE empenho.nr_empenho = pagament.nr_empenho)";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $pagoTotal += (float) $row['valor'];
            }
            $sql = "SELECT SUM(pago) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $pagoAno += (float) $row['valor'];
            }
            $result = ($empenhadoTotal - $pagoTotal) - ($empenhadoAno - $pagoAno);
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getAPagar(int $rv): float {
        try {
            $result = 0.0;
            $liquidado = 0.0;
            $pago = 0.0;
            $sql = "SELECT SUM(liquidado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $liquidado += (float) $row['valor'];
            }
            $sql = "SELECT SUM(pago) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $pago += (float) $row['valor'];
            }

            $result = $liquidado - $pago;
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getALiquidar(int $rv): float {
        try {
            $result = 0.0;
            $liquidado = 0.0;
            $empenhado = 0.0;
            $sql = "SELECT SUM(liquidado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $liquidado += (float) $row['valor'];
            }
            $sql = "SELECT SUM(empenhado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $empenhado += (float) $row['valor'];
            }

            $result = $empenhado - $liquidado;
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getLimitado(int $rv): float {
        try {
            $result = 0.0;

            $sql = "SELECT SUM(limitado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $result += (float) $row['valor'];
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getRecomposto(int $rv): float {
        try {
            $result = 0.0;

            $sql = "SELECT SUM(limitado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $result += (float) $row['valor'];
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getAEmpenhar(int $rv): float {
        try {
            $result = 0.0;
            $dotAtualizada = 0.0;
            $empenhado = 0.0;
            $sql = "SELECT SUM(prev_ate_termino) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $dotAtualizada += (float) $row['valor'];
            }
            $sql = "SELECT SUM(empenhado) AS valor FROM bal_desp WHERE rv = $rv";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $empenhado += (float) $row['valor'];
            }

            $result = $dotAtualizada - $empenhado;
        } catch (Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    protected function getAArrecadar(int $rv): float {
        try {
            $result = 0.0;
            $atualizada = 0.0;
            $arrecadada = 0.0;
            $areceber = 0.0;

            $sql = "SELECT SUM(atualizada) AS valor FROM bal_rec WHERE rv = $rv AND tipo_nivel LIKE 'A'";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $atualizada += (float) $row['valor'];
            }

            $sql = "SELECT SUM(realizada) AS valor FROM bal_rec WHERE rv = $rv AND tipo_nivel LIKE 'A'";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $arrecadada += (float) $row['valor'];
            }

            //soma as transferências a receber
            $rvStr = str_pad($rv, 4, '0', STR_PAD_LEFT);
            $sql = "SELECT SUM(saldo_final_debito) AS valor FROM bal_ver WHERE conta LIKE '%RV$rvStr%' AND conta_contabil_f LIKE '1.1.2.3.%' AND escrituracao IN('S', 's')";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $areceber += (float) $row['valor'];
            }

            if ($atualizada < $arrecadada) {
                $atualizada = $arrecadada + $this->getAArrecadarPelaMeta($rv);
            }
            $result = $atualizada - $arrecadada + $areceber;
            if ($result < 0) {
                $result = 0.0;
            }

            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getAArrecadarPelaMeta(int $rv): float {
        try {
            $result = 0.0;
            $mes = $this->getMes($this->getDataBase());

            for ($mes++; $mes <= 12; $mes++) {
                $bimestre = $this->getBimestre($mes);
                $sql = "SELECT SUM(meta{$bimestre}bim) AS valor FROM receita WHERE rv = $rv";
                $calc = $this->pdo->query($sql);
                foreach ($calc as $row) {
                    $result += (float) round($row['valor'] / 2, 2);
                }
            }

        } catch (Exception $ex) {
            throw $ex;
        }

        return $result;
    }

//    protected function getAArrecadar(int $rv): float {
//        try {
//            $result = 0.0;
//            $mes = $this->getMes($this->getDataBase());
//            $bimestre = $this->getBimestre($mes);
//            $meses = [
//                1 => 'janeiro',
//                2 => 'fevereiro',
//                3 => 'marco',
//                4 => 'abril',
//                5 => 'maio',
//                6 => 'junho',
//                7 => 'julho',
//                8 => 'agosto',
//                9 => 'setembro',
//                10 => 'outubro',
//                11 => 'novembro',
//                12 => 'dezembro',
//            ];
//
//            /* soma as metas de arrecadação dos bimestres seguintes ao mês de referência */
//            $bimAtual = $this->getBimestre($mes);
//            for ($bimAtual++; $bimAtual <= 6; $bimAtual++) {
//                $sql = "SELECT SUM(meta{$bimAtual}bim) AS valor FROM receita WHERE rv = $rv";
//                $calc = $this->pdo->query($sql);
//                foreach ($calc as $row) {
//                    $result += (float) $row['valor'];
//                }
//            }
//
//            /* soma o valor a arrecadar do bimestre atual, se houver */
//            if (($mes % 2) != 0) {
//                $arrecadacaoMesAtual = 0.0;
//                //pega a arrecadação do mês atual
//                $sql = "SELECT SUM($mes) AS valor FROM receita WHERE rv = $rv";
//                $calc = $this->pdo->query($sql);
//                foreach ($calc as $row) {
//                    $arrecadacaoMesAtual += (float) $row['valor'];
//                }
//
//                //pega a meta de arrecadação do bimestre atual
//                $metaBimAtual = 0.0;
//                $sql = "SELECT SUM(meta{$this->getBimestre($mes)}bim) AS valor FROM receita WHERE rv = $rv";
//                $calc = $this->pdo->query($sql);
//                foreach ($calc as $row) {
//                    $metaBimAtual += (float) $row['valor'];
//                }
//
//                //soma apenas se a ainda tiver meta a arrecadar
//                if ($metaBimAtual > $arrecadacaoMesAtual) {
//                    $result += $metaBimAtual - $arrecadacaoMesAtual;
//                }
//
//            }
//            //soma as transferências a receber
//            $rvStr = str_pad($rv, 4, '0', STR_PAD_LEFT);
//            $sql = "SELECT SUM(saldo_final_debito) AS valor FROM bal_ver WHERE conta LIKE '%RV$rvStr%' AND conta_contabil_f LIKE '1.1.2.3.%' AND escrituracao IN('S', 's')";
//            $calc = $this->pdo->query($sql);
//            foreach ($calc as $row) {
//                $result += (float) $row['valor'];
//            }
//        } catch (Exception $ex) {
//            throw $ex;
//        }
//
//        return $result;
//    }

    protected function getSaldoFinanceiro(int $rv): float {
        try {
            $result = 0.0;
            $sql = "SELECT (SUM(saldo_final_debito) - SUM(saldo_final_credito)) AS valor FROM bal_ver WHERE escrituracao IN('S', 's') AND superavit_financ IN('F', 'f') AND rv = $rv AND (conta_contabil_f LIKE '1.1.1.1.1.01.%' OR conta_contabil_f LIKE '1.1.1.1.1.06.%' OR conta_contabil_f LIKE '1.1.1.1.1.19.%' OR conta_contabil_f LIKE '1.1.1.1.1.50.%' OR conta_contabil_f LIKE '1.1.4.%')";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $result += (float) $row['valor'];
            }
        } catch (Exception $ex) {
            throw $ex;
        }

        return $result;
    }

    protected function getExtraAPagar(int $rv): float {
        try {
            $result = 0.0;
            $sql = "SELECT (SUM(saldo_final_credito) - SUM(saldo_final_debito)) AS valor FROM bal_ver WHERE escrituracao IN('S', 's') AND superavit_financ IN('F', 'f') AND rv = $rv AND conta_contabil_f LIKE '2.1.8.8.%'";
            $calc = $this->pdo->query($sql);
            foreach ($calc as $row) {
                $result += (float) $row['valor'];
            }
        } catch (Exception $ex) {
            throw $ex;
        }

        return $result;
    }

    /**
     * Verifica se o vínculo tem algum valor e pode ser incluído no relatório.
     * 
     * @param array $result
     * @return bool
     */
    protected function check(array $result): bool {
        return true;
    }

    protected function getVinculos(): array {
        try {
            $stmt = $this->pdo->query("SELECT * FROM recurso GROUP BY cod, nome, finalidade ORDER BY cod ASC");
            $vinculos = []; //necessário converter para array porque PDOStatement::rowCount() não funciona com o SQLite.
            foreach ($stmt->fetchAll() as $row) {
                $vinculos[] = $row;
            }
            return $vinculos;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function getResult(): array {
        return $this->result;
    }

    /**
     * Retira todas as linhas de resultado sem nenhum valor
     * @param array $data
     * @return array
     */
    public static function clearZeroLines(array $data): array {
        $soma = 0.0;
        $result = [];
        foreach ($data as $item) {
            $soma = $item['saldoFinanceiro'] + $item['aArrecadar'] + $item['aEmpenhar'] + $item['limitado'] + $item['recomposto'] + $item['aLiquidar'] + $item['aPagar'] + $item['restosAPagar'] + $item['extraAPagar'];
            if ($soma != 0.0) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function getEntidades(): array {
        $result = [];
        $sql = "SELECT * FROM meta GROUP BY entidade";
        $entidades = $this->pdo->query($sql);
        foreach ($entidades as $row) {
            $result[$row['cnpj']]['cnpj'] = $row['cnpj'];
            $result[$row['cnpj']]['nome'] = $row['entidade'];
        }

        return $result;
    }

    /**
     * Calcula o saldo final dos recursos 0001 Livres após retirar o valor comprometido com a cobertura dos demais recursos com saldo final negativo.
     * @param array $result
     * @return float
     */
    public function getSaldoLivre(array $result): float {
        $deficit = 0.0;
        foreach ($result as $item) {
            $saldoFinal = $this->getSaldoFinal($item);
            if ($item['rv'] === 1) {
                $saldoLivre = $saldoFinal;
            } else {
                if ($saldoFinal < 0) {
                    $deficit += ($saldoFinal * -1);
                }
            }
        }

        return $saldoLivre - $deficit;
    }

    protected function getSaldoFinal(array $item): float {
        return $item['saldoFinanceiro'] + $item['aArrecadar'] - $item['aEmpenhar'] + $item['limitado'] - $item['recomposto'] - $item['aLiquidar'] - $item['aPagar'] - $item['restosAPagar'] - $item['extraAPagar'];
    }

}
