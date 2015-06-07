<?php

$rootPath = dirname(__DIR__);
include_once $rootPath . '/lib/cns11643/scripts/big5e_to_utf8.php';
$outputPath = "{$rootPath}/output/details";

foreach (glob("{$rootPath}/output/lists/*.csv") AS $csvFile) {
    $cFh = fopen($csvFile, 'r');
    fgets($cFh, 512);
    while ($line = fgetcsv($cFh, 2048)) {
        $urlParts = parse_url($line[9]);
        parse_str($urlParts['query'], $queryParts);
        $record = array();
        $recordId = str_replace('/', '-', $queryParts['ab']);
        $recordPath = "{$outputPath}/{$queryParts['ef']}/{$queryParts['bc']}/{$queryParts['de']}";
        $recordFile = "{$recordPath}/{$recordId}.json";
        if (!file_exists($recordFile)) {
            if (!file_exists($recordPath)) {
                mkdir($recordPath, 0777, true);
            }
            $urls = array(
                'data' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K03.jsp?' . $urlParts['query'],
                'staff' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K04.jsp?' . $urlParts['query'],
                'office' => 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K07.jsp?' . $urlParts['query'],
            );
            $record['url'] = $urls;
            $tmpPath = "{$rootPath}/tmp/{$queryParts['ef']}/{$queryParts['bc']}/{$queryParts['de']}";
            if (!file_exists($tmpPath)) {
                mkdir($tmpPath, 0777, true);
            }
            foreach ($urls AS $fKey => $url) {
                if (!file_exists("{$tmpPath}/{$recordId}_{$fKey}")) {
                    echo "getting {$url}\n";
                    file_put_contents("{$tmpPath}/{$recordId}_{$fKey}", file_get_contents($url));
                }
                if (filesize("{$tmpPath}/{$recordId}_{$fKey}") <= 0) {
                    unlink("{$tmpPath}/{$recordId}_{$fKey}");
                    continue;
                }
                $page = file_get_contents("{$tmpPath}/{$recordId}_{$fKey}");
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
                        $page = str_replace(array(' ', '　'), '', $page);
                        $pos = strpos($page, '姓名</td>');
                        $table = substr($page, $pos, strpos($page, '</table', $pos) - $pos);
                        $tLines = explode('</tr>', $table);
                        foreach ($tLines AS $tLine) {
                            $tCols = explode('</td>', $tLine);
                            if (count($tCols) === 4) {
                                $tCols[1] = trim(strip_tags($tCols[1]));
                                $tCols[2] = trim(strip_tags($tCols[2]));
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
        } else {
            $record = json_decode(file_get_contents($recordFile), true);
        }
        if (!isset($record['latitude'])) {
            $record['latitude'] = '';
            $record['longitude'] = '';
            file_put_contents($recordFile, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
    }
    fclose($cFh);
}