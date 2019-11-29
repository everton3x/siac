<?php

use App\Command\CalcProgOrcCommand;
use App\Command\ValeCalcCommand;
use App\Command\PadConvertCommand;
use App\Command\PadSplitCommand;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

$application = new Application();

$application->addCommands([
    new PadConvertCommand(),
    new CalcProgOrcCommand(),
    new PadSplitCommand(),
    new ValeCalcCommand()
]);

$application->run();