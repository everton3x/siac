<?php

namespace App\Writer;

use App\Reader\FileReader;
use DateTime;

/**
 * Interface do Writer
 * 
 * @author everton
 */
interface WriterInterface {

    public function create();
    public function prepare(FileReader $freader);
    public function write(array $data);
    public function commit();
    public function save();
    public function saveMetaData(string $filePath, string $baseName, string $cnpj, DateTime $initialDate, DateTime $finalDate, DateTime $generationDate, string $entityName, int $totalRows);
}
