<?php

use App\Command\CalcProgOrcCommand;
use App\Command\PadConvertCommand;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

$application = new Application();

$application->addCommands([
    new PadConvertCommand(),
    new CalcProgOrcCommand()
]);

$application->run();