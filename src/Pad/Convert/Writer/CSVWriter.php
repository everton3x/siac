<?php

namespace App\Pad\Convert\Writer;

use App\Pad\Convert\Reader\FileReader;
use DateTime;
use Exception;
use Symfony\Component\Config\Definition\Exception\Exception as Exception2;

/**
 * Writer para o formato CSV
 *
 * @author everton
 */
class CSVWriter implements WriterInterface {

    protected $path = '';
    protected $fhandle = null;
    protected $freader = null;

    public function __construct($destiny) {
        $this->path = $destiny;
    }

    public function create() {
        if (file_exists($this->path)) {
            throw new Exception(sprintf('O diretório %s já existe.', $this->path));
        }
        if (@mkdir($this->path, 0777, true) === false) {
            throw new Exception(sprintf('Falha ao criar o diretório %s.', $this->path));
        }
    }

    public function prepare(FileReader $freader) {
        $this->freader = $freader;

        $fname = sprintf('%s/%s.csv', $this->path, $this->freader->getBaseName());
        $exists = file_exists($fname);

        if (($this->fhandle = fopen($fname, 'a')) === false) {
            throw new Exception(sprintf('Não foi possível preparar %s em %s', $this->freader->getBaseName(), $this->path));
        }

        /* prepara a linha com os rótulos de colunas */
        if (!$exists) {
            $spec = $freader->getSpec()->getFieldSpec();
            $colNames = [];
            foreach ($spec as $fieldSpec) {
                $colNames[] = $fieldSpec['id'];
            }

            if (fputcsv($this->fhandle, $colNames, ';') === false) {
                throw new Exception(sprintf('Não foi possível adicionar os rótulos de colunas em %s em %s', $this->freader->getBaseName(), $this->path));
            }
        }
    }

    public function write(array $data) {
        $data = $this->formatFieldTypes($data);
        if (fputcsv($this->fhandle, $data, ';') == false) {
            throw new Exception(sprintf('Falha ao escrever a linha %s para %s em %s', join(';', $data), $this->freader, $this->path));
        }
    }

    protected function formatFieldTypes(array $data): array {
        foreach ($data as $key => $value) {
//            if(is_string($value)){
//                $data[$key] = sprintf('"%s"', $value);
//            }

            if (is_float($value)) {
                $data[$key] = number_format($value, 2, ',', '.');
            }

            if ($value instanceof DateTime) {
                $data[$key] = $value->format('d/m/Y');
            }
        }

        return $data;
    }

    public function commit() {
        if (fclose($this->fhandle) === false) {
            throw new Exception2(sprintf('Falha ao fechar %s em %s', $this->freader, $this->path));
        }
    }

    public function save() {
        //não faz nada
    }

    public function saveMetaData(
            string $filePath,
            string $baseName,
            string $cnpj,
            DateTime $initialDate,
            DateTime $finalDate,
            DateTime $generationDate,
            string $entityName,
            int $totalRows
    ) {
        try {
            $fmeta = "{$this->path}/meta.csv";

            if (!file_exists($fmeta)) {
                $header = [
                    'id',
                    'arquivo',
                    'cnpj',
                    'data_inicial',
                    'data_final',
                    'data_geracao',
                    'entidade',
                    'registros'
                ];
            } else {
                $header = false;
            }

            if (($fhandle = fopen($fmeta, 'a')) === false) {
                throw new Exception(sprintf('Não foi possível criar %s', $fmeta));
            }


            if ($header) {
                if (fputcsv($fhandle, $header, ';') == false) {
                    throw new Exception(sprintf('Falha ao salvar cabeçalho de %s para meta.csv em %s', $baseName, $this->path));
                }
            }

            $meta = [
                $baseName,
                $filePath,
                $cnpj,
                $initialDate->format('d/m/Y'),
                $finalDate->format('d/m/Y'),
                $generationDate->format('d/m/Y'),
                $entityName,
                $totalRows
            ];

            if (fputcsv($fhandle, $meta, ';') == false) {
                throw new Exception(sprintf('Falha ao salvar dados de %s para meta.csv em %s', $baseName, $this->path));
            }

            if (fclose($fhandle) === false) {
                throw new Exception(sprintf('Falha ao fechar meta.csv em %s', $this->path));
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
