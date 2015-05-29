<?php

$rootPath = dirname(__DIR__);
require_once "{$rootPath}/lib/LIB_http.php";
include_once $rootPath . '/lib/cns11643/scripts/big5e_to_utf8.php';
$outputPath = "{$rootPath}/output/lists";
if (!file_exists($outputPath)) {
    mkdir($outputPath, 0777, true);
}

$action = 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K02.jsp';
$method = 'POST';
$ref = ' ';
$dataBase = array(
    'court' => '',
    'classType' => 'RA001', //法人登記
    'year' => '',
    'word' => '',
    'no' => '',
    'recno' => '',
    'kind' => '0',
    'Date1Start' => '',
    'Date1End' => '',
    'kind1' => '0',
    'comname' => '',
    'pageSize' => '10',
    'pageTotal' => 1,
    'pageNow' => 1,
);

$hitExisted = false;
$listFh = array();
$currentTime = strtotime(date('Y-m') . '-01');
$nextTime = strtotime(date('Y-m', strtotime('+1 month', $currentTime)) . '-01') - 1;
while (false === $hitExisted) {
    $cachedFolder = "{$rootPath}/tmp/" . date('Ym', $currentTime);
    if (!file_exists($cachedFolder)) {
        mkdir($cachedFolder, 0777, true);
    }
    echo "processing {$cachedFolder}\n";
    $data = $dataBase;
    $data['Date1Start'] = date('Y', $currentTime) - 1911;
    $data['Date1Start'] .= date('md', $currentTime);
    $data['Date1End'] = date('Y', $nextTime) - 1911;
    $data['Date1End'] .= date('md', $nextTime);
    for (; $data['pageNow'] <= $data['pageTotal']; $data['pageNow'] ++) {
        $cachedFile = "{$cachedFolder}/page_{$data['pageNow']}.html";
        if (!file_exists($cachedFile)) {
            $response = http($action, $ref, $method, $data, EXCL_HEAD);
            file_put_contents($cachedFile, serialize($response));
        }
        $page = unserialize(file_get_contents($cachedFile));
        if ($data['pageNow'] === 1) {
            $pos = strrpos($page['FILE'], 'name="pageTotal" value="');
            if (false !== $pos) {
                $pos += 24;
                $data['pageTotal'] = substr($page['FILE'], $pos, strpos($page['FILE'], '"', $pos) - $pos);
            }
        }
        $pos = strpos($page['FILE'], '<td width="6%" class="head">');
        $posEnd = strpos($page['FILE'], '</table>', $pos);
        $page['FILE'] = substr($page['FILE'], $pos, $posEnd - $pos);
        $lines = explode('</tr>', $page['FILE']);
        $headers = $countHeaders = false;
        foreach ($lines AS $line) {
            $line = Converter::iconv($line, 1);
            $line = str_replace(array(' ', '&nbsp;'), array(''), $line);
            $cols = explode('</td>', $line);
            if (false === $headers) {
                foreach ($cols AS $k => $v) {
                    $cols[$k] = trim(strip_tags($v));
                }
                $headers = $cols;
                $headers[10] = '檔案位置';
                $headers[11] = 'ID';
                $headers[12] = '法院代碼';
                $countHeaders = count($headers) - 2;
            } else {
                if (count($cols) === $countHeaders && false === $hitExisted) {
                    foreach ($cols AS $k => $v) {
                        switch ($k) {
                            case 9:
                                $vPos = strpos($v, 'WHD6K05.jsp');
                                $cols[$k] = 'http://cdcb.judicial.gov.tw/abbs/wkw/' . substr($v, $vPos, strpos($v, '"', $vPos) - $vPos);
                                break;
                            case 10:
                                $urlParts = parse_url($cols[9]);
                                parse_str($urlParts['query'], $queryParts);
                                $recordId = str_replace('/', '-', $queryParts['ab']);
                                $cols[11] = $queryParts['ab'];
                                $cols[12] = $queryParts['ef'];
                                $cols[10] = "output/details/{$queryParts['ef']}/{$queryParts['bc']}/{$queryParts['de']}/{$recordId}.json";
                                break;
                            default:
                                $cols[$k] = trim(strip_tags($v));
                        }
                    }
                    if (!file_exists("{$rootPath}/{$cols[10]}")) {
                        if (!isset($listFh[$cols[12]])) {
                            $listFile = "{$rootPath}/output/lists/{$cols[12]}.csv";
                            if (file_exists($listFile)) {
                                $listFh[$cols[12]] = fopen($listFile, 'a');
                            } else {
                                $listFh[$cols[12]] = fopen($listFile, 'w');
                                fputcsv($listFh[$cols[12]], $headers);
                            }
                        }
                        fputcsv($listFh[$cols[12]], $cols);
                    } else {
                        $hitExisted = true;
                    }
                }
            }
        }
    }

    // for next loop
    $nextTime = $currentTime;
    $currentTime = strtotime('-1 month', $currentTime);
}