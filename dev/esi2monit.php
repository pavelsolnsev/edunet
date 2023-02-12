<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=false;
$y=20;
$res=$db->query('SELECT `id`, `esi` FROM `vuzes` WHERE vedom="0" AND delReason=""');
while($row=$db->get_row($res)) {
    $db->query('SELECT 1 FROM monit WHERE vuz_id=? AND `year`=? AND label!="msg"', $row['id'], (string) $y);
    if($db->num_rows()) {
        if($row['esi']>7) {
            $out = 'A';
        } elseif($row['esi']>3) {
            $out = 'B';
        } else {
            $out = 'C';
        }
       $db->query('REPLACE  INTO monit(`vuz_id`, `year`, `label`, `val`) VALUES(?, ?, "eff", ?)', $row['id'], (string) ($y+1), $out);
    }
}