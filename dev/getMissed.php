<?php

set_time_limit(0);
error_reporting(E_ALL ^ E_NOTICE);
define("base_path", "../../system/");

require(base_path . "config.php");
require(base_path . "classes/pdo_class.php");
require(base_path . "classes/user_class.php");
//require("../../secure.edunetwork.ru/classes/auth.php");

    $db = new db;
    $db->connect($cDbLogin, $cDbPass, $vuzDbName);
    //$pass = 123456;  letta_valya@mail.ru
    //$salt = 'xGhE7';
    //echo auth::pass_hash($pass,$salt);

    if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
        if (!$u_id = user::check_session()) {
            header('Location: https://secure' . DOMAIN . '/#vuz' . DOMAIN . '/dev/getMissed.php');
            die;
        }

        //        122	chekulaeva1@yandex.ru	1957021b86819853d4b3028027bf756b	VJrir	guest	2021-01-12 06:44:08		Chekulaeva	Alekseevna	Yuliya
        //        777	seven.fourty@gmail.com	a7fab00f13d4fb952e5c21f86e6933f1	u4OJ8	user	2011-06-30 09:41:39	79153950746	Петр	Иванович	Мосолов
        //        59981	letta_valya@mail.ru	538d3bf4ae683fc86407d37b40db6939	xGhE7	guest	2022-01-31 07:07:45		Valentina		Khalaimova     *
        //        999	letta_valya@mail.ru	538d3bf4ae683fc86407d37b40db6939	xGhE7	guest	2022-01-31 07:07:45		Valentina		Khalaimova     *
        //        555	sbushuev@synergy.ru	*
        //        914	ikalashnikova@synergy.ru	*
        //        842 sshlokov@synergy.ru	*
        //        845 reserved
        //        846 reserved
        if (!in_array($u_id, [122, 999, 555, 914, 826, 842, 845, 846])) {
            die("access denied");
        }
    }


/*
    <option value="today">С начала суток</option>
    <option value="last_1">Последний час</option>
    <option selected value="last_3">Последние 3 часа</option>
    <option value="last_6">Последние 6 часов</option>
    <option value="last_12">Последние 12 часов</option>
    <option value="last_24">Последние 24 часа</option>
    <option value="all">За все время</option>
*/

    $default_depths = [
        "today"  => 'С начала суток',
        "last_1" => 'Последний час',
        "last_3" => 'Последние 3 часа',
        "last_6" => 'Последние 6 часов',
        "last_12" => 'Последние 12 часов',
        "last_24" => 'Последние 24 часа',
        "all"     => 'За все время',
    ];
    $depths = $default_depths;

    if ($_POST['act'] == 'go') {
        if ($_POST['depth']) {

            switch ($_POST['depth']) {
                case 'today' :
                    $start = date("Y-m-d 00:00:00", time());
                    break;
                case 'last_1' :
                    $start = date("Y-m-d H:i:s", strtotime("now -1 hour"));
                    break;
                case 'last_3' :
                    $start = date("Y-m-d H:i:s", strtotime("now -3 hour"));
                    break;
                case 'last_6' :
                    $start = date("Y-m-d H:i:s", strtotime("now -6 hour"));
                    break;
                case 'last_12' :
                    $start = date("Y-m-d H:i:s", strtotime("now -12 hour"));
                    break;
                case 'last_24' :
                    $start = date("Y-m-d H:i:s", strtotime("now -24 hour"));
                    break;
                case 'all' :
                    $start = '2022-11-01 00:00:00';
                    break;
                default :
                    $selected = 'last_3';
                    $start = date("Y-m-d H:i:s", strtotime("now -3 hour"));
            }

            $selected = $_POST['depth'];

            $where = 'WHERE added >= "'.$start.'"';
            //$where = '';
            $sql = 'SELECT * FROM `ads`.`antispam` '.$where;

            //echo $_POST['depth'].'<br>';
            //echo $sql.'<br>';

            $db->query($sql);
            $qty = $db->num_rows();
            echo "Необработанные лиды за период с $start по настоящее время.<br> Всего выбрано: $qty <br>";
            //echo $db->num_rows().'<br>';
            while ($row = $db->get_row()) {
                //echo $row['id'];
                echo '<pre>'.json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
            }
        }
    }  else {
        $selected = 'last_3';
    }

    $form = '
        <form method="POST">
            <input type="hidden" name="act" value="go"/>
            <label>Проверка лидов, попавших в спам за период: </label>
            <select size="1" name="depth">
                <option disabled>Выберите глубину</option>
    ';

    foreach ($depths as $depth => $info) {

        if ($selected == $depth) {
            $f_select = 'selected';
        } else {
            $f_select = '';
        }
        $form.='<option '.$f_select.' value="'.$depth.'">'.$info.'</option>';
    }
    $form .= '
            </select>
            <button type="submit">Выбрать</button>
        </form>
    ';

    echo $form;

?>
