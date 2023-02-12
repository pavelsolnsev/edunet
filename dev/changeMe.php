<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
define("base_path", "../../system/");

require(base_path . "config.php");
require(base_path . "classes/pdo_class.php");
require(base_path . "classes/user_class.php");


//require("../../secure.edunetwork.ru/classes/auth.php");
/*
$db = new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);
//$pass = 123456;  letta_valya@mail.ru
//$salt = 'xGhE7';
//echo auth::pass_hash($pass,$salt);

if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    if (!$u_id = user::check_session()) {
        header('Location: https://secure' . DOMAIN . '/#vuz' . DOMAIN . '/dev/changeMe.php');
        die;
    }


//        1	daniil@rybakov.me	b66b21e3e8b4c4cc4e428f05070553b1	Gf38e	user	2011-06-30 09:40:03	79104510813	Даниил	Сергеевич	Рыбаков
//        113	christina@edunetwork.ru	fd160e4aa5058c22132222905a5d318b	TM7yI	guest	2017-05-29 12:23:11		Кристина		Бреева
//        121	jafta@mail.ru	59e27b4d4471039bb85885de248610a2	yXpow	guest	2020-12-07 10:06:44		Татьяна	Александровна	Киселева
//        122	chekulaeva1@yandex.ru	1957021b86819853d4b3028027bf756b	VJrir	guest	2021-01-12 06:44:08		Chekulaeva	Alekseevna	Yuliya
//        777	seven.fourty@gmail.com	a7fab00f13d4fb952e5c21f86e6933f1	u4OJ8	user	2011-06-30 09:41:39	79153950746	Петр	Иванович	Мосолов
//        59981	letta_valya@mail.ru	538d3bf4ae683fc86407d37b40db6939	xGhE7	guest	2022-01-31 07:07:45		Valentina		Khalaimova     *
//        999	letta_valya@mail.ru	538d3bf4ae683fc86407d37b40db6939	xGhE7	guest	2022-01-31 07:07:45		Valentina		Khalaimova     *
//        555	sbushuev@synergy.ru	*
//        914	ikalashnikova@synergy.ru	*
//        842 sshlokov@synergy.ru	*


    //if(!in_array($u_id, [1, 777, 113, 701, 121, 122, 59981])) {
    //if(!in_array($u_id, [1, 701, 122, 59981, 999, 555, 914])) {
    if (!in_array($u_id, [122, 999, 555, 914, 826, 842])) {
        die("access denied");
    }
} else {
    //$u_id = 555;
}

if ($_POST['act'] == 'go') {
    $vuz_id = intval($_POST['vuz_id']);
    $db->query('UPDATE user2vuz SET vuz_id = ? WHERE u_id = ?', $vuz_id, $u_id);
    if ($db->affected_rows() == 0) {
        $db->query('SELECT 1 FROM `vuz`.`specs` WHERE `vuz_id` = ? LIMIT 1', $vuz_id); // For vandal check
        if ($db->num_rows()) {
            $specs = '1';
        } else {
            $specs = '0';
        }
        $sql = 'INSERT INTO `vuz`.`user2vuz` (u_id , vuz_id, specs) values (' . $u_id . ', ' . $vuz_id . ', "' . $specs . '")';
        $db->query($sql);
        //echo '<p>SQL => '.$sql.'</p>';
        echo '<p style="color:green">Запись добавлена</p>';
    } else {
        echo '<p style="color:green">Запись обновлена</p>';
    }
    // echo '<p style="color:green">Готово</p>';
}

$db->query('SELECT name FROM vuzes LEFT JOIN user2vuz ON vuz_id = vuzes.id WHERE u_id = ?', $u_id);
$row = $db->get_row();

?>
<p>ID текущего пользователя: <?= $u_id ?></p>
<p>Активный вуз: <?= $row['name'] ?></p>
<form method="POST">
    <input type="hidden" name="act" value="go"/>
    Id вуза <input type="text" name="vuz_id" value="" style="width:50px; text-align: center"/>
    <button type="submit">Переключить</button>
</form>
*/