<?php

use Wslim\Drdb\Drdb;

include '../bootstrap.php';

$drdb = new Drdb();
$result = $drdb->find('user', 2);
print_r($result);

