<?php
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin,$cDbPass,$vuzDbName);
$db->hideErr=false;

/*
$v_id=0;
$i=0;
$res=$db->query('
    SELECT 
        vuz_id, SUBSTRING(`okso_gr`.`name`, 1, 40) AS name, perc
    FROM 
        `e_plus2_dirs` LEFT JOIN 
        `okso_gr` ON `okso_gr`.`id`=dir 
    ORDER BY vuz_id, perc DESC');
while($row=$db->get_row($res)) {
    if($i==4) {
        $str.='Другое|'.round(100-$s)."\n";
        $i=5;
        continue;
    }
    if($i==5 && $v_id==$row['vuz_id']) {
        continue;
    }
    //echo $v_id."|";
    if($v_id!=$row['vuz_id']) {
        if($v_id)  {
            $str=substr($str, 0, -1);
            //$db->query('INSERT INTO e_plus(vuz_id, dirs) VALUES(?, ?)', $v_id, $str);
            //$db->query('UPDATE e_plus SET dirs=? WHERE vuz_id=?', $str, $v_id);
        }
        $v_id=$row['vuz_id'];
        $str='';
        $s=0;
        $i=0;
    }
    $s+=$row['perc'];
    $i++;
    $str.=trim($row['name']).'|'.round($row['perc'])."\n";

}

die;
$cntsFree=array();
$db->query('SELECT subj_id, count(*) as c FROM e_plus2 WHERE egeFree!=0 GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsFree[$row['subj_id']]=$row['c'];
}

$cntsPay=array();
$db->query('SELECT subj_id, count(*) as c FROM e_plus2 WHERE egePay!=0 GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsPay[$row['subj_id']]=$row['c'];
}

$cntsCost=array();
$db->query('SELECT subj_id, COUNT(*) as c FROM vuzes WHERE vuzes.id IN (SELECT vuz_id FROM specs LEFT JOIN okso ON okso.id=okso_id WHERE form="1" AND f_cost!=0 AND substring(code, 4,1)!="4") AND delReason="" GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsCost[$row['subj_id']]=$row['c'];
}

$cntsPlace=array();
$db->query('SELECT subj_id, count(*) as c FROM e_plus2 WHERE place!=0 GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsPlace[$row['subj_id']]=$row['c'];
}

$cntsPc=array();
$db->query('SELECT subj_id, count(*) as c FROM e_plus2 WHERE pc!=0 GROUP BY subj_id');
while($row=$db->get_row()) {
    $cntsPc[$row['subj_id']]=$row['c'];
}
*/
$res=$db->query('
    SELECT 
        `vuz_id`, `subj_id`, 
        `score`, `egeFree`, `egePay`, `egeSred`, `egeMin`, 
        `o`, `oz`, `z`, `mag`, 
        `sng`, `neSng`, `free`, 
        `inoPrep`, `place`, `pc`, `trud`, `kn`, `dn`, `shtat`, `do40`, `do65`, hos, `noHos` FROM `e_plus2`');
while($vuz=$db->get_row($res)) {/*
    $s=$vuz['o']+$vuz['oz']+$vuz['z'];
    $forms=round($vuz['o']*100/$s)."|".round($vuz['oz']*100/$s)."|".round($vuz['z']*100/$s);

    $vuz['mag']=round($vuz['mag']);
    $studs=(100-round($vuz['sng'])-round($vuz['neSng']))."|".round($vuz['sng'])."|".round($vuz['neSng']);


    $j=0;
    if($vuz['egeFree']) {
        $db->query('SELECT vuz_id FROM e_plus2 WHERE subj_id=? ORDER BY egeFree DESC', $vuz['subj_id']);
        while($row=$db->get_row()) {
            $j++;

            if($row['vuz_id']==$vuz['vuz_id']) {
                break;
            }
        }
        $egeFree=str_replace(".",',', round($vuz['egeFree'],1))."|".$j."|".$cntsFree[$vuz['subj_id']];
    } else {
        $egeFree='';
    }

    $j=0;
    if($vuz['egePay']) {
        $db->query('SELECT vuz_id FROM e_plus2 WHERE subj_id=? ORDER BY egePay DESC', $vuz['subj_id']);
        while($row=$db->get_row()) {
            $j++;

            if($row['vuz_id']==$vuz['vuz_id']) {
                break;
            }
        }
        $egePay=str_replace(".",',', round($vuz['egePay'],1))."|".$j."|".$cntsPay[$vuz['subj_id']];
    } else {
        $egePay='';
    }


    $db->query('SELECT AVG(f_cost) AS cost FROM `specs` LEFT JOIN okso ON okso.id=okso_id WHERE vuz_id=? AND form="1" AND SUBSTRING(code, 4, 1) != 4 GROUP BY vuz_id', $vuz['vuz_id']);
    $row=$db->get_row();
    if($row['cost']) {
        $j=0;
        $db->query('SELECT vuz_id, AVG(f_cost) AS cost FROM `specs` LEFT JOIN okso ON okso.id=okso_id LEFT JOIN vuzes ON vuz_id=vuzes.id WHERE subj_id=? AND form="1" AND SUBSTRING(code, 4, 1) != 4 GROUP BY vuz_id', $vuz['subj_id']);
        while($row=$db->get_row()) {
            $j++;
            if($row['vuz_id']==$vuz['vuz_id']) {
                break;
            }
        }
        $cost=round($row['cost'])."|".$j."|".$cntsCost[$vuz['subj_id']];
    } else {
        $cost='';
    }

    $j=0;
    if($vuz['place']) {
        $db->query('SELECT vuz_id FROM e_plus2 WHERE subj_id=? ORDER BY place DESC', $vuz['subj_id']);
        while($row=$db->get_row()) {
            $j++;
            if($row['vuz_id']==$vuz['vuz_id']) {
                break;
            }
        }
        $place=str_replace(".",',', round($vuz['place'],1))."|".$j."|".$cntsPlace[$vuz['subj_id']];
    } else {
        $place='';
    }


    $j=0;
    if($vuz['pc']) {
        $db->query('SELECT vuz_id FROM e_plus2 WHERE subj_id=? ORDER BY pc DESC', $vuz['subj_id']);
        while($row=$db->get_row()) {
            $j++;

            if($row['vuz_id']==$vuz['vuz_id']) {
                break;
            }
        }
        $pc=str_replace(".",',', round($vuz['pc'],2))."|".$j."|".$cntsPc[$vuz['subj_id']];
    } else {
        $pc='';
    }

*/
    $j=0;
    $a=false;

    if($vuz['hos']) {
        $db->query('SELECT vuz_id, ROUND(100-noHos) AS b FROM e_plus2 WHERE subj_id=? AND noHos!="100" ORDER BY `b` DESC', $vuz['subj_id']);
        $cntsHos=$db->num_rows();
        while($row=$db->get_row()) {
            $j++;

            if($row['vuz_id']==$vuz['vuz_id']) {
                $a=true;
                break;
            }
        }
        if($a) {
            $hos=(100-$vuz['noHos'])."|".$j."|".$cntsHos;
        } else {
            $hos="";
        }
    } else {
        $hos='';
    }
    echo $hos.'<br>';
    $db->query('
        UPDATE `e_plus` SET 
            `hos`=?
        WHERE `vuz_id`=?',
        $hos,
        $vuz['vuz_id']);

  /*
 // -cost
    $db->query('
        UPDATE `e_plus` SET 
            `forms`=?, `mag`=?, `free`=?, `studs`=?, 
            `shtat`=?, `uchen`=?, `age`=?, `inoPrep`=?,
            `egeFree`=?, `egePay`=?, 
            `place`=?, `pc`=?, `hos`=?
        WHERE `vuz_id`=?',
        $forms, $vuz['mag'], round($vuz['free']), round($vuz['sng'])."|".round($vuz['neSng']),
        round($vuz['shtat']), round($vuz['dn'])."|".round($vuz['kn']), round($vuz['do40'])."|".round($vuz['do65']), round($vuz['inoPrep']),
        $egeFree, $egePay,
        $place, $pc, $hos,
        $vuz['vuz_id']);*/

}




