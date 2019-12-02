<?php

namespace App\Pessoal;

use DateTime;
use Exception;
use PDO;

/**
 * Calcula o superavit/deficit da dotação de pessoal.
 *
 * @author everton
 */
class DotacaoCalculator {

    protected $pdo = null;
    protected $config = [];

    public function __construct(string $db) {
        try {
            $this->pdo = new PDO("sqlite:$db");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if (($this->config = parse_ini_file('pessoal-dotacao.ini', true)) === false) {
                throw new Exception("Falha ao ler configurações de pessoal-dotacao.ini");
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function calcUltimoMes(): array {
        try {
            $dataBase = $this->getDataBase();
            $entidades = $this->getEntidades();
            $dotacaoAtualizada = $this->getDotacaoAtualizada();
            $dotAtualizDeducoes = $this->getDotacaoAtualizadaDeducoes();
            $reserva = 0.0;
            $dotacaoLiquida = $dotacaoAtualizada - $reserva - $dotAtualizDeducoes;
            $empenhadoAteMes = $this->getEmpenhadoAteMes();
            $empenhadoAteMesDeducoes = $this->getEmpenhadoAteMesDeducoes();
            $empenhadoLiquido = $empenhadoAteMes - $empenhadoAteMesDeducoes;
            $mesesEmpenhados = $dataBase->format('n');
            $empenhadoUltimoMes = $this->getEmpenhadoUltimoMes();
            $empenhadoUltimoMesDeducoes = $this->getEmpenhadoUltimoMesDeducoes();
            $empenhadoUltimoMesLiquido = $empenhadoUltimoMes - $empenhadoUltimoMesDeducoes;
            $mesesAEmpenhar = 13 - $mesesEmpenhados;
            $valorAEmpenharUltimoMes =  $mesesAEmpenhar * $empenhadoUltimoMesLiquido;
            $resultadoUltimoMes = $dotacaoLiquida - $empenhadoLiquido - $valorAEmpenharUltimoMes;

            return [
                'dataBase' => $dataBase,
                'entidades' => $entidades,
                'dotacaoAtualizada' => $dotacaoAtualizada,
                'reserva' => $reserva,
                'dotAtualizDeducoes' => $dotAtualizDeducoes,
                'dotacaoLiquida' => $dotacaoLiquida,
                'empenhadoAteMes' => $empenhadoAteMes,
                'empenhadoAteMesDeducoes' => $empenhadoAteMesDeducoes,
                'empenhadoLiquido' => $empenhadoLiquido,
                'empenhadoUltimoMes' => $empenhadoUltimoMes,
                'empenhadoUltimoMesDeducoes' => $empenhadoUltimoMesDeducoes,
                'empenhadoUltimoMesLiquido' => $empenhadoUltimoMesLiquido,
                'mesesAEmpenhar' => $mesesAEmpenhar,
                'valorAEmpenharUltimoMes' => $valorAEmpenharUltimoMes,
                'resultadoUltimoMes' => $resultadoUltimoMes
            ];
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getEmpenhadoUltimoMesDeducoes(): float {
        try {
            $mes = $this->getDataBase()->format('n');
            $ano = $this->getDataBase()->format('Y');
            
            $result = 0.0;
            foreach ($this->config['deducao']['ndo'] as $key => $deducao) {
                $stmt = $this->pdo->query("SELECT sum(valor) AS valor FROM empenho WHERE ndo_f LIKE '{$deducao}' AND mes = $mes AND ano = $ano");
                foreach ($stmt as $row) {
                    $result += $row['valor'];
                }
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getEmpenhadoUltimoMes(): float {
        try {
            $mes = $this->getDataBase()->format('n');
            $ano = $this->getDataBase()->format('Y');
            
            $stmt = $this->pdo->query("SELECT sum(valor) AS valor FROM empenho WHERE ndo_f LIKE '31%' AND mes = $mes AND ano = $ano");
            $result = 0.0;
            foreach ($stmt as $row) {
                $result += $row['valor'];
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function calcMediaMensal(): array {
        try {
            $dataBase = $this->getDataBase();
            $entidades = $this->getEntidades();
            $dotacaoAtualizada = $this->getDotacaoAtualizada();
            $dotAtualizDeducoes = $this->getDotacaoAtualizadaDeducoes();
            $reserva = 0.0;
            $dotacaoLiquida = $dotacaoAtualizada - $reserva - $dotAtualizDeducoes;
            $empenhadoAteMes = $this->getEmpenhadoAteMes();
            $empenhadoAteMesDeducoes = $this->getEmpenhadoAteMesDeducoes();
            $empenhadoLiquido = $empenhadoAteMes - $empenhadoAteMesDeducoes;
            $mesesEmpenhados = $dataBase->format('n');
            $mediaMensal = round(($empenhadoAteMes - $empenhadoAteMesDeducoes) / $mesesEmpenhados, 2);
            $mesesAEmpenhar = 13 - $mesesEmpenhados;
            $valorAEmpenharMedia =  $mesesAEmpenhar * $mediaMensal;
            $resultadoMedia = $dotacaoLiquida - $empenhadoLiquido - $valorAEmpenharMedia;

            return [
                'dataBase' => $dataBase,
                'entidades' => $entidades,
                'dotacaoAtualizada' => $dotacaoAtualizada,
                'reserva' => $reserva,
                'dotAtualizDeducoes' => $dotAtualizDeducoes,
                'dotacaoLiquida' => $dotacaoLiquida,
                'empenhadoAteMes' => $empenhadoAteMes,
                'empenhadoAteMesDeducoes' => $empenhadoAteMesDeducoes,
                'empenhadoLiquido' => $empenhadoLiquido,
                'mesesEmpenhados' => $mesesEmpenhados,
                'mediaMensal' => $mediaMensal,
                'mesesAEmpenharMedia' => $mesesAEmpenhar,
                'valorAEmpenharMedia' => $valorAEmpenharMedia,
                'resultadoMedia' => $resultadoMedia
            ];
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getEmpenhadoAteMesDeducoes(): float {
        try {
            $ano = $this->getDataBase()->format('Y');
            $result = 0.0;
            foreach ($this->config['deducao']['ndo'] as $key => $deducao) {
                $stmt = $this->pdo->query("SELECT sum(valor) AS valor FROM empenho WHERE ndo_f LIKE '{$deducao}' AND ano = $ano");
                foreach ($stmt as $row) {
                    $result += $row['valor'];
                }
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getEmpenhadoAteMes(): float {
        try {
            $ano = $this->getDataBase()->format('Y');
            $stmt = $this->pdo->query("SELECT sum(valor) AS valor FROM empenho WHERE ndo_f LIKE '31%' AND ano = $ano");
            $result = 0.0;
            foreach ($stmt as $row) {
                $result += $row['valor'];
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getDotacaoAtualizadaDeducoes(): float {
        try {
            $result = 0.0;
            foreach ($this->config['deducao']['ndo'] as $key => $deducao) {
                $stmt = $this->pdo->query("SELECT sum(prev_ate_termino) AS valor FROM bal_desp WHERE elemento_f LIKE '{$deducao}'");
                foreach ($stmt as $row) {
                    $result += $row['valor'];
                }
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getDotacaoAtualizada(): float {
        try {
            $stmt = $this->pdo->query("SELECT sum(prev_ate_termino) AS valor FROM bal_desp WHERE elemento_f LIKE '31%'");
            $result = 0.0;
            foreach ($stmt as $row) {
                $result += $row['valor'];
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getEntidades(): array {
        try {
            $stmt = $this->pdo->query("SELECT cnpj, entidade FROM meta GROUP BY cnpj, entidade");
            $entidades = [];
            foreach ($stmt as $row) {
                $entidades[$row['cnpj']] = $row['entidade'];
            }
            return $entidades;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getDataBase(): DateTime {
        try {
            $stmt = $this->pdo->query("SELECT data_final FROM meta GROUP BY data_final");
            foreach ($stmt as $row) {
                return date_create_from_format('Y-m-d', $row['data_final']);
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
