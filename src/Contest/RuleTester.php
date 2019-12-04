<?php

namespace App\Contest;

use Exception;

/**
 * Tstador de regras individuais
 *
 * @author everton
 */
class RuleTester extends ContestTesterAbstract {

    public function __construct(string $db) {
        try {
            parent::__construct($db);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function run(string $rule): array {
        try {
            $ruleData = $this->loadRule($rule);
            if ($ruleData['active'] == false) {
//                var_dump($ruleData['active']);
//                exit();
                return [];
            }

            $result = [
                'name' => $rule,
                'title' => $ruleData['title'],
                'description' => $ruleData['description'],
            ];

            /* loop nos totais */
            $totalBase = 0.0;
            for ($i = 1; true; $i++) {
                $itotal = "total$i";
                if (!key_exists($itotal, $ruleData)) {
                    break;
                }

                $result['total'][$i] = [];
                $total = 0.0;
                /* loop nos valores de um total */
//                print_r($ruleData[$itotal]);exit();
                foreach ($ruleData[$itotal]['value'] as $ivalue => $sql) {
                    $sql = $this->prepareSQL($sql);
                    $result['total'][$i]['value'][$ivalue]['sql'] = $sql;
                    $value = $this->valorize($sql);
                    $result['total'][$i]['value'][$ivalue]['value'] = $value;
                    $total += $value;
                }//fim loop de um total
                if ($i === 1) {
                    $totalBase = $total;
                }
//                echo $i, '->', $total, '->',$totalBase, PHP_EOL;
                if ($total !== $totalBase) {
                    $result['total'][$i]['diference'] = round($totalBase - $total, 2);
                } else {
                    $result['total'][$i]['diference'] = 0.0;
                }
                $result['total'][$i]['total'] = $total;
            }//fim do loop nos totais

            return $result;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
