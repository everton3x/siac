<?php

namespace App\Pad\Convert\Parser;

use App\Pad\Convert\Reader\FileReader;
use App\Pad\Convert\Reader\Reader;
use App\Pad\Convert\Reader\SpecReader;
use App\Pad\Convert\Writer\WriterInterface;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Parser dos arquivos
 *
 * @author everton
 */
class Parser {

    protected $reader = null;
    protected $writer = null;
    protected $io = null;
    protected $progressbar = null;

    public function __construct(Reader $reader, WriterInterface $writer, SymfonyStyle $io) {
        try {
            $this->reader = $reader;
            $this->writer = $writer;
            $this->io = $io;
            ProgressBar::setFormatDefinition('custom', '<info>%message%</info> [%bar%] %percent:3s%% [%current% / %max%]');
            $this->progressbar = $io->createProgressBar();
            $this->progressbar->setBarCharacter('|');
            $this->progressbar->setProgressCharacter('|');
            $this->progressbar->setEmptyBarCharacter(' ');
            $this->progressbar->setBarWidth(80);
            $this->progressbar->setFormat('custom');
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function run() {
        try {
            $this->progressbar->setMessage(str_pad("Processando...", 20, ' ', STR_PAD_RIGHT));
            $this->progressbar->start($this->reader->getNumRows());
            $this->initWriter();
        } catch (Exception $ex) {
            throw $ex;
        }

        try {
            foreach ($this->reader->getFileReaders() as $freader) {
//                echo $freader->getBaseName(), PHP_EOL;
                $this->processFileReader($freader);
            }//fim do loop nos freader
        } catch (Exception $ex) {
            throw $ex;
        }

        try {
            $this->finalizeWriter();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function finalizeWriter() {
        try {
            $this->writer->save();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function processFileReader(FileReader $freader) {
        try {

            try {
                $this->progressbar->setMessage(str_pad($freader->getBaseName(), 20, ' ', STR_PAD_RIGHT));
                
                $this->writer->saveMetaData(
                        $freader->getFilePath(),
                        $freader->getBaseName(),
                        $freader->getCNPJ(),
                        $freader->getInitialDate(),
                        $freader->getFinalDate(),
                        $freader->getGenerationDate(),
                        $freader->getEntityName(),
                        $freader->getTotalRows()
                );
            } catch (Exception $ex) {
                throw $ex;
            }

            if (!is_null($freader->getSpec())) {//processa o arquivo apenas se tiver especificação
                $this->writer->prepare($freader);
                $spec = $freader->getSpec();
                while (($line = $freader->readLine()) != false) {
                    $this->processLine($line, $spec);
                }

                $this->writer->commit();
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function processLine(string $line, SpecReader $spec) {

        try {
            $data = [];

            foreach ($spec->getFieldSpec() as $fieldSpec) {
                $id = $fieldSpec['id'];
                $type = $fieldSpec['type'];
                $start = $fieldSpec['start'];
                $size = $fieldSpec['size'];
                $transform = $fieldSpec['transform'];
                $start--; //necessário porque no php o primeiro caractere é 0
                $value = substr($line, $start, $size);
                if ($transform) {
                    $value = $transform($value);
                }
                $value = $this->setType($value, $type);
                $data[$id] = $value;
            }

            $this->writer->write($data);
            $this->progressbar->advance();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function setType($value, $type) {
        switch ($type) {
            case 'int':
                settype($value, 'int');
                break;
            case 'string':
            case 'text':
                settype($value, 'string');
                break;
            case 'float':
                settype($value, 'float');
                break;
            case 'date':
                settype($value, 'string');
                break;
            default :
                throw new Exception("Tipo de dados $type não suportado.");
        }
        return $value;
    }

    protected function initWriter() {
        try {
            $this->writer->create();
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
