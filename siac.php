<?php

use App\Command\PadConvertCommand;
use Symfony\Component\Console\Application;

require 'vendor/autoload.php';

$application = new Application();

$application->addCommands([
    new PadConvertCommand()
]);

$application->run();