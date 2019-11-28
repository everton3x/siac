<?php

namespace App\Pad\Split;

use Exception;
use PDO;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Separa por entidades os arquivos agragados do PAD
 *
 * @author everton
 */
class Spliter {

    /**
     *
     * @var string Caminho para o arquivo *.db com os dados agragados
     */
    protected $dbPath = '';

    /**
     *
     * @var PDO Instância PDO de self::dbPath
     */
    protected $pdo = null;

    /**
     *
     * @var array configurações
     */
    protected $config = '';

    /**
     *
     * @var string Caminho onde serão salvos os dados.
     */
    protected $saveDir = '';

    /**
     *
     * @var string Caminho do banco de dados base.
     */
    protected $dbTemp = '';
    protected $io = null;

    public function __construct(string $dbPath, string $saveDir, string $config, SymfonyStyle $io) {
        $this->dbPath = $dbPath;
        $this->saveDir = $saveDir;
        $this->config = $config;
        $this->io = $io;
        $this->dbTemp = tempnam($this->saveDir, 'tmp');

        try {
            if (($this->config = parse_ini_file($config, true)) === false) {
                throw new Exception("Falha ao carregar configurações de $config");
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function connect(string $path) {
        try {
            $this->pdo = new PDO("sqlite:$path");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function run() {
        try {
            /* cria o banco base */
            $this->io->note(sprintf('Preparando arquivo de transição %s ...', $this->dbTemp));
            $this->prepare();

            /* separa dados da câmara */
            $this->io->note(sprintf('Segregando dados da Câmara para %s ...', $this->saveDir . 'cm.db'));
            $this->splitCMandRPPS($this->saveDir . 'cm.db', $this->config['UniOrcamEntidades']['cm']);

            /* separa dados do rpps */
            $this->io->note(sprintf('Segregando dados do RPPS para %s ...', $this->saveDir . 'rpps.db'));
            $this->splitCMandRPPS($this->saveDir . 'rpps.db', $this->config['UniOrcamEntidades']['rpps']);

            /* separa dados do executivo */
            $this->io->note(sprintf('Segregando dados da Prefeitura para %s ...', $this->saveDir . 'pm.db'));
            $this->splitPM($this->saveDir . 'pm.db', $this->config['UniOrcamEntidades']['cm'], $this->config['UniOrcamEntidades']['rpps']);
        } catch (Exception $ex) {
            @unlink($this->saveDir . 'rpps.db');
            @unlink($this->saveDir . 'cm.db');
            throw $ex;
        } finally {
            $this->io->note('Finalizando processamento...');
            $this->clean();
        }
    }

    protected function splitCMandRPPS(string $foutput, int $uniorcam) {
        try {
            if (!copy($this->dbTemp, $foutput)) {
                throw new Exception(sprintf("Não foi possível copiar %s para %s", $this->dbTemp, $foutput));
            }

            $this->connect($foutput);

            
            $this->pdo->exec("DELETE FROM liquidac WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam <> $uniorcam GROUP BY nr_empenho)");
            $this->pdo->exec("DELETE FROM pagament WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam <> $uniorcam GROUP BY nr_empenho)");

            $set = [
                'bal_desp' => 'uniorcam',
                'bal_rec' => 'uniorcam',
                'bal_ver' => 'uniorcam',
                'brec_ant' => 'uniorcam',
                'brub_ant' => 'uniorcam',
                'cta_disp' => 'uniorcam',
                'empenho' => 'uniorcam',
                'receita' => 'uniorcam',
                'tce_4111' => 'uniorcam'
            ];

            foreach ($set as $table => $field) {
                $this->pdo->exec("DELETE FROM $table WHERE $field <> $uniorcam");
            }
        } catch (Exception $ex) {
            if (!unlink($foutput)) {
                throw new Exception(sprintf("Não foi possível apagar %s", $foutput));
            }
            throw $ex;
        }
    }

    protected function splitPM(string $foutput, int $uniorcamCM, int $uniorcamRPPS) {
        try {
            if (!copy($this->dbTemp, $foutput)) {
                throw new Exception(sprintf("Não foi possível copiar %s para %s", $this->dbTemp, $foutput));
            }
            
            $this->connect($foutput);
            
            $this->pdo->exec("DELETE FROM liquidac WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam = $uniorcamCM GROUP BY nr_empenho)");
            $this->pdo->exec("DELETE FROM liquidac WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam = $uniorcamRPPS GROUP BY nr_empenho)");
            $this->pdo->exec("DELETE FROM pagament WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam = $uniorcamCM GROUP BY nr_empenho)");
            $this->pdo->exec("DELETE FROM pagament WHERE nr_empenho IN (SELECT nr_empenho FROM empenho WHERE uniorcam = $uniorcamRPPS GROUP BY nr_empenho)");

            $set = [
                'bal_desp' => 'uniorcam',
                'bal_rec' => 'uniorcam',
                'bal_ver' => 'uniorcam',
                'brec_ant' => 'uniorcam',
                'brub_ant' => 'uniorcam',
                'cta_disp' => 'uniorcam',
                'empenho' => 'uniorcam',
                'receita' => 'uniorcam',
                'tce_4111' => 'uniorcam'
            ];

            foreach ($set as $table => $field) {
                $this->pdo->exec("DELETE FROM $table WHERE $field = $uniorcamCM");
                $this->pdo->exec("DELETE FROM $table WHERE $field = $uniorcamRPPS");
            }

        } catch (Exception $ex) {
            if (!unlink($foutput)) {
                throw new Exception(sprintf("Não foi possível apagar %s", $foutput));
            }
            throw $ex;
        }
    }

    protected function prepare() {
        try {
            if (!copy($this->dbPath, $this->dbTemp)) {
                throw new Exception(sprintf("Não foi possível copiar %s para %s", $this->dbPath, $this->dbTemp));
            }

            /* exclui tabelas que não podem ser separadas */
            $this->connect($this->dbTemp);
            $this->pdo->exec("DROP TABLE IF EXISTS decreto");
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    protected function clean() {
        try {
            if (!unlink($this->dbTemp)) {
                throw new Exception(sprintf("Não foi possível apagar %s", $this->dbTemp));
            }
        } catch (Exception $ex) {
            throw $ex;
        }
    }

}
