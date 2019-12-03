<?php

namespace App\Contest;

use DateTime;
use Exception;
use PDO;

/**
 * Implementação abstrata de um tstador
 *
 * @author everton
 */
abstract class ContestTesterAbstract {

    protected $pdo = null;
    
    protected $dataBase = null;

    abstract public function run(string $rule): array;

    public function __construct(string $db) {
        try {
            $this->pdo = new PDO("sqlite:$db");
            $stmt = $this->pdo->query("SELECT data_final FROM meta GROUP BY data_final");
            foreach($stmt as $row){
                $this->dataBase = date_create_from_format('Y-m-d', $row['data_final']);
                break;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public static function getRuleDir(): string {
        return 'contest/rules/';
    }

    public static function getProfileDir(): string {
        return 'contest/profiles/';
    }

    protected function loadRule(string $rule): array {
        $frule = self::getRuleDir() . $rule . '.ini';
        if (file_exists($frule) === false) {
            throw new Exception("$frule não existe.");
        }
        if (($data = parse_ini_file($frule, true)) === false) {
            throw new Exception("Falha ao carregar $frule");
        }

        return $data;
    }
    
    protected function valorize(string $sql): float {
        try{
            $ano = $this->dataBase->format('Y');
            $stmt = $this->pdo->query($sql);
            $result = 0.0;
            foreach ($stmt as $row){
                $result += $row['valor'];
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public function getDataBase(): DateTime {
        return $this->dataBase;
    }
    
    public function getEntidades(): array {
        try{
            if(($config = parse_ini_file('pad-split.ini', true)) === false){
                throw new Exception("Falha ao carregar configurações de pad-split.ini");
            }
            
            $entidades = [];
            
            $stmt = $this->pdo->query('SELECT uniorcam FROM bal_ver GROUP BY uniorcam');
            foreach ($stmt as $row){
                switch ($row['uniorcam']){
                    case $config['UniOrcamEntidades']['cm']:
                        $entidades[$config['CnpjEntidades']['cm']] = 'Câmara de Vereadores';
                        break;
                    case $config['UniOrcamEntidades']['rpps']:
                        $entidades[$config['CnpjEntidades']['rpps']] = 'RPPS';
                        break;
                    default :
                        $entidades[$config['CnpjEntidades']['pm']] = 'Prefeitura';
                }
            }
            return $entidades;
        } catch (Exception $ex) {
            throw $ex;
        }
    }
    
    public static function getRules(): array {
        try{
            $result = [];
            $it = new \DirectoryIterator(self::getRuleDir());
            foreach ($it as $item){
//                echo $item->getPathname(), PHP_EOL;
                if($item->isFile()){
                    $result[] = $item->getBasename(".{$item->getExtension()}");
                }
            }
            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
