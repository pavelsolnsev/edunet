<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=0;

/*
// 1 step get lic id by inn
$cur=$db->query('SELECT vuz_id, inn FROM connector WHERE inn!="" AND `vuz_id` NOT IN (SELECT vuz_id FROM lic_main) AND vuz_id IN (SELECT id FROM vuzes WHERE delReason="")');
while($row=$db->get_row($cur)) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL,"http://isga.obrnadzor.gov.ru/rlic/search/?page=1");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'regionId=&licenseOrganId=&eoName=&eoInn='.$row['inn'].'&eoOgrn=&number=&licenseStateId=1&issueFrom=&issueTo=');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $page = curl_exec($ch);
    curl_close ($ch);

//echo $page;
    $c=preg_match_all('/<tr data-guid="(.+?)">/', $page, $res);
    if($c) {
        $db->query('INSERT INTO lic_main(vuz_id, hash) VALUES(?, ?)', $row['vuz_id'], $res[1][0]);
    } else {
        echo $row['vuz_id'].'<br>'; // Valid license not found or it changed INN
    }

    sleep(1);
}
die;
// !
*/

/*
// 2 step get lic data and prils
$cur=$db->query('SELECT vuz_id, hash FROM lic_main WHERE `name`=""');
while($row=$db->get_row($cur)) {
    $name=$lic1=$lic2='';
    $page=file_get_contents('http://isga.obrnadzor.gov.ru/rlic/details/'.$row['hash'].'/');

    $c=preg_match_all('/<td>Регистрационный номер лицензии<\/td>\s+<td>(.+?)<\/td>/misu', $page, $res);
    if($c) {
        $lic1=$res[1][0];
        $c=preg_match_all('/<td>Решение о выдаче<\/td>\s+<td>.+?(\d{1,2}\.\d{1,2}\.\d{4}).+?<\/td>/misu', $page, $res);
        if($c) {
            $lic2=$res[1][0];
            //echo $lic1.' '.$lic2;
        }
    }
    $res='';

    $c=preg_match_all('/<td>Полное наименование организации \(ФИО индивидуального предпринимателя\)<\/td>\s+<td>(.+?)<\/td>/misu', $page, $res);
    if($c) {
        $name=trim($res[1][0]);
    }
    $res='';

    $db->query('UPDATE lic_main SET `name`=?, `lic_num`=?, `lic_start`=? WHERE vuz_id=?', $name, $lic1, $lic2, $row['vuz_id']);

    $c=preg_match_all('/<tr class="clickable" data-target="[a-f\d\-]+">.+?<\/tr>/misu', $page, $res);
    for($i=0; $i<$c; $i++) {
        if(!strstr($res[0][$i], 'Действует')) {
            unset($res[0][$i]);
        }
    }

    $arr=array();
    foreach ($res[0] as $line) {
        $c1=preg_match_all('/<tr class="clickable" data-target="([a-f\d\-]+)">\s+<td>[\d\.]+<\/td>\s+<td>(.+?)<\/td>/misu', $line, $res1);
        $res1[1][0]=trim($res1[1][0]);
        $res1[2][0]=trim($res1[2][0]);
        if($res1[2][0] === $name) {
            $main="1";
        } else {
            $main="0";
        }
        $db->query('INSERT INTO `lic_prils`(`vuz_id`, `name`, `hash`, `main`) VALUES(?, ?, ?, ?)', $row['vuz_id'], $res1[2][0], $res1[1][0], $main);
        //echo $r[0]." ".$res1[1][0]." ".$res1[2][0]."<br>";
    } $res='';

    sleep(1);
}
die;
// !
*/

// 3 step check lic without main prils
// SELECT * FROM `lic_main` WHERE vuz_id NOT IN (SELECT vuz_id FROM lic_prils WHERE main="1" AND vuz_id=vuz_id)
/*
 * Fix it
 */

// Set main=1 for prils where prils is single
/*
    UPDATE lic_prils SET main="1" WHERE vuz_id IN (
        SELECT b.vuz_id FROM (
            SELECT a.vuz_id, count(*) AS c FROM (
                SELECT vuz_id FROM `lic_main` WHERE vuz_id NOT IN (
                    SELECT vuz_id FROM lic_prils WHERE main="1" AND vuz_id=vuz_id
                )
            ) a
            LEFT JOIN lic_prils USING (vuz_id) WHERE lic_prils.id IS NOT NULL GROUP BY a.vuz_id HAVING c=1
        ) b
    )
*/
/*
$cur=$db->query('SELECT vuz_id, name FROM lic_main');
while($row=$db->get_row($cur)) {
    $row['name']=str_replace("\n", ' ', $row['name']);
    $row['name']=preg_replace('/([^а-яА-ЯёЁ\s\d])/u', ' ', $row['name']);
    $row['name']=preg_replace('/(\s{2,})/u', ' ', $row['name']);
    $row['name']=trim($row['name']);
    $db->query('UPDATE lic_main SET clear_name=? WHERE vuz_id=?', $row['name'], $row['vuz_id']);
}
die;*/
/*
$cur=$db->query('SELECT id, name FROM lic_prils');
while($row=$db->get_row($cur)) {
    $row['name']=str_replace("\n", ' ', $row['name']);
    $row['name']=preg_replace('/([^а-яА-ЯёЁ\s\d])/u', ' ', $row['name']);
    $row['name']=preg_replace('/(\s{2,})/u', ' ', $row['name']);
    $row['name']=trim($row['name']);
    $db->query('UPDATE lic_prils SET clear_name=? WHERE id=?', $row['name'], $row['id']);
}
die;
$cur=$db->query('SELECT vuz_id, clear_name FROM `lic_main` WHERE vuz_id NOT IN (SELECT vuz_id FROM lic_prils WHERE main="1" AND vuz_id=vuz_id)');
while($row=$db->get_row($cur)) {
    $cur2=$db->query('SELECT id FROM lic_prils WHERE clear_name=? AND vuz_id=?', $row['clear_name'], $row['vuz_id']);
    while($row1=$db->get_row($cur2)) {
        $db->query('UPDATE lic_prils SET main="1" WHERE id=?', $row1['id']);
    }
}
die;
//!
*/

/*
// 3 step. Get specs from prils
$cur=$db->query('SELECT id, `vuz_id`, `hash` FROM `lic_prils` WHERE addr1=""');
while($row=$db->get_row($cur)) {
    $page=file_get_contents('http://isga.obrnadzor.gov.ru/rlic/supplement/'.$row['hash'].'/');
    $c=preg_match_all('/<td>Место нахождения организации<\/td>\s+<td>(.+?)<\/td>/misu', $page, $res);
    if($c) {
        $addr1=trim($res[1][0]);
    }$res='';

    $c=preg_match_all('/<td>Места осуществления образовательной деятельности<\/td>\s+<td>(.+?)<\/td>/misu', $page, $res);
    if($c) {
        $addr2=trim($res[1][0]);
    }$res='';
    $db->query('UPDATE lic_prils SET addr1=?, addr2=? WHERE id=?', $addr1, $addr2, $row['id']);

    $c=preg_match_all('/<td>(\d{2}\.(03|04|05)\.\d{2})<\/td>\s+<td>(.+?)<\/td>\s+<td>.*?<\/td>\s+<td>.*?<\/td>\s+<td>(.*?)<\/td>/misu', $page, $res);
    for($i=0; $i<$c; $i++) {
        $db->query('INSERT INTO `lic_spec`(`vuz_id`, `hash`, `code`, `name`, `qual`) VALUES (?, ?, ?, ?, ?)',
            $row['vuz_id'], $row['hash'], str_replace(".", "", $res[1][$i]), $res[3][$i], $res[4][$i]);

        //echo $res[1][$i]." ".$res[3][$i]." ".$res[4][$i]."<br>";
    }
    sleep(1);
}
die;
// !
*/


// 3 step. Add okso for vuz
$cur1=$db->query('SELECT hash, vuz_id FROM lic_prils WHERE main="1"');
while($row1=$db->get_row($cur1)) {
    $cur2=$db->query('SELECT DISTINCT okso.id FROM `lic_spec` LEFT JOIN okso ON okso.code=lic_spec.code WHERE `hash`=?', $row1['hash']);
    while($row2=$db->get_row($cur2)) {
        if($row2['id']) {
            $db->query('SELECT 1 FROM lic_okso WHERE vuz_id=? AND okso_id=?', $row1['vuz_id'], $row2['id']);
            if(!$db->num_rows()) {
                $db->query('INSERT INTO lic_okso(vuz_id, okso_id) VALUES (?, ?)', $row1['vuz_id'], $row2['id']);
            }
        }
    }
}
die;
//!


/*
// 4 step. Add okso for filials
$cur=$db->query('SELECT vuzes.id, parent_id, cities.name FROM `vuzes` LEFT JOIN general.cities ON cities.id=city_id  WHERE parent_id IS NOT NUll AND vuzes.id NOT IN (SELECT DISTINCT vuz_id FROM lic_okso) ORDER BY `vuzes`.`parent_id` ASC ');
while($row=$db->get_row($cur)) {
    $cur1 = $db->query('SELECT hash FROM lic_prils WHERE vuz_id=? AND addr2 LIKE "%'.$row['name'].'%" AND main="0"', $row['parent_id']);
    if(!$db->num_rows()) {
        echo $row['id']."<br>";
        continue;
    }
    while ($row1 = $db->get_row($cur1)) {
        $cur2=$db->query('SELECT DISTINCT okso.id FROM `lic_spec` LEFT JOIN okso ON okso.code=lic_spec.code WHERE `hash`=?', $row1['hash']);
        while($row2=$db->get_row($cur2)) {
            if($row2['id']) {
                $db->query('SELECT 1 FROM lic_okso WHERE vuz_id=? AND okso_id=?', $row['id'], $row2['id']);
                if(!$db->num_rows()) {
                    $db->query('INSERT INTO lic_okso(vuz_id, okso_id) VALUES (?, ?)', $row['id'], $row2['id']);
                }
            }
        }
    }
}

die;
*/

// Check unit without okso
//SELECT `id` FROM `vuzes` WHERE delReason="" AND `id` NOT IN (SELECT DISTINCT `vuz_id` FROM `lic_okso`)