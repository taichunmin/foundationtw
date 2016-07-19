<?php

require_once('lib/QueryPath/qp.php');

class Crawler
{
    const URL = 'http://cdcb.judicial.gov.tw/abbs/wkw/WHD6K02.jsp';
    const COOKIE_FILE = __DIR__ . '/cookie.txt';

    public $qp_opt = [
        'convert_to_encoding' => 'UTF-8',
        'convert_from_encoding' => 'UTF-8',
        'encoding' => 'UTF-8',
    ];

    private $length = null;
    private $pageCount = null;

    public function length()
    {
        if( is_null($this->length) ) {
            $html = $this->getList();
            preg_match('/合計件數.*?(\d+)/us', $html, $match);
            $this->length = intval($match[1]) ?: 0;
        }
        return $this->length;
    }

    public function pageCount()
    {
        if( is_null($this->pageCount) )
            $this->pageCount = ceil($this->length() / 10);
        return $this->pageCount;
    }

    public function getList($page = 1)
    {
        $page = intval($page) ?: 1;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT_MS => 5000,
            CURLOPT_FOLLOWLOCATION    => true,
            CURLOPT_MAXREDIRS         => 5,
            CURLOPT_POST              => true,
            CURLOPT_PROTOCOLS         => CURLPROTO_HTTP|CURLPROTO_HTTPS,
            CURLOPT_RETURNTRANSFER    => true,
            CURLOPT_TIMEOUT_MS        => 7500,
            CURLOPT_URL               => static::URL,
            CURLOPT_COOKIEJAR         => static::COOKIE_FILE,
            CURLOPT_COOKIEFILE        => static::COOKIE_FILE,
            CURLOPT_POSTFIELDS        => 'displayCourt=%A5%FE%B3%A1&crtid=ALL&court=ALL&kind=0&kind1=0&year=&word=&no=&recno=&Date1Start=&Date1End=&comname=&pageSize=10&pageTotal=2147483647&addr=&pageNow='.$page,
        ]);
        $curlRes = curl_exec($ch);
        curl_close($ch);
        if( !$curlRes )
            throw new Exception("getList $page failed.");
        return mb_convert_encoding($curlRes, 'UTF-8', 'CP950, BIG-5');
    }

    public function parseList($page = 1)
    {
        $html = $this->getList($page);
    }
}

$crawler = new Crawler;
file_put_contents(__DIR__ . '/debug.txt', $crawler->getList(3));
file_put_contents(__DIR__ . '/debug1.txt', $crawler->length());

