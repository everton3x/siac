<?php

namespace App\Pad\Convert\Writer;

use App\Pad\Convert\Reader\FileReader;
use DateTime;
use Exception;
use PDO;

/**
 * Writer para o formato SQLite3
 *
 * @author everton
 */
class SQLiteWriter implements WriterInterface {

    protected $path = '';
    protected $pdo = null;
    protected $freader = null;

    public function __construct($destiny) {
        $this->path = $destiny;
    }

    public function create() {
        try {
            $this->pdo = new PDO("sqlite:{$this->path}");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function prepare(FileReader $freader) {
        $this->freader = $freader;
        try {
            /* monta a spec das colunas */
            $coldef = [];
            $cols = $freader->getSpec()->getFieldSpec();
            foreach ($cols as $col) {
                $coltype = $this->translateColTypes($col['type']);
                $coldef[] = "{$col['id']} $coltype";
            }
            $coldef = join(', ', $coldef);
            $sql = "CREATE TABLE IF NOT EXISTS {$this->freader->getBaseName()} ($coldef)";

            $this->pdo->exec($sql);

            /* inicia a transação */
            $this->pdo->beginTransaction();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function translateColTypes(string $native): string {
        switch ($native) {
            case 'int':
                return 'INTEGER';
            case 'string':
                return 'TEXT';
            case 'text':
                return 'TEXT';
            case 'float':
//            return 'REAL';
//            return 'NUMERIC';
                return 'DECIMAL';
            case 'date':
                return 'TEXT';
            default :
                throw new Exception("Tipo de dados $native não suportado.");
        }
    }

    public function write(array $data) {
        try {
            $colslabel = array_map(function($value) {
                return ":$value";
            }, array_keys($data));
            $colnames = join(', ', array_keys($data));
            $colvalues = join(', ', array_values($colslabel));
            $data_prepared = [];
            foreach ($data as $k => $v) {
                $data_prepared[":$k"] = $v;
            }
            $sql = "INSERT INTO {$this->freader->getBaseName()} ($colnames) VALUES ($colvalues)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data_prepared);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function commit() {
        try {
            return $this->pdo->commit();
        } catch (Exception $ex) {
            $this->pdo->rollBack();
            throw $ex;
        }
    }

    public function save() {
        unset($this->pdo);
    }

    public function saveMetaData(string $filePath, string $baseName, string $cnpj, DateTime $initialDate, DateTime $finalDate, DateTime $generationDate, string $entityName, int $totalRows) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS meta (tabela TEXT, arquivo TEXT, cnpj TEXT, data_inicial INTEGER, data_final INTEGER, data_geracao INTEGER, entidade TEXT, registros INTEGER)";
            $this->pdo->exec($sql);

            $sql = "INSERT INTO meta (tabela, arquivo, cnpj, data_inicial, data_final, data_geracao, entidade, registros) VALUES ('$baseName', '$filePath', '$cnpj', '{$initialDate->format('Y-m-d')}', '{$finalDate->format('Y-m-d')}', '{$generationDate->format('Y-m-d')}', '$entityName', '$totalRows')";
            $this->pdo->exec($sql);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
