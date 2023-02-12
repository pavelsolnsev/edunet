<?php


set_time_limit(0);
define("base_path","../../system/");
require(base_path."config.php");
require(base_path."classes/pdo_class.php");
require(base_path."myMailer/myMailer.php");
$db=new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);


$cur=$db->query('
  SELECT 
    vuzes.id, users.id AS u_id, vuzes.name, users.email, IFNULL(user_requests.email, "") AS request, CONCAT(f_name," ", s_name, " ", surname) AS u_name 
  FROM 
    `user2vuz` LEFT JOIN 
    vuzes ON vuzes.id=`user2vuz`.vuz_id LEFT JOIN 
    specs ON specs.vuz_id=vuzes.id LEFT JOIN 
    auth.users ON users.id=u_id LEFT JOIN 
user_requests ON user_requests.v_id=vuzes.id
  WHERE (`packetEnd`<DATE(NOW()) OR `packetEnd` IS NULL) AND users.id>1000 AND (specs.lastEdit IS NULL OR specs.lastEdit <"'.date("Y").'-01-01") 
  GROUP BY vuzes.id');
if($db->num_rows()) {
    $mailer = new myMailer;

    //$date=date('d.m.Y', strtotime("+1 week"));
    while ($row = $db->get_row($cur)) {

        $db->query('SELECT count(*) AS c FROM `vuzBan` WHERE `vuz_id`=? GROUP BY vuz_id', $row['id']);
        if($db->num_rows()) {
            $t=$db->get_row();
            $ban=$t['c']+1;
        }
        else {
            $ban=1;
        }

        $db->query('
        INSERT INTO vuzBan(`vuz_id`, `reason`, `request`, `email`, `name`, `disconnect`, `endBan`)
        VALUES (?, "inactive", ?, ?, ?, NOW(), "'.(date("Y")+$ban).'-07-01")', $row['id'], $row['request'], $row['email'], $row['u_name'] );

        if ($row['u_id'] >= 1000) {
            $db->query('DELETE FROM `vuz`.`user2vuz` WHERE `vuz_id`=? AND `u_id`=?', $row['id'], $row['u_id']);
        }

        $mailer->start();
        $msg = '
                    <p>
                            Вы получили это письмо, потому что являлись официальным представителем вуза «' . $row['name'] . '»
                            на проекте vuz.EduNetwork.ru
                    </p>
                    <p>
                            В соответствии с <a href="https://vuz.EduNetwork.ru/connect/rules">правилами проекта</a>,
                            вы отключены от панели управления вузом, а на вуз наложено ограничение на дальнейшее участие, в связи нарушением контрольных сроков актуализации информации на проекте.
                    </p>';

        $mailer->prepareMsg(
            $row['u_name'],
            'https://vuz.edunetwork.ru',
            $msg,
            'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
        );
        $a = $mailer->send('Отключение от панели вуза ' . $row['name'], $row['email']);
        $db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Бан для ' . $row['name'] . ' - badStart", ?, ?, 0)', $a, $row['email']);
        /*

        $mailer->start();
        $msg = '
                    <p>
                            Вы получили это письмо, потому что являетесь официальным представителем вуза «' . $row['name'] . '»
                            на проекте vuz.EduNetwork.ru
                    </p>
                    <p>
                            Проводя очередную проверку деятельности представителей вузов, мы обнаружили что данные об
                            образовательных программах вашего вуза еще необновлены.
                    </p>
                    <p>
                            Напоминаем Вам о том, что в соответствии с <a href="https://vuz.EduNetwork.ru/connect/rules">правилами проекта</a>,
                            Вы возложили на себя обязанность своевременно акутализировать информацию о своем учебном заведении до 15 июня '.date("Y").' года.
В противном случае, вы будете отключены от панели управления вузом, а дальнейшее участие вашего
                            вуза в проекте будет ограничено на срок от одного календарного года.
                    </p>
                    <p>
                            Если у Вас возникли трудности при выполнении данной задачи, то рекомендуем Вам ознакомиться с
                            соответствующими инструкциями в <a href="https://vuz.EduNetwork.ru/vuz_panel/">панели управления вузом</a>.
                    </p>
                    <p>
                            Так же Вы можете доверить заполнение данных нашим сотрудникам на коммерческом основе,
                            путем приобретения <a href="https://vuz.edunetwork.ru/ads#packet">пакета сопровождения вуза</a> на проекте.
                    </p>';

        $mailer->prepareMsg(
            $row['u_name'],
            'https://vuz.edunetwork.ru',
            $msg,
            'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
        );
        $a = $mailer->send('Предупреждение - панель вуза ' . $row['name'], $row['email']);
        $db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Пребан для ' . $row['name'] . ' - badStart", ?, ?, 0)', $a, $row['email']);
*/
    }
}
?>