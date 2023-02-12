<?
class openDays {
	static function show($v_id) {
		global $db;

		$page = intval($_POST['page']); // page num
		$rp = intval($_POST['rp']); // count per page

		if (!$page) {
			$page = 1;
		}
		if (!$rp) {
			$rp = 15;
		}
		$start = (($page-1) * $rp);

		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
		header("Cache-Control: no-cache, must-revalidate" );
		header("Pragma: no-cache" );
		header("Content-type: text/x-json");

		$res=$db->query(
         	'SELECT SQL_CALC_FOUND_ROWS * FROM (
				SELECT 
					`openDays`.`id`, `openDays`.`name`, `openDays`.`start`, "Общий вуза" AS vuz
				FROM `vuz`.`openDays`  
				WHERE `openDays`.`vuz_id`=? AND `start`>NOW() AND `openDays`.`subvuz_id` IS NULL
				UNION SELECT 
					`openDays`.`id`, `openDays`.`name`, `openDays`.`start`, `subvuz`.`name` AS vuz
				FROM `vuz`.`openDays` LEFT JOIN `vuz`.`subvuz` ON `openDays`.`subvuz_id`=`subvuz`.`id`
				WHERE  `openDays`.`vuz_id`=? AND `start`>NOW() AND `openDays`.`subvuz_id` IS NOT NULL
			) as a ORDER BY a.`start` DESC LIMIT ?, ?', $v_id, $v_id, $start, $rp);
        $db->query('SELECT FOUND_ROWS() as cnt');
        $c=$db->get_row();
		
		$json =
        '{
            "page":"'.$page.'",
            "total":'.$c['cnt'].',
            "rows":[';
		if($c['cnt']) {
			while($row=$db->get_row($res)) {
				$json.='
	            {
	                "id":"'.$row['id'].'",
	                "cell":[
	                	"'.$row['name'].'",
	                    "'.$row['vuz'].'",
	                    "'.date::timeSt2normal($row['start']).'"
	                ]
	            },';
			}
			$json=substr($json,0,strlen($json)-1);
			$json.='
	            ]
	        }';
		}
		else {
			$json.=']}';
		}
		echo $json;
    }

    static function addForm($v_id)
    {
        global $db;

        $db->query('SELECT `id`, `name` FROM `vuz`.`subvuz` WHERE `vuz_id`=?', $v_id);
        if ($db->num_rows()) {
            $type = '
				<div>
					<label>Тип дня открытых дверей:
						<div class="hint">
							<p>Общий день открытых дверей ‒ день открытых дверей не привязанный к единственному подразделению вуза (общеуниверситетский).</p>
							<p>День открытых дверей подразделения ‒ день открытых дверей проводимый для абитуриентов конкретного подразделения.</p>
						</div>
					</label>
					<select name="type" id="type">
						<option value="vuz">Общий вуза</option>
						<option value="sv">Подразделения</option>
					</select>
				</div>';
            $sv   = '
				<div>
					<label>Подразделение:</label>
					<select name="sv" id="sv">';
            while ($row = $db->get_row()) {
                $sv .= '<option value="'.$row['id'].'">'.$row['name'].'</option>';
            }
            $sv .= '
					</select>
				</div>';
        } else {
            $sv = '';
        }

        $db->query(
            'SELECT `vuzes`.`subj_id`, `subjects`.`name` as subject, `cities`.`name` as city FROM `vuz`.`vuzes` LEFT JOIN `general`.`subjects` ON `vuzes`.`subj_id`=`subjects`.`id` LEFT JOIN `general`.`cities` ON `vuzes`.`city_id`=`cities`.`id` WHERE `vuzes`.`id`=?',
            $v_id
        );
        $row = $db->get_row();

        $subjCity = $row['subject'];
        if ($row['subj_id'] != 77 && $row['subj_id'] != 78) {
            $subjCity .= ', '.$row['type'].' <span id="locat">'.$row['city'].'</span>';
        } else {
            $subjCity = '<span id="locat">'.$subjCity.'</span>';
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/openDayAdd.html');
        $tpl->replace([
            '[subjCity]' => $subjCity,
            '[type]'     => $type,
            '[sv]'       => $sv,
        ]);
        $tpl->out();
    }

    static function add(int $v_id)
    {
        global $db;

        $name = htmlspecialchars(trim($_POST['name']));
        if (mb_strtolower($name, 'UTF-8') === 'День открытых дверей') {
            $name = '';
        }
        $sv    =& $_POST['sv'];
        $date  =& $_POST['date'];
        $hours =& $_POST['hours'];
        $mins  =& $_POST['mins'];
        if ($_POST['online'] === '1') {
            $online = '1';
            $addr   = '';
        } else {
            $online = '0';
            $addr   = htmlspecialchars(trim($_POST['address']));
        }
        $url = htmlspecialchars(trim($_POST['url']));
        if ($url && !preg_match(
                "/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                $url
            )) {
            die('Некорректный адрес мероприятия');
        }

        if ($_POST['type'] == 'sv') {
            if (!preg_match('/^\d+$/', $sv)) {
                die('Произошла внутренняя ошибка, попробуйте еще раз');
            }
            $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `id`=? AND `vuz_id`=?', $sv, $v_id);
            if (!preg_match('/^\d+$/', $sv)) {
                die('Произошла внутренняя ошибка, попробуйте еще раз');
            }
        } else {
            $sv = null;
        }
        if (!preg_match('/\d{2,2}\.\d{2,2}\.\d{4,4}/', $date)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        if (!preg_match('/^\d+$/', $hours)) {
            die('Произошла внутренняя ошибка, попробуйте еще раз');
        }
        if (!preg_match('/^\d+$/', $mins)) {
            die('Произошла внутренняя ошибка, попробуйте еще раз');
        }

        $date = date::normal2mysql($date);
        if ($date < date('Y-m-d')) {
            die('Указанная дата уже прошла');
        }

        if ($hours > 23) {
            die('Некорректно указано время начала мероприятия');
        }
        if ($mins > 59) {
            die('Некорректно указано время начала мероприятия');
        }
        $date .= ' '.$hours.':'.$mins.':00';

        $db->query(
            '
			INSERT INTO `vuz`.`openDays`(
				`name`, `vuz_id`, `subvuz_id`, `start`, `address`, `online`, `url`
			) VALUES(?, ?, ?, ?, ?, ?, ?)',
            $name,
            $v_id,
            $sv,
            $date,
            $addr,
            $online,
            $url
        );
        $id = $db->insert_id();
        $db->query(
            '
			INSERT INTO `vuz`.`vuzRequests`(
				`vuz_id`, `type`, `oldVal`, `newVal`, `o_id`
			) VALUES (
			?, "dodName", "", ?, ?), (?, "dodAddr", "", ?, ?)',
            $v_id,
            $name,
            $id,
            $v_id,
            $addr,
            $id
        );

        echo 'success';
    }

    static function editForm(int $v_id)
    {
        global $db;

        $id =& $_POST['id'];
        if (!preg_match('/^\d+$/', $id)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query(
            '
			SELECT 
				`openDays`.`name`, `openDays`.`subvuz_id`, `openDays`.`start`, 
				`openDays`.`address`, `openDays`.`online`, `openDays`.`url`,  
				`vuzes`.`subj_id`, `subjects`.`name` as subject, `cities`.`name` as city
			FROM 
				`vuz`.`openDays` LEFT JOIN 
				`vuz`.`vuzes` ON `openDays`.`vuz_id`=`vuzes`.`id` LEFT JOIN 
				`general`.`subjects` ON `vuzes`.`subj_id`=`subjects`.`id` LEFT JOIN 
				`general`.`cities` ON `vuzes`.`city_id`=`cities`.`id` 
			WHERE `openDays`.`id`=? AND `openDays`.`vuz_id`=?',
            $id,
            $v_id
        );
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $day    = $db->get_row();
        $online = (($day['online']) ? (' checked="checked"') : (''));

        $subjCity = $day['subject'];
        if ($day['subj_id'] != 77 && $day['subj_id'] != 78) {
            $subjCity .= ', '.$day['type'].' <span id="locat">'.$day['city'].'</span>';
        } else {
            $subjCity = '<span id="locat">'.$subjCity.'</span>';
        }

        $db->query('SELECT `id`, `name` FROM `vuz`.`subvuz` WHERE `vuz_id`=?', $v_id);
        if ($db->num_rows()) {
            $type = '
				<div>
					<label>Тип дня открытых дверей:
						<div class="hint">
							<p>Общий день открытых дверей ‒ день открытых дверей не привязанный к единственному подразделению вуза (общеуниверситетский).</p>
							<p>День открытых дверей подразделения ‒ день открытых дверей проводимый для абитуриентов конкретного подразделения.</p>
						</div>
					</label>
					<select name="type" id="type">
						<option value="vuz"'.(($day['subvuz_id']) ? ('') : (' selected="selected"')).'>Общий вуза</option>
						<option value="sv"'.(($day['subvuz_id']) ? (' selected="selected"') : ('')).'>Подразделения</option>
					</select>
				</div>';
            $sv   = '
				<div>
					<label>Подразделение:</label>
					<select name="sv" id="sv">';
            while ($row = $db->get_row()) {
                $sv .= '<option value="'.$row['id'].'"'.(($row['id'] == $day['subvuz_id']) ? (' selected="selected"') : ('')).'>'.$row['name'].'</option>';
            }
            $sv .= '
					</select>
				</div>';
        } else {
            $sv = '';
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/openDayEdit.html');
        $tpl->replace([
            '[subjCity]' => $subjCity,
            '[type]'     => $type,
            '[sv]'       => $sv,

            '[id]'     => $id,
            '[name]'   => $day['name'],
            '[date]'   => date::mysql2normal(substr($day['start'], 0, 10)),
            '[hours]'  => substr($day['start'], 11, 2),
            '[mins]'   => substr($day['start'], 14, 2),
            '[addr]'   => $day['address'],
            '[online]' => $online,
            '[url]'    => $day['url'],
        ]);
        $tpl->out();
    }

    static function edit(int $v_id)
    {
        global $db;

        $id   =& $_POST['id'];
        $name = htmlspecialchars(trim($_POST['name']));
        if (mb_strtolower($name, 'UTF-8') === 'День открытых дверей') {
            $name = '';
        }
        $sv    =& $_POST['sv'];
        $date  =& $_POST['date'];
        $hours =& $_POST['hours'];
        $mins  =& $_POST['mins'];

        if ($_POST['online'] === '1') {
            $online = '1';
            $addr   = '';
        } else {
            $online = '0';
            $addr   = htmlspecialchars(trim($_POST['address']));
        }
        $url = htmlspecialchars(trim($_POST['url']));
        if ($url && !preg_match(
                "/\b(?:(?:https?):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",
                $url
            )) {
            die('Некорректный адрес мероприятия');
        }

        if (!preg_match('/^\d+$/', $id)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('SELECT 1 FROM `vuz`.`openDays` WHERE `openDays`.`id`=? AND `openDays`.`vuz_id`=?', $id, $v_id);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        if ($_POST['type'] == 'sv') {
            if (!preg_match('/^\d+$/', $sv)) {
                die('Произошла внутренняя ошибка, попробуйте еще раз');
            }
            $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `id`=? AND `vuz_id`=?', $sv, $v_id);
            if (!preg_match('/^\d+$/', $sv)) {
                die('Произошла внутренняя ошибка, попробуйте еще раз');
            }
        } else {
            $sv = null;
        }
        if (!preg_match('/\d{2,2}\.\d{2,2}\.\d{4,4}/', $date)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        if (!preg_match('/^\d+$/', $hours)) {
            die('Произошла внутренняя ошибка, попробуйте еще раз');
        }
        if (!preg_match('/^\d+$/', $mins)) {
            die('Произошла внутренняя ошибка, попробуйте еще раз');
        }

        $date = date::normal2mysql($date);
        if ($date < date('Y-m-d')) {
            die('Указанная дата уже прошла');
        }

        if ($hours > 23) {
            die('Некорректно указано время начала мероприятия');
        }
        if ($mins > 59) {
            die('Некорректно указано время начала мероприятия');
        }

        $date .= ' '.$hours.':'.$mins.':00';

        $db->query(
            '
			INSERT INTO `vuz`.`vuzRequests`(
				`vuz_id`, `type`, `oldVal`, `newVal`, `o_id`
			) VALUES (
			?, "dodName", (SELECT `name` FROM `openDays` WHERE `id`=?), ?, ?), (
			?, "dodAddr", (SELECT `address` FROM `openDays` WHERE `id`=?), ?, ?)',
            $v_id,
            $id,
            $name,
            $id,
            $v_id,
            $id,
            $addr,
            $id
        );

        $db->query(
            "
			UPDATE `vuz`.`openDays` SET 
			`name`=?, `subvuz_id`=?, `start`=?, `address`=?, `online`=?, `url`=? WHERE `id`=?",
            $name,
            $sv,
            $date,
            $addr,
            $online,
            $url,
            $id
        );
        echo 'success';
    }

    static function del(int $v_id)
    {
        global $db;
        $id =& $_POST['id'];

        if (!preg_match('/^\d+$/', $id)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('SELECT DATE(`start`) AS date FROM `vuz`.`openDays` WHERE `id`=? AND `vuz_id`=?', $id, $v_id);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $row = $db->get_row();

        if ($row['date'] < date("Y-m-d")) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('DELETE FROM `vuz`.`openDays` WHERE `id`=?', $id);

        echo 'success';
    }
}

