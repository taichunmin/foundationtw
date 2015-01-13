<?php

$rootPath = dirname(__DIR__);

$counter = 0;

foreach (glob("{$rootPath}/output/details/*/*/*.json") AS $jsonFile) {
    $json = json_decode(file_get_contents($jsonFile), true);
    if (md5(implode('', array_keys($json))) !== 'f35ba01b5874b0a155ab639b434e9d5c') {
        unlink($jsonFile);
        ++$counter;
    }
}

echo "removed {$counter} files\n";
