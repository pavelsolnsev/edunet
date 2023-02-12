<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=false;


// PRE: add dirs where city_id= null(all russia), 26, 44 manualy
$cur=$db->query('SELECT vuzes.city_id, count(*) AS c FROM e_plus2 LEFT JOIN `vuzes`  ON e_plus2.vuz_id=vuzes.id WHERE delReason="" GROUP BY city_id HAVING c>2 UNION SELECT null, 0');
while($city=$db->get_row($cur)) {
    $city_id=$city['city_id'];

    if($city_id!==null && $city_id!=26 && $city_id!=44) {
        $db->query('SELECT SUM(o+oz+z) AS S FROM `e_plus2` WHERE vuz_id IN (SELECT id FROM vuzes WHERE delReason="" AND city_id=?)', $city_id);
        $row=$db->get_row();
        $S=$row['S'];

        $db->query('SELECT o+oz+z AS S, (SELECT abrev FROM vuzes WHERE id=vuz_id) AS abrev FROM `e_plus2` WHERE vuz_id IN (SELECT id FROM vuzes WHERE delReason="" AND city_id=?) ORDER BY S DESC', $city_id);
        $cnt=$db->num_rows();
        $dirs='';
        $i=$tmp=$sum=0;

        while($row=$db->get_row()) {
            $tmp=round($row['S']*100/$S);
            $sum+=$tmp;
            $dirs.=$row['abrev'].'|'.$tmp."\n";
            $i++;
            if($i==4) {
                $dirs.='Другие|'.(100-$sum)."\n";
                break;
            }
        }
        $dirs=substr($dirs, 0, -1);
        $db->query('INSERT INTO e_plus_geo(city_id, dirs) VALUES(?, ?)', $city_id, $dirs);
    }

    if($city_id) {
        $W1=' WHERE vuz_id IN (SELECT id FROM vuzes WHERE city_id='.$city_id.')';
        $W2=' AND city_id='.$city_id;
    } else {
        $W1=$W2='';
    }

    $db->query('
        SELECT SUM(a.f)*100/SUM(a.total) as free 
        FROM (
            SELECT (o+oz+z) AS total, ROUND((o+oz+z)*free/100) AS f FROM `e_plus2` '.$W1.'
        ) a');
    $row=$db->get_row();
    $free=round($row['free']);

    $db->query('
        SELECT a.o*100/a.total AS o, a.oz*100/a.total AS oz, a.z*100/a.total AS z 
        FROM (
            SELECT SUM(o) AS o, SUM(oz) AS oz, SUM(z) AS z, SUM(o+oz+z) AS total FROM `e_plus2` '.$W1.'
        )a');
    $row=$db->get_row();
    $forms=round($row['o']).'|'.round($row['oz']).'|'.round($row['z']);

    $db->query('
        SELECT SUM(a.f)*100/SUM(a.total) as mag 
        FROM (
            SELECT (o+oz+z) AS total, ROUND((o+oz+z)*mag/100) AS f FROM `e_plus2` '.$W1.'
        ) a');
    $row=$db->get_row();
    $mag=round($row['mag']);

    $db->query('
        SELECT count(*)*100/(SELECT count(*) FROM vuzes WHERE delReason="" '.$W2.')  AS gos 
        FROM vuzes WHERE delReason="" AND gos="1" '.$W2);
    $row=$db->get_row();
    $gos=round($row['gos']);

    $db->query('
        SELECT count(*)*100/(SELECT count(*) FROM vuzes WHERE delReason="" '.$W2.')  AS fils
        FROM vuzes WHERE delReason="" AND parent_id IS NOT NULL '.$W2);
    $row=$db->get_row();
    $fils=round($row['fils']);

    $db->query('
        SELECT SUM(o+oz+z)*100/(SELECT SUM(o+oz+z) FROM e_plus2 '.$W1.') AS gosstuds 
        FROM `e_plus2` WHERE vuz_id IN (SELECT id FROM vuzes WHERE gos="1" AND delReason="" '.$W2.')');
    $row=$db->get_row();
    $gosstuds=round($row['gosstuds']);

    $db->query('
        UPDATE `e_plus_geo` 
        SET `free`=?,`gos`=?,`gos_studs`=?,`forms`=?,`fils`=?,`mag`=? 
        WHERE city_id'.(($city_id)?('='.$city_id):(' IS NULL')),
        $free, $gos, $gosstuds, $forms, $fils, $mag);
}


//SELECT SUM(a.f)*100/SUM(a.total) as free FROM (SELECT (o+oz+z) AS total, ROUND((o+oz+z)*free/100) AS f FROM `e_plus2` WHERE vuz_id IN (SELECT id FROM vuzes WHERE city_id=16)) a
//SELECT count(*)*100/(SELECT count(*) FROM vuzes WHERE delReason="" AND city_id=26) AS gos FROM vuzes WHERE delReason="" AND gos="1" AND city_id=26
die;

