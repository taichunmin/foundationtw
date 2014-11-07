#!/usr/bin/php
<?php
$rootPath = dirname(__DIR__);
require_once "{$rootPath}/lib/LIB_http.php";
require_once "{$rootPath}/lib/LIB_parse.php";
$outputPath = "{$rootPath}/output/lists";
if (!file_exists($outputPath)) {
    mkdir($outputPath, 0777, true);
}

$all_court = array(
    'TPD&臺灣台北地方法院',
    'PCD&臺灣新北地方法院',
    'SLD&臺灣士林地方法院',
    'TYD&臺灣桃園地方法院',
    'SCD&臺灣新竹地方法院',
    'MLD&臺灣苗栗地方法院',
    'TCD&臺灣臺中地方法院',
    'NTD&臺灣南投地方法院',
    'CHD&臺灣彰化地方法院',
    'ULD&臺灣雲林地方法院',
    'CYD&臺灣嘉義地方法院',
    'TND&臺灣臺南地方法院',
    'KSD&臺灣高雄地方法院',
    'PTD&臺灣屏東地方法院',
    'TTD&臺灣臺東地方法院',
    'HLD&臺灣花蓮地方法院',
    'ILD&臺灣宜蘭地方法院',
    'KLD&臺灣基隆地方法院',
    'PHD&臺灣澎湖地方法院',
    'KSY&臺灣高雄少年法院',
    'LCD&褔建連江地方法院',
    'KMD&福建金門地方法院',
);
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

foreach ($all_court as $court) {
    $courtVals = explode('&', $court);
    if (!file_exists("{$rootPath}/tmp/{$courtVals[0]}")) {
        mkdir("{$rootPath}/tmp/{$courtVals[0]}", 0777, true);
    }
    $data = $dataBase;
    $data['court'] = mb_convert_encoding($court, 'big5', 'utf8');
    $fh = fopen("{$outputPath}/{$courtVals[0]}.csv", 'w');
    $headersWritten = false;
    for (; $data['pageNow'] <= $data['pageTotal']; $data['pageNow'] ++) {
        $cachedFile = "{$rootPath}/tmp/{$courtVals[0]}/page_{$data['pageNow']}";

        if (!file_exists($cachedFile)) {
            $response = http($action, $ref, $method, $data, EXCL_HEAD);
            $response['FILE'] = mb_convert_encoding($response['FILE'], 'utf8', 'big5');
            file_put_contents($cachedFile, json_encode($response));
        }
        $page = json_decode(file_get_contents($cachedFile), true);
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
            $line = str_replace(array('&nbsp;', ' '), array('', ''), $line);
            $cols = explode('</td>', $line);
            if (false === $headers) {
                foreach ($cols AS $k => $v) {
                    $cols[$k] = trim(strip_tags($v));
                }
                $headers = $cols;
                if (false === $headersWritten) {
                    fputcsv($fh, $headers);
                    $headersWritten = true;
                }
                $countHeaders = count($headers);
            } else {
                if (count($cols) === $countHeaders) {
                    foreach ($cols AS $k => $v) {
                        if ($k !== 9) {
                            $cols[$k] = trim(strip_tags($v));
                        } else {
                            $vPos = strpos($v, 'WHD6K05.jsp');
                            $cols[$k] = 'http://cdcb.judicial.gov.tw/abbs/wkw/' . substr($v, $vPos, strpos($v, '"', $vPos) - $vPos);
                        }
                    }
                    fputcsv($fh, $cols);
                }
            }
        }

        if ($data['pageNow'] % 50 === 0) {
            echo "processing {$court} page {$data['pageNow']}+\n";
        }
    }
    fclose($fh);
}