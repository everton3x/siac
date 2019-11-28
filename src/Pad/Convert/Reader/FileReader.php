<?php

namespace App\Pad\Convert\Reader;

use DateTime;
use Exception;
use UnexpectedValueException;

/**
 * Reader para arquivos individuais
 *
 * @author everton
 */
class FileReader {

    /**
     *
     * @var resource Armazena o resource obtido por fopen().
     */
    protected $fhandle = null;
    protected $path = '';
    protected $cnpj = '';
    protected $generationDate = null;
    protected $initalDate = null;
    protected $finalDate = null;
    protected $entityName = '';
    protected $totalRows = 0;
    protected $baseName = '';
    protected $spec = null;

    public function __construct(string $filePath) {
        try {
            if (($this->fhandle = fopen($filePath, 'r')) === false) {
                throw new UnexpectedValueException(sprintf('Não foi possível abrir %s', $filePath));
            }

            $this->path = $filePath;
            
            $this->baseName = basename(strtolower($filePath), '.txt');

            $this->readMetaData();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function loadSpecFrom(string $specPath): bool {
        $specFile = sprintf('%s%s.xml', $specPath, $this->baseName);
        if (SpecReader::hasSpec($specFile) === false) {
            return false;
        }

        try {
            $this->spec = new SpecReader($specFile);
            return true;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function getSpec(): ?SpecReader {
        return $this->spec;
    }

    /**
     * Processa o arquivo buscando meta-dados.
     * 
     * @return void
     */
    protected function readMetaData(): void {
        $headerLine = $this->getHeaderLine();
        $this->cnpj = substr($headerLine, 0, 14);
        $this->initalDate = $this->strToDate(substr($headerLine, 14, 8));
        $this->finalDate = $this->strToDate(substr($headerLine, 22, 8));
        $this->generationDate = $this->strToDate(substr($headerLine, 30, 8));
        $this->entityName = trim(substr($headerLine, 38, 80));
        $this->totalRows = (int) substr($this->seekFinalLine(), 11);

        //reseta o ponteiro para a linha 1
        if (rewind($this->fhandle) === false) {
            throw new RuntimeException(sprintf('Não foi possível resetar o ponteiro para %s', $this->path));
        } else {
            fgets($this->fhandle);
        }
    }

    protected function seekFinalLine(): string {
        while (($buffer = fgets($this->fhandle)) !== false) {
            if (substr($buffer, 0, 11) === 'FINALIZADOR') {
                return trim($buffer);
            }
        }

        throw new \RuntimeException(sprintf('Não foi encontrada linha FINALIZADOR PARA O arquivo %s', $this->path));
    }

    protected function strToDate(string $strDate): DateTime {
//        echo $strDate, PHP_EOL;
        try {
            return date_create_from_format('dmY', $strDate);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function getHeaderLine(): string {
        return trim(fgets($this->fhandle));
    }

    public function getCNPJ(): string {
        return $this->cnpj;
    }

    public function getGenerationDate(): DateTime {
        return $this->generationDate;
    }

    public function getInitialDate(): DateTime {
        return $this->initalDate;
    }

    public function getFinalDate(): DateTime {
        return $this->finalDate;
    }

    public function getEntityName(): string {
        return $this->entityName;
    }

    public function getBaseName(): string {
        // empenho.txt -> empenho
        return $this->baseName;
    }

    public function getTotalRows(): int {
        return $this->totalRows;
    }
    
    public function getFilePath(): string {
        return $this->path;
    }

    public function readLine(): string {
        $buffer = trim(fgets($this->fhandle));
        if (substr($buffer, 0, 11) === 'FINALIZADOR') {
            return '';
        }
        return $buffer;
    }

}
