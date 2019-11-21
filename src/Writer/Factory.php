<?php

namespace App\Writer;

use Exception;
use InvalidArgumentException;

/**
 * Factory para seleção do writer adequado.
 *
 * @author everton
 */
class Factory {

    /**
     * Retorna um Writer de acordo com a extensão do destino.
     * 
     * @param string $destiny
     * @return WriterInterface
     */
    public static function createWriter(string $destiny): WriterInterface {
        try {
            switch (($ext = self::seekExtension($destiny))) {
                case 'CSV':
                    return new CSVWriter($destiny);
                    break;
//                case 'DB':
//                    break;
                default :
                    throw new InvalidArgumentException(sprintf('Não há suporte para o formato de destino %s', $ext));
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected static function seekExtension(string $destiny): string {
        $splitted = explode('.', $destiny);
        return strtoupper(array_pop($splitted));
    }

}
