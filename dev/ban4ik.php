<? 
set_time_limit(0);
define("base_path","../../system/");

require(base_path."config.php");
require(base_path."classes/pdo_class.php");
//require(base_path."classes/date.php");
require(base_path."myMailer/myMailer.php");
$db=new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);

$act=$_GET['act'];

switch($act) {
	case 'start':
		$cur=$db->query('
			SELECT 
				a.`u_id` , a.`vuz_id` , count(*) AS cnt, `users`.`email`, CONCAT(`users`.`f_name`, " ", `users`.`s_name`, " ", `users`.`surname`) AS u_name, `vuzes`.`abrev`, (
					SELECT `id` FROM `vuz`.`preBan` 
					WHERE `vuz_id`=a.vuz_id AND `preBan`.`result`="" AND `preBan`.`end`>=DATE(NOW()) AND `preBan`.`reason`="badStart"
					LIMIT 1
				) AS preBan, (
					SELECT `email` FROM `vuz`.`user_requests` 
					WHERE `user_requests`.`v_id`=a.`vuz_id` AND `u_id`=a.`u_id` AND `user_requests`.`status`="done" LIMIT 1
				) AS req_email	
			FROM (
					SELECT `u_id` , `vuz_id` , `connect`
					FROM `vuz`.`user2vuz`
					WHERE `connect` < DATE_SUB(NOW() , INTERVAL 7 DAY) AND `connect` > DATE_SUB(NOW() , INTERVAL 1 MONTH) 
				) a LEFT JOIN 
				`vuz`.`specs` ON `specs`.`vuz_id` = a.`vuz_id` LEFT JOIN
				`auth`.`users` ON `users`.`id`=a.`u_id` LEFT JOIN
				`vuz`.`vuzes` ON `vuzes`.`id`=a.`vuz_id`
			WHERE 
				(`packetEnd` IS NULL OR `packetEnd`<DATE(NOW())) AND a.`u_id` NOT IN (1, 777, 111, 113) AND `vedom`="0" AND
				(`lastEdit` < DATE(a.`connect`) OR `lastEdit` IS NULL)
			GROUP BY a.`vuz_id`
			HAVING cnt>1');
		if($db->num_rows()) {
			$mailer=new myMailer;
			$date=date('d.m.Y', strtotime("+1 week"));
			$sqldate=date('Y-m-d', strtotime("+1 week"));
			while($row=$db->get_row($cur)) {
				$mailer->start();
				if($row['preBan']) {
					$db->query('SELECT count(*) AS c FROM `vuz`.`vuzBan` WHERE `vuz_id`=?', $row['vuz_id']);
					$srok=$db->get_row();
					$srok=1+$srok['c'];
					$msg='
						<p>
							Вы получили это письмо, потому что являлись официальным представителем «'.$row['abrev'].'» 
							на проекте <a href="https://vuz.edunetwork.ru">vuz.EduNetwork.ru</a>.
						</p>
						<p>
							Сегодня истек контрольный срок на обновление данных об образовательных программах вуза. 
							В связи с данным фактом, в соответствии с 
							<a href="https://vuz.edunetwork.ru/connect/rules">правилами проекта</a>, вы отключены от панели управления вузом, 
							а на вуз наложено ограничение на дальнейшее участие в проекте сроком до '.date('d.m.Y', strtotime('+'.$srok.' year')).'.
						</p>
						<p>
							В период ограничения, участие вуза в проекте возможно только на коммерческой основе путем приобретения 
							<a href="https://vuz.edunetwork.ru/ads#packet">пакета сопровождения вуза</a> на проекте.
						</p>';
					$db->query('DELETE FROM `vuz`.`user2vuz` WHERE `vuz_id`=? AND `u_id`=?', $row['vuz_id'], $row['u_id']);
					$db->query('DELETE FROM `system`.`user2service` WHERE `u_id`=? AND `service`="vuz" AND `role`="panel"', $row['u_id']);	
					$db->query('UPDATE `vuz`.`preBan` SET `result`="ban" WHERE `id`=?', $row['preBan']);
					$db->query('
						INSERT INTO vuz.vuzBan(
							`vuz_id`, `reason`, `request`, `email`, `name`, `disconnect`, `endBan`
						) VALUES(
							?, "badStart", ?, ?, ?, DATE(NOW()), DATE(DATE_ADD(NOW(), INTERVAL '.$srok.' YEAR))
						)', 
						$row['vuz_id'], $row['req_email'], $row['email'], $row['u_name']);
					$mailer->prepareMsg(
						$row['u_name'],
						'https://vuz.edunetwork.ru',
						$msg,
						'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
					);
					$a=$mailer->send('Отключение от панели вуза '.$row['abrev'], $row['email']);
					$db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Бан для '.$row['abrev'].' - badStart '.$srok.' год", ?, ?, 0)', $a, $row['email']);	
				}
				else {
					$msg='
						<p>
							Вы получили это письмо, потому что являетесь официальным представителем вуза «'.$row['abrev'].'» 
							на проекте vuz.EduNetwork.ru
						</p>
						<p>
							Проводя очередную проверку деятельности представителей вузов, мы обнаружили что данные об 
							образовательных программах вашего вуза еще не обновлены.
						</p>
						<p>
							Напоминаем Вам о том, что в соответствии с <a href="https://vuz.edunetwork.ru/connect/rules">правилами проекта</a>, 
							Вы возложили на себя обязанность обновить всю информацию о вузе в течение 14 календарных дней с момента 
							подключения к панели вуза.
							Таким образом, контрольный срок обновления информации истекает '.$date.'.
						</p>
						<p>
							В случае нарушения данного правила, вы будете отключены от панели вуза, а дальнейшее участие вашего 
							вуза в проекте будет ограничено на срок от одного календарного года.
						</p>
						<p>
							Если у Вас возникли трудности при выполнении данной задачи, то рекомендуем Вам ознакомиться с 
							соответствующими инструкциями в <a href="https://vuz.edunetwork.ru/vuz_panel/">панели управления вузом</a>.
						</p>
						<p>
							Так же Вы можете доверить заполнение данных нашим сотрудникам на коммерческом основе, 
							путем приобретения <a href="https://vuz.edunetwork.ru/ads#packet">пакета сопровождения вуза</a> на проекте.
						</p>';
					$db->query('
						INSERT INTO `vuz`.`preBan` (`u_id`, `vuz_id`, `reason`, `send`, `end`) VALUES(?, ?, "badStart", NOW(), ?)',
						$row['u_id'], $row['vuz_id'], $sqldate);
					$mailer->prepareMsg(
						$row['u_name'],
						'https://vuz.edunetwork.ru',
						$msg,
						'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
					);
					$a=$mailer->send('Предупреждение - панель вуза '.$row['abrev'], $row['email']);
					$db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Пребан для '.$row['abrev'].' - badStart", ?, ?, 0)', $a, $row['email']);
				}
			}
		}
	break;
	case '0specs':
		$cur=$db->query('
			SELECT 
				`user2vuz`.`u_id`, `user2vuz`.`vuz_id`, SUM(`specs`.`id` IS NOT NULL) as cnt, `user2vuz`.`specs`,
				`users`.`email`, CONCAT(`users`.`f_name`, " ", `users`.`s_name`) AS u_name, `vuzes`.`abrev`, (
					SELECT `email` FROM `vuz`.`user_requests` 
					WHERE `user_requests`.`v_id`=`user2vuz`.`vuz_id` AND `u_id`=`user2vuz`.`u_id` AND `user_requests`.`status`="done" LIMIT 1
				) AS req_email, (
					SELECT `id` FROM `vuz`.`preBan` 
					WHERE `vuz_id`=`user2vuz`.`vuz_id` AND `preBan`.`result`="" AND `reason`="0specs"  AND `preBan`.`end`>=NOW()
					LIMIT 1
				) AS preBan
				 
			FROM 
				`vuz`.`user2vuz` LEFT JOIN 
				`vuz`.`specs` ON `user2vuz`.`vuz_id`=`specs`.`vuz_id` LEFT JOIN
				`vuz`.`vuzes` ON `user2vuz`.`vuz_id`=`vuzes`.`id` LEFT JOIN
				`auth`.`users` ON `users`.`id`=`user2vuz`.`u_id`
			WHERE 
				(`packetEnd` IS NULL OR `packetEnd`<DATE(NOW())) AND `user2vuz`.`u_id` NOT IN(1, 777, 1488) AND `vedom`="0"
			GROUP BY `user2vuz`.`vuz_id`
			HAVING cnt=0');
			
		if($db->num_rows()) {
			$mailer=new myMailer;
			$date=date('d.m.Y', strtotime("+1 week"));
			$sqldate=date('Y-m-d', strtotime("+1 week"));
			while($row=$db->get_row($cur)) {
				$mailer->start();
				if($row['preBan']) {
					$db->query('SELECT count(*) AS c FROM `vuz`.`vuzBan` WHERE `vuz_id`=?', $row['vuz_id']);
					$srok=$db->get_row();
					if($row['specs']=='1') {
						$reason='0 specs';
						$srok=3+$srok['c'];
					}
					else {
						$reason='badStart';
						$srok=1+$srok['c'];
					}
					$msg='
						<p>
							Вы получили это письмо, потому что являлись официальным представителем «'.$row['abrev'].'» 
							на проекте <a href="https://vuz.edunetwork.ru">vuz.EduNetwork.ru</a>.
						</p>
						<p>
							Сегодня истек контрольный срок на добавление данных об образовательных программах вуза. 
							В связи с данным фактом, в соответствии с 
							<a href="https://vuz.edunetwork.ru/connect/rules">правилами проекта</a>, вы отключены от панели управления вузом, 
							а на вуз наложено ограничение на дальнейшее участие в проекте сроком до '.date('d.m.Y', strtotime('+'.$srok.' year')).'.
						</p>
						<p>
							В период ограничения, участие вуза в проекте возможно на только на коммерческой основе путем приобретения 
							<a href="https://vuz.edunetwork.ru/ads#packet">пакета совпровождения вуза</a> на проекте.
						</p>';
					$db->query('DELETE FROM `vuz`.`user2vuz` WHERE `vuz_id`=? AND `u_id`=?', $row['vuz_id'], $row['u_id']);
					$db->query('DELETE FROM `system`.`user2service` WHERE `u_id`=? AND `service`="vuz" AND `role`="panel"', $row['u_id']);	
					$db->query('UPDATE `vuz`.`preBan` SET `result`="ban" WHERE `id`=?', $row['preBan']);
					$db->query('
						INSERT INTO vuz.vuzBan(
							`vuz_id`, `reason`, `request`, `email`, `name`, `disconnect`, `endBan`
						) VALUES(
							?, ?, ?, ?, ?, DATE(NOW()), DATE(DATE_ADD(NOW(), INTERVAL '.$srok.' YEAR))
						)', 
					$row['vuz_id'], $reason, $row['req_email'], $row['email'], $row['u_name']);
					$mailer->prepareMsg(
						$row['u_name'],
						'https://vuz.edunetwork.ru',
						$msg,
						'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
					);
					$a=$mailer->send('Отключение от панели вуза '.$row['abrev'], $row['email']);
					$db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Бан для '.$row['abrev'].' - '.$reason.' '.$srok.' год", ?, ?, 0)', $a, $row['email']);	
				}
				else {
					$msg='
						<p>
							Вы получили это письмо, потому что являетесь официальным представителем вуза «'.$row['abrev'].'» 
							на проекте vuz.EduNetwork.ru
						</p>
						<p>
							Проводя очередную проверку деятельности представителей вузов, мы обнаружили что данные об 
							образовательных программах вашего вуза отсутствуют.
						</p>
						<p>
							Напоминаем Вам о том, что в соответствии с <a href="https://vuz.edunetwork.ru/connect/rules">правилами проекта</a>, 
							Вы возложили на себя обязанность предоставлять <b>полные</b>, корректные и актуальные данные.
							Пожалуйста заполните информацию об образовательных программах вашего вуза до '.$date.'.
						</p>
						<p>
							В случае нарушения данного правила, вы будете отключены от панели вуза, а дальнейшее участие вашего 
							вуза в проекте будет ограничено на срок от одного календарного года.
						</p>
						<p>
							Если у Вас возникли трудности при выполнении данной задачи, то рекомендуем Вам ознакомиться с 
							соответствующими инструкциями в <a href="https://vuz.edunetwork.ru/vuz_panel/">панели управления вузом</a>.
						</p>
						<p>
							Так же Вы можете доверить заполнение данных нашим сотрудникам на коммерческом основе, 
							путем приобретения <a href="https://vuz.edunetwork.ru/ads#packet">пакета совпровождения вуза</a> на проекте.
						</p>';
					$db->query('
						INSERT INTO `vuz`.`preBan` (`u_id`, `vuz_id`, `reason`, `send`, `end`) VALUES(?, ?, ?, NOW(), ?)',
						$row['u_id'], $row['vuz_id'], '0specs', $sqldate);
					$mailer->prepareMsg(
						$row['u_name'],
						'https://vuz.edunetwork.ru',
						$msg,
						'Спасибо за внимание к проекту,<br />
						EduNetwork.ru'
					);
					$a=$mailer->send('Предупреждение - панель вуза '.$row['abrev'], $row['email']);
					$db->query('INSERT INTO `system`.`mail_reports`(`type`,`mess`,`email`,`service`) VALUES("Пребан для '.$row['abrev'].' - 0specs", ?, ?, 0)', $a, $row['email']);
				}
			}
		}
	break;
}

?>