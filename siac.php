<?php

use App\Command\CalcProgOrcCommand;
use App\Command\ContestNewProfileCommand;
use App\Command\ContestNewRuleCommand;
use App\Command\ContestRunCommand;
use App\Command\ContestShowProfilesCommand;
use App\Command\ContestShowRulesCommand;
use App\Command\PadConvertCommand;
use App\Command\PadSplitCommand;
use App\Command\PessoalDotacaoCommand;
use App\Command\ValeCalcCommand;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

$application = new Application();

$application->addCommands([
    new PadConvertCommand(),
    new CalcProgOrcCommand(),
    new PadSplitCommand(),
    new ValeCalcCommand(),
    new PessoalDotacaoCommand(),
    new ContestNewRuleCommand(),
    new ContestNewProfileCommand(),
    new ContestRunCommand(),
    new ContestShowProfilesCommand(),
    new ContestShowRulesCommand()
]);

$application->run();