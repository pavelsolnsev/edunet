<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=false;

$lastMon=20;
$cur=$db->query('SELECT `id` FROM `vuzes` WHERE `delReason`=""');
while($vuz=$db->get_row($cur)) {
    $marks=0;
    $db->query('
        SELECT `label`, `val` FROM `monit` 
        WHERE `vuz_id`=? AND `year`="'.$lastMon.'" AND `label` IN("o", "oz", "z")', $vuz['id']);
    if($db->num_rows()) {
        $arr=array();
        $popul=0;
        while($row=$db->get_row()) {
            $arr[$row['label']]=$row['value'];
            $popul+=$row['value'];
        }

        if($arr['z']*100/($arr['oz']+$arr['o']+$arr['z']) > 80) {
            $marks+=10;
        }

        $db->query('
            SELECT `label`, `val` FROM `monit` 
            WHERE `vuz_id`=? AND `year`="'.($lastMon-2).'" AND `label` IN("o", "oz", "z")', $vuz['id']);
        if($db->num_rows()) {
            $popul1=0;
            while($row=$db->get_row()) {
                $popul1+=$row['value'];
            }
            if($popul*100/$popul1 < 80) {
                $marks+=1;
            }
        }
    }
    $db->query('UPDATE `vuzes` SET `esi_marks`=? WHERE `id`=?', $marks, $vuz['id']);
}