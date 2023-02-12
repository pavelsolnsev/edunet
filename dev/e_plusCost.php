<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=false;


$cntsCost=array();
$db->query('SELECT subj_id, COUNT(*) as c FROM vuzes WHERE vuzes.id IN (SELECT DISTINCT vuz_id FROM specs LEFT JOIN okso ON okso.id=okso_id WHERE form="1" AND `f_cost`!=0 AND substring(code, 4,1)!="4") AND delReason="" GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsCost[$row['subj_id']]=$row['c'];
}
$res=$db->query('
    SELECT `vuz_id`, `subj_id`, `city_id` FROM `e_plus` LEFT JOIN vuzes ON vuz_id=vuzes.id');
while($vuz=$db->get_row($res)) {
    //$db->query('SELECT AVG(f_cost) AS cost FROM `specs` LEFT JOIN okso ON okso.id=okso_id WHERE vuz_id=? AND form="1" AND SUBSTRING(code, 4, 1) != 4 GROUP BY vuz_id', $vuz['vuz_id']);
    // $row=$db->get_row();
    $j = 0;
    $a=false;
    $cost='';
    $db->query('SELECT vuz_id, AVG(f_cost) AS cost FROM `specs` LEFT JOIN okso ON okso.id=okso_id LEFT JOIN vuzes ON vuz_id=vuzes.id WHERE subj_id=? AND form="1" AND SUBSTRING(code, 4, 1) != 4 GROUP BY vuz_id HAVING cost>0 ORDER BY cost DESC ', $vuz['subj_id']);
    if ($db->num_rows()) {
        if(in_array($vuz['city_id'], array(26,44,32,11,30,14,58,33,43,40,54,21,37,10,8,20))) {
            while ($row = $db->get_row()) {
                $j++;
                if ($row['vuz_id'] == $vuz['vuz_id']) {
                    $a=true;
                    break;
                }
            }
            if($a) {
                $cost = round($row['cost']) . "|" . $j . "|" . $cntsCost[$vuz['subj_id']];
            }
        } else {
            $cost = round($row['cost']);
        }
    }

    $db->query('
        UPDATE `e_plus` SET `cost`=? WHERE `vuz_id`=?',
        $cost, $vuz['vuz_id']);
}




