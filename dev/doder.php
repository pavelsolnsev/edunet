<?php

/*
https://gumrf.ru/Abitur/denotkrdver
https://abiturient.donstu.ru/proforientatsiya/dni-otkrytyh-dverei/
http://kazgau.ru/obrazovanie/dopolnitelnoe-professionalnoe/dopolnitelnoe-obrazovanie-detej-i-vzroslyh/dni-otkrytyh-dverej/
https://omgtu.ru/entrant/preparation-for-admission/commission_on_career_guidance_and_advocacy/open-days.php
https://rsue.ru/abitur/pdf/grafik-DOD.pdf
https://new.guap.ru/dods
https://abit.itmo.ru/page/50/
https://www.gukit.ru/abiturient/dni-otkrytyx-dverey
https://kosygin-rgu.ru/vuz/opendoors.aspx
https://edu.mgou.ru/openday/new/
http://www.unn.ru/site/education/dni-otkrytykh-dverej-festivali
https://www.rsuh.ru/applicant/dod/
https://atiso.ru/abitur/dod/
https://school.mephi.ru/actions/open-days
 */


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

    $_POST['id']=(int) $_POST['id'];
    switch($_POST['act']) {
        case 'add':
            $sv=(intval($_POST['sv']) ? intval($_POST['sv']) : null);
            $online=($_POST['online']==='1' ? '1' : '0');
            $db->query('
              INSERT INTO `openDays`(`vuz_id`, `subvuz_id`, `name`, `start`, `address`, `online`, `url`) 
              VALUES(?, ?, ?, ?, ?, ?, ?)',
                intval($_POST['vuz_id']), $sv, $_POST['name'], $_POST['start'], $_POST['address'], $online, $_POST['url']);
            $db->query('UPDATE `vuz`.`doder` SET `done`="1" WHERE `id`=?', $_POST['id']);
            break;
        case 'skip':
            $db->query('UPDATE `vuz`.`doder` SET `done`="1" WHERE `id`=?', $_POST['id']);
            break;
    }

    $db->query('
        SELECT `id`,`vuz_id`, `vuz`, `name`, `time`, `place`, `url` 
        FROM `vuz`.`doder` 
        WHERE `vuz_id`!=0 AND `done`="0" 
        ORDER BY vuz_id LIMIT 1');
    if(!$db->num_rows()) {
        die('Все');
    }
    $row=$db->get_row();

    $html='
        <html>
        <head>
            <title>Додер</title>
            <style>input[type=text] { width: 100%; }</style>
        </head>
        <body>
            <div style="overflow: hidden">
                <div style="float: left; width:45%">
                    <form method="post">
                        <p>'.$row['vuz_id'].'|'.$row['vuz'].'</p>
                        <input type="hidden" name="act" value="add" />
                        <input type="hidden" name="id" value="'.$row['id'].'" />
                        <input type="hidden" name="vuz_id" value="'.$row['vuz_id'].'" />
                        <p>Название: <input type="text" name="name" value="'.$row['name'].'" /></p>';
    $db->query('SELECT `id`, `name` FROM `vuz`.`subvuz` WHERE `vuz_id`=?', $row['vuz_id']);
    if($db->num_rows()>1) {
        $html.='<p>Подр. <select name="sv"><option value="0">Нет</option>';
        while($sv=$db->get_row()) {
            $html.='<option value="'.$sv['id'].'">'.$sv['name'].'</option>';
        }
        $html.='</select></p>';
    }
    $html.='<p>Начало: <input type="text" name="start" value="'.$row['time'].'" /></p>
                        <p>Место: <input type="text" name="address" value="'.$row['place'].'" /></p>
                        <p>Онлайн: <input type="checkbox" name="online" value="1" /></p>
                        <p>УРЛ: <input type="text" name="url" value="'.$row['url'].'" /> <a target="_blank" href="'.$row['url'].'">Ссылка</a></p>
                        <button>Поехали</button>
                    </form>
                    <br><br>
                    <form method="post">
                        <input type="hidden" name="act" value="skip" />
                        <input type="hidden" name="id" value="'.$row['id'].'" />
                        <button>Пропустить</button>
                    </form>
                </div>
                <div style="float: right; width:45%">';
    $db->query('
				SELECT a.`name`, a.`address`, a.`start`, a.`online`, a.`url`, `subvuz`.`name` as subvuz 
				FROM 
					(
						SELECT 
							`openDays`.`name`, `openDays`.`address`, `openDays`.`subvuz_id`, `openDays`.`start`,
							`openDays`.`online`, `openDays`.`url`
						FROM `vuz`.`openDays` WHERE `openDays`.`vuz_id`=? AND `openDays`.`start`>NOW()
					) a LEFT JOIN 
					`vuz`.`subvuz` ON a.`subvuz_id`=`subvuz`.`id`
				ORDER BY a.`start`', $row['vuz_id']);
    while($dod=$db->get_row()) {
        $html.=
            '<p>
            <ul>
                <li>Старт '.($dod['start'] ? $dod['start'] : '-').'</li>
                <li>Имя '.($dod['name'] ? $dod['name'] : '-').'</li>
                <li>Подр. '.($dod['subvuz'] ? $dod['subvuz'] : '-').'</li>
                <li>Адрес '.($dod['address'] ? $dod['address'] : '-').'</li>
                <li>'.($dod['online'] ? 'Онлайн' : '-').'</li>
                <li>УРЛ '.($dod['url'] ? '<a href="'.$dod['url'].'">'.$dod['url'].'</a>' : '-').'</li>
            </ul>
        </p>';
    }

    $html.='
                </div>
            </div>
        </body>
        </html>';
    echo $html;