<?php

namespace App\Reader;

use DirectoryIterator;
use Exception;

/**
 * Reader para os arquivos txt do PAD.
 *
 * @author everton
 */
class Reader {

    /**
     *
     * @var array Coleção de objetos DirectoryIterator
     */
    protected $files = [];

    /**
     *
     * @var App\Reader\FileReader Armazena o FileReader corrente.
     */
//    protected $current = null;

    /**
     *
     * @var int Número total de registros.
     */
    protected $totalRows = 0;

    public function __construct(array $origins, string $specDir) {
        try {
            foreach ($origins as $originPath) {
                $directory = new DirectoryIterator($originPath);
                foreach ($directory as $item) {
                    if ($item->isFile() && ($item->getExtension() === 'txt' || $item->getExtension() === 'TXT')) {
                        $freader = new FileReader($item->getPathname());
                        if ($freader->loadSpecFrom($specDir)) {
                            $this->totalRows += $freader->getTotalRows();
                            $this->files[] = $freader;
                        }
//                        if(is_null($this->current)){
//                            $this->current = $freader;
//                        }
                    }
                }
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Lê uma linha do FileReader atual.
     * 
     * @return string Retorna a linha bruta mas sem a quebra de linha no final, ou uma string vazia se não tiver mais linhas.
     */
//    public function readLine(): string {
//        return $this->current->readLine();
//    }

    /**
     * Carrega e retorna o próximo FileReader.
     * 
     * @return FileReader|null
     */
    public function getFileReaders() {
//        $freader = $this->current;
//        $this->nextFileReader();
//        return $freader;
        return $this->files;
    }

    public function getNumRows(): int {
        return $this->totalRows;
    }

    /**
     * Avança para o próximo FileReader
     * @return bool
     */
//    public function nextFileReader(): bool {
//        if($this->current = next($this->files)){
//            return true;
//        }else{
//            return false;
//        }
//    }
}
