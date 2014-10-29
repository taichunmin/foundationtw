<?php
/*
 * put the targets you want to find, like $targets = array('name1', 'name2');
 */
$targets = array('');
foreach ($targets AS $target) {
    $outFh = fopen(__DIR__ . '/' . $target, 'w');
    $items = array();
    $fh = fopen(__DIR__ . '/output/all_detail.csv', 'r');
    $titles = fgetcsv($fh, 2048);
    while ($line = fgetcsv($fh, 2048)) {
        if (false !== strpos($line[33], $target)) {
            $line[33] = explode('|', $line[33]);
            $dateParts = explode('/', $line[7]);
            $dateParts[0] += 1911;
            $line[7] = implode('', $dateParts);
            if (isset($items[$line[12]]) && $line[7] > $items[$line[12]][7]) {
                $items[$line[12]] = $line;
            } elseif (!isset($items[$line[12]])) {
                $items[$line[12]] = $line;
            }
        }
    }
    foreach ($items AS $name => $data) {
        $data = array_combine($titles, $data);
        foreach ($data['董監事'] AS $col) {
            if (false !== strpos($col, $target)) {
                $data['obj'] = substr($col, 0, strpos($col, ':'));
            }
        }
        fputs($outFh, "法人名稱： {$data['法人名稱']}\n");
        fputs($outFh, "設立登記日期： {$data['設立登記日期']}\n");
        fputs($outFh, "法人代表： {$data['法人代表']}\n");
        fputs($outFh, "主事務所： {$data['主事務所']}\n");
        fputs($outFh, "目的： {$data['目的']}\n");
        fputs($outFh, "{$target}擔任： {$data['obj']}\n\n");
    }
}
