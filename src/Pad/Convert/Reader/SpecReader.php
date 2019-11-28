<?php

namespace App\Pad\Convert\Reader;

use Exception;

/**
 * REader para as especificações de conversão
 *
 * @author everton
 */
class SpecReader {
    protected $path = '';
    protected $spec = null;
    
    public function __construct(string $path) {
        $this->path = $path;
        
        try{
            $this->spec = simplexml_load_file($path);
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public static function hasSpec(string $path): bool {
        return file_exists($path);
    }

    public function getFieldSpec(): array {
        $spec = [];
        foreach ($this->spec->field as $fieldSpec){
            $spec[(string) $fieldSpec['id']] = [
                'id' => (string) $fieldSpec['id'],
                'type' => (string) $fieldSpec['type'],
                'start' => (int) $fieldSpec['start'],
                'size' => (int) $fieldSpec['size'],
                'transform' => (string) $fieldSpec['transform']
            ];
        }
        return $spec;
    }
}
