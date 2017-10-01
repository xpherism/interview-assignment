#!/usr/bin/env php
<?php

require __DIR__."/vendor/autoload.php";

use App\QueryCommand;
use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new QueryCommand());

$application->run();

?>