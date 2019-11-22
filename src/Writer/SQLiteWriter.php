<?php

namespace App\Writer;

use App\Reader\FileReader;
use DateTime;
use Exception;
use Symfony\Component\Config\Definition\Exception\Exception as Exception2;

/**
 * Writer para o formato SQLite3
 *
 * @author everton
 */
class SQLiteWriter implements WriterInterface {

    protected $path = '';
    protected $fhandle = null;
    protected $freader = '';

    public function __construct($destiny) {
        $this->path = $destiny;
    }

    public function create() {
        
    }

    public function prepare(FileReader $freader) {
        $this->freader = $freader;

        
    }

    public function write(array $data) {
        
    }

    public function commit() {
        
    }

    public function save() {
        
    }

    public function saveMetaData(string $filePath, string $baseName, string $cnpj, DateTime $initialDate, DateTime $finalDate, DateTime $generationDate, string $entityName, int $totalRows) {
        
    }

}
