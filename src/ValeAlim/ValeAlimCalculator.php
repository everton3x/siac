<?php

namespace App\ValeAlim;

use DateTime;
use Exception;
use PDO;

/**
 * Calucla o superavit/deficit da dotação do vale-alimentação
 *
 * @author everton
 */
class ValeAlimCalculator {

    protected $pdo = null;

    public function __construct(string $db) {
        try {
            $this->pdo = new PDO("sqlite:$db");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function run(): array {
        try {
            $dataBase = $this->getDataBase();
            $entidades = $this->getEntidades();
            $dotAtualizada = $this->getDotAtualizada();
            $empenhado = $this->getEmpenhado();
            $meses = $dataBase->format('n');
            $media = round($empenhado / $meses, 2);
            $saldo = $dotAtualizada - $empenhado;
            $mesesAEmpenhar = 12 - $meses;
            $valorAEmpenhar = round($media * $mesesAEmpenhar, 2);
            $resultado = $saldo - $valorAEmpenhar;
            return [
                'dataBase' => $dataBase,
                'entidades' => $entidades,
                'dotacao' => $dotAtualizada,
                'empenhado' => $empenhado,
                'saldo' => $saldo,
                'media' => $media,
                'mesesEmpenhados' => $meses,
                'mesesAEmpenhar' => $mesesAEmpenhar,
                'valorAEmpenhar' => $valorAEmpenhar,
                'resultado' => $resultado
            ];
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getEmpenhado(): float {
        try {
            $stmt = $this->pdo->query("SELECT sum(empenhado) AS valor FROM bal_desp WHERE elemento_f LIKE '33 90 46'");
            $result = 0.0;
            foreach ($stmt as $row) {
                $result += $row['valor'];
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    protected function getDotAtualizada(): float {
        try {
            $stmt = $this->pdo->query("SELECT sum(prev_ate_termino) AS valor FROM bal_desp WHERE elemento_f LIKE '33 90 46'");
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
