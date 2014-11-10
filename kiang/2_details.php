<?php

$rootPath = dirname(__DIR__);
include_once $rootPath . '/lib/cns11643/scripts/big5e_to_utf8.php';
$outputPath = "{$rootPath}/output/details";

foreach (glob("{$rootPath}/output/lists/*.csv") AS $csvFile) {
    $cFh = fopen($csvFile, 'r');
    fgets($cFh, 512);
    $pInfo = pathinfo($csvFile);
    while ($line = fgetcsv($cFh, 2048)) {
        $pos = strpos($line[9], '?') + 1;
        $posEnd = strpos($line[9], '&');
        $recordId = substr($line[9], $pos + 3, $posEnd - $pos - 3);
        $record = array();
        echo "processing {$pInfo['filename']}/{$recordId}\n";
        $recordPathPrefix = substr($recordId, -3);
        $recordPath = "{$outputPath}/{$pInfo['filename']}/{$recordPathPrefix}";
        $recordFile = "{$recordPath}/{$recordId}.json";
        if (!file_exists($recordFile)) {
            if (!file_exists($recordPath)) {
                mkdir($recordPath, 0777, true);
            }
            $urls = array(
                'data' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K03.jsp?' . substr($line[9], $pos, $posEnd - $pos),
                'staff' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K04.jsp?' . substr($line[9], $posEnd + 1),
                'office' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K07.jsp?' . substr($line[9], $posEnd + 1),
            );
            foreach ($urls AS $fKey => $url) {
                $tmpPath = "{$rootPath}/tmp/{$pInfo['filename']}/{$fKey}/{$recordPathPrefix}";
                if (!file_exists($tmpPath)) {
                    mkdir($tmpPath, 0777, true);
                }
                if (!file_exists("{$tmpPath}/{$recordId}")) {
                    file_put_contents("{$tmpPath}/{$recordId}", file_get_contents($url));
                }
                $page = file_get_contents("{$tmpPath}/{$recordId}");
                $page = Converter::iconv($page, 1);
                switch ($fKey) {
                    case 'data':
                        $pos = strpos($page, '<td width="8%" class="head">') + 28;
                        while (false !== $pos) {
                            $posEnd = strpos($page, '</td>', $pos) + 1;
                            $posEnd = strpos($page, '</td>', $posEnd);
                            $pair = explode('</td>', substr($page, $pos, $posEnd - $pos));
                            foreach ($pair AS $k => $v) {
                                $pair[$k] = trim(preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', strip_tags($v)));
                                $pair[$k] = str_replace(array('&nbsp;', ' ', "\n", "\r"), '', $pair[$k]);
                            }
                            $record[$pair[0]] = $pair[1];
                            $pos = strpos($page, 'class="head">', $posEnd);
                            if (false !== $pos) {
                                $pos += 13;
                            }
                        }
                        break;
                    case 'staff':
                        $record['董監事'] = array();
                        $pos = strpos($page, '姓名</td>');
                        $table = substr($page, $pos, strpos($page, '</table') - $pos);
                        $tLines = explode('</tr>', $table);
                        foreach ($tLines AS $tLine) {
                            $tCols = explode('</td>', $tLine);
                            if (count($tCols) === 4) {
                                $tCols[1] = str_replace(array(' ', '　'), '', trim(strip_tags($tCols[1])));
                                $tCols[2] = str_replace(array(' ', '　'), '', trim(strip_tags($tCols[2])));
                                $record['董監事'][] = array(
                                    $tCols[1], $tCols[2]
                                );
                            }
                        }
                        break;
                    case 'office':
                        $record['分事務所'] = array();
                        $pos = strpos($page, '分事務所地址</td>');
                        $table = substr($page, $pos, strpos($page, '</table') - $pos);
                        $tLines = explode('</tr>', $table);
                        foreach ($tLines AS $tLine) {
                            $tCols = explode('</td>', $tLine);
                            if (count($tCols) === 3) {
                                $tCols[1] = trim(strip_tags($tCols[1]));
                                $record['分事務所'][] = $tCols[1];
                            }
                        }
                        break;
                }
            }
            file_put_contents($recordFile, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
    fclose($cFh);
}