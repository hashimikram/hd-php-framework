<?php
namespace HDFramework\src;

require_once 'HDAutoload.php';

const DS = DIRECTORY_SEPARATOR;

$hdAutoload = new HDAutoload();
$hdAutoload->register();
$hdAutoload->addNamespace('HDFramework\src', realpath(__DIR__));
$hdAutoload->addNamespace('HDFramework\libs', realpath(__DIR__ . DS . '..' . DS . 'libs'));

