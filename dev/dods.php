<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
require(base_path."classes/user_class.php");

$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=0;

if($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    if(!$u_id=user::check_session()) {
        header('Location: https://secure'.DOMAIN.'/#vuz'.DOMAIN.'/dev/doder.php');
        die;
    }

    if(!in_array($u_id, array(1, 777))) {
        die("access denied");
    }
}

function normal2mysql($date) {
    $date=explode(".",$date);
    return($date[2].'-'.$date[1].'-'.$date[0]);
}

function get_ssl_page($url) {
    $options = array(
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_USERAGENT      => "Mozilla/5.0 (compatible; MegaIndex.ru/2.0; +http://megaindex.com/crawler)", // who am i
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
        //CURLOPT_PROXYTYPE      => CURLPROXY_SOCKS5,
        //CURLOPT_PROXY, "96.44.183.149:55225"
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    curl_close( $ch );

    return $content;
}

$matrix=array(
    'spbgikit'=>577,
    'fa'=>705,
    'vka'=>2510,
    'uslu'=>685,
    'yafu'=>524,
    'vfmgei'=>1030,
    'ranepa'=>9,
    'sechenov'=>353,
    'igumo'=>184,
    'mabiu'=>326,
    'linguanet'=>372,
    'unecon'=>582,
    'spbu'=>570,
    'spmi'=>563,
    'sziu-ranepa'=>591,
    'unn'=>426,
    'vzfei-omsk'=>998,
    'hse'=>109,
    'mosgu'=>198,
    'mitu-masi'=>2550,
    'mfua'=>189,
    'mgei'=>393,
    'mai'=>359,
    'guu'=>123,
    'vavt'=>102,
    'mgppu'=>1109,
    'mfpa'=>863,
    'spbstu'=>567,
    'spbume'=>219,
    'dgtu'=>156,
    'guap'=>547,
    'gup'=>575,
    'ieml'=>227,
    'kpfu'=>253,
    'krasnodar-ruc'=>1233,
    'mvaa'=>2574,
    'nfmgei'=>1031,
    'ngaha'=>438,
    'nnov-hse'=>1058,
    'nstu'=>443,
    'nsu'=>444,
    'rggmu'=>511,
    'sfu-krasnoyarsk'=>286,
    'siu-ranepa'=>598,
    'spbguga'=>6,
    'chgik'=>718,
    'gubkin'=>121,
    'gumrf'=>125,
    'ifmo'=>564,
    'kazgau'=>248,
    'krkime'=>1708,
    'kubsu'=>292,
    'lengu'=>1793,
    'mgavm'=>341,
    'mgpu'=>358,
    'mpi-fsb'=>357,
    'msal'=>351,
    'mtuci'=>407,
    'nid-design'=>2722,
    'nsuem'=>437,
    'obe'=>2040,
    'omgtu'=>456,
    'rgufk'=>504,
    'rosnou'=>517,
    'rostov-rpa'=>2407,
    'rsue'=>521,
    'rsuh'=>512,
    'rudn'=>518,
    'sgups'=>600,
    'sui-fsin'=>1801,
    'sut'=>572,
    'sutd'=>573,
    'uspu'=>135,
    'vgik-rostov'=>3303,
    'vgltu'=>88,
    'vsmaburdenko'=>89,
    'mguu'=>1108,
    'muiv'=>399,
    'mtu-mirea'=>368,
    'mospolytech'=>340,
    'imc-i'=>2505,
    'usurt'=>677,
    'kazanrpa'=>2512,
    'susu'=>720,
    'omgups'=>452,
    'omgups'=>452,
    'rea-perm'=>452,
    'vfreu'=>1561,
    'omgups'=>452,
    'atiso'=>12,
    'mgudt'=>343,
    'rpa-mu'=>506,
    'mghpu'=>392,
    'rea'=>509,
    'chirt'=>771,
    'vgifk'=>2808,
    'spcpa'=>557,
    'rgppu'=>688,
    'mgifkst'=>1930,
    'gipsr'=>580,
    'imsit'=>755,
    'ineup'=>2979,
    'kgpu'=>284,
    'kf-fa'=>1080,
    'mephi'=>366,
    'mgupp'=>387,
    'sibsau'=>599,
    'sibupk'=>604,
    'skf-mtusi'=>1134,
    'kstu'=>252
);

$cities=array(
    26=>"msk",
    44=>"spb",
    32=>"novosibirsk",
    11=>"ekaterinburg",
    30=>"nn",
    14=>"kazan",
    58=>"chelyabinsk",
    33=>"omsk",
    43=>"samara",
    40=>"rostov-na-donu",
    21=>"krasnoyarsk",
    37=>"perm",
    10=>"voronezh",
    20=>"krasnodar");
//ufa volgograd
$start_date = new DateTime();
$start_date->modify('+3 day');
//echo $start_date->format('Y-m-d');


foreach($cities AS $key=>$val) {
    $doit=true;
    $p=0;
    while($doit) {
        $page=$c=$res='';
        $p++;
        echo '<p>'.$p.'</p>';
        if($p>10) {
            die;
        }
        $page=get_ssl_page('https://vuzlist.com/'.$val.'/dod'.(($p>1) ? ('/'.$p) : ('')));
        $res=explode('<div class="card">', $page);
        $c=sizeof($res);
        for($i=1; $i<$c && $doit; $i++) {
            $c1=preg_match_all('/<div class="card-sub-title">(.+?)<\/div>/mis', $res[$i], $res1);
            for($j=0; $j<$c1; $j++) {
                $addr='';
                preg_match_all('/(\d{2}\.\d{2}\.\d{4})/', $res1[1][$j], $res2);
                $date=normal2mysql($res2[1][0]);
                if($date<$start_date->format('Y-m-d')) {
                    $doit=false;
                    break;
                }
                $res2='';
                $c2=preg_match_all('/(\d{2}:\d{2})/', $res1[1][$j], $res2);
                if($c2) {
                    $time=$res2[1][0];
                } else {
                    $time='00:00';
                }
                $date=$date.' '.$time.':00';
                //echo $date.' '.$time."\n";
                $res2='';

                $vuz=$name='';
                $c2=preg_match_all('/<div class="card-overline">(.+?)<\/div>/mis', $res[$i], $res2);
                if($c2) {
                    $vuz=trim($res2[1][0]);
                }

                $c2=preg_match_all('/<div class="card-title">(.+?)<\/div>/mis', $res[$i], $res2);
                if($c2) {
                    if($vuz) {
                        $name=trim($res2[1][0]);
                    } else {
                        $vuz=trim($res2[1][0]);
                    }
                }
                $c2=preg_match_all('/<a class="card-menu-item-link" href="https:\/\/vuzlist\.com\/'.$val.'\/(.+?)"/mis', $res[$i], $res2);
                if($c2) {
                    $obj_id=$res2[1][0];
                }

                $c2=preg_match_all('/<p><a class="btn btn-primary" target="_blank" href="(.+?)">/mis', $res[$i], $res2);
                if($c2) {
                    $url=$res2[1][0];
                }

                $c2=preg_match_all('/<dt>(.+?)<\/dt>\s+<dd>(.+?)<\/dd>/mis', $res[$i], $res2);
                for($k=0; $k<$c2; $k++) {
                    if($res2[1][$k] === 'Место проведения:') {
                        $addr=$res2[2][$k];
                        break;
                    }

                }

                $hash=crc32($key.$obj_id.$name.$date.$url);
                $hash=current(unpack('l', pack('l', $hash)));

                $db->query('SELECT 1 FROM `doder` WHERE `source`="vuzlist" AND `hash`=?',$hash);
                if(!$db->num_rows()) {
                    $vuz_id=($matrix[$obj_id] ? $matrix[$obj_id] : 0);
                    if(!$vuz_id) {
                        echo $obj_id."|".$name."<br>";
                    }
                    $db->query('
                        INSERT INTO `vuz`.`doder`(`source`, `hash`, `city_id`, `vuz_id`, `obj_id`, `vuz`, `name`, `time`, `place`, `url`) 
                        VALUES ("vuzlist", ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                        $hash, $key, $vuz_id, $obj_id, $vuz, $name, $date, $addr, $url);
                }
/*
                echo $date.' '.$time."\n";
                echo $vuz."\n";
                echo $name."\n";
                echo $obj_id."\n";
                echo $url."\n";
                echo $addr."\n------------------------\n";*/
                $date=$time=$name=$vuz=$obj_id=$url=$addr=$res2=$res1=$hash='';
            }
        }
        sleep(1);
    }
}
echo '<p>ГОТОВО</p>';


