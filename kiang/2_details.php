<?php

$rootPath = dirname(__DIR__);
$outputPath = "{$rootPath}/output/details";

foreach (glob("{$rootPath}/output/lists/*.csv") AS $csvFile) {
    $cFh = fopen($csvFile, 'r');
    fgets($cFh, 512);
    $pInfo = pathinfo($csvFile);
    while ($line = fgetcsv($cFh, 2048)) {
        $pos = strpos($line[9], '?') + 1;
        $posEnd = strpos($line[9], '&');
        $recordId = substr($line[9], $pos + 3, $posEnd - $pos - 3);
        echo "processing {$pInfo['filename']}/{$recordId}\n";
        $recordParts = array(
            substr($recordId, -3),
            $recordId,
        );
        $urls = array(
            'data' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K03.jsp?' . substr($line[9], $pos, $posEnd - $pos),
            'staff' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K04.jsp?' . substr($line[9], $posEnd + 1),
            'office' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K07.jsp?' . substr($line[9], $posEnd + 1),
        );
        foreach ($urls AS $fKey => $url) {
            $tmpPath = "{$rootPath}/tmp/{$pInfo['filename']}/{$fKey}/{$recordParts[0]}";
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }
            if (!file_exists("{$tmpPath}/{$recordParts[1]}")) {
                file_put_contents("{$tmpPath}/{$recordParts[1]}", mb_convert_encoding(file_get_contents($url), 'utf8', 'big5'));
            }
        }
    }
    fclose($cFh);
}