<?
class subvuzs {

    static function getUserId()
    {
        global $db;
        $sess =& $_COOKIE['sess'];
        if (preg_match('/^[a-f0-9]{32,32}$/i', $sess)) {
            $db->query(
                'SELECT `u_id` FROM `system`.`sessions` WHERE `id` = ? AND `dieTime` > ? LIMIT 1',
                $sess,
                time()
            );
            if ($db->num_rows()) {
                $row = $db->get_row();
                return ((int)$row['u_id']);
            }
        }
        return (0);
    }

    static function getUserParams()
    {
        //$u_ip   = $_SERVER['REMOTE_HOST'];
        $u_ip   = $_SERVER['REMOTE_ADDR'] != NULL ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        return [
            'u_id'       => self::getUserId(),
            'u_ip'       => $u_ip,
            'time_stamp' => date('Y-m-d H:i:s')
        ];
    }

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


		$res=$db->query('
            SELECT  SQL_CALC_FOUND_ROWS
              `id`, `name`, `address`
            FROM 
              `vuz`.`subvuz` 
            WHERE `vuz_id`=? ORDER BY `id` DESC LIMIT ?, ?', $v_id, $start, $rp);
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
	                    "'.$row['address'].'"
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

        $db->query(
            '
			SELECT 
				`subjects`.`id` as subj_id, `subjects`.`name` as subject, 
				`cities`.`type`, `cities`.`name` as city
			FROM
				`vuz`.`vuzes` LEFT JOIN 
				`general`.`subjects` ON `vuzes`.`subj_id`=`subjects`.`id` LEFT JOIN 
				`general`.`cities` ON `cities`.`id`=`vuzes`.`city_id`
			WHERE `vuzes`.`id`=?',
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
        $tpl->start('tpl/forms/svAdd.html');
        $tpl->replace([
            '[subjCity]' => $subjCity,
        ]);
        $tpl->out();
    }

    static function add(int $vusId)
    {
        global $db;

        $name = htmlspecialchars(trim($_POST['name']));
        $addr = htmlspecialchars(trim($_POST['address']));

        if (!mb_strlen($name)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        if (!mb_strlen($addr)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query(
            '
			INSERT INTO `vuz`.`subvuz`(
				`vuz_id`,`name`,`address`
			) VALUES (
				?, ?, ?
			)',
            $vusId,
            $name,
            $addr
        );
        $o_id = $db->insert_id();

        $u_params = self::getUserParams();
        $u_id = $u_params['u_id'];
        $u_ip = $u_params['u_ip'];
        //$u_ts = $u_params['time_stamp'];

        $db->query(
            '
			INSERT INTO `vuz`.`vuzRequests`(
				`vuz_id`, `type`, `oldVal`, `newVal`, `o_id`, `user_id`, `user_ip`
			) VALUES (?, "suName", "", ?, ?, ?, ?), (?, "suAddr", "", ?, ?, ?, ?)',
            $vusId,
            $name,
            $o_id,
            $vusId,
            $addr,
            $o_id,
            $u_id,
            $u_ip
        );

        echo 'success';
    }

    static function editForm($u_id, $v_id)
    {
        global $db;

        $id =& $_POST['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::hack(
                'vuz',
                '/панель вуза/форма редактирования подразделения',
                'Некорректный тип id подразделения',
                'BadParam1',
                $u_id
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query(
            '
			SELECT 
				`subjects`.`name` as subject, `vuzes`.`subj_id`,
				`cities`.`type`, `cities`.`name` as city, `subvuz`.`name`,  
				`subvuz`.`address`
			FROM 
				`vuz`.`subvuz` LEFT JOIN 
				`vuz`.`vuzes` ON `subvuz`.`vuz_id`=`vuzes`.`id` LEFT JOIN 
				`general`.`subjects` ON `vuzes`.`subj_id`=`subjects`.`id` LEFT JOIN 
				`general`.`cities` ON `cities`.`id`=`vuzes`.`city_id`
			WHERE `subvuz`.`id`=? AND `subvuz`.`vuz_id`=?',
            $id,
            $v_id
        );
        if (!$db->num_rows()) {
            myErr::hack(
                'vuz',
                '/панель вуза/форма редактирования подразделения',
                'Подмена id подразделения',
                'SpoofParam1',
                $u_id
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $row = $db->get_row();

        $subjCity = $row['subject'];
        if ($row['subj_id'] != 77 && $row['subj_id'] != 78) {
            $subjCity .= ', '.$row['type'].' <span id="locat">'.$row['city'].'</span>';
        } else {
            $subjCity = '<span id="locat">'.$subjCity.'</span>';
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/svEdit.html');
        $tpl->replace([
            '[subjCity]' => $subjCity,
            '[city]'     => $row['city'],
            '[name]'     => $row['name'],
            '[address]'  => $row['address'],
            '[id]'       => $id,
        ]);
        $tpl->out();
    }

    static function edit($u_id, $v_id)
    {
        global $db;
        $id =& $_POST['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::hack(
                'vuz',
                '/панель вуза/редактирование подразделения',
                'Некорректный тип id подразделения',
                'BadParam1',
                $u_id
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `id`=? AND `vuz_id`=?', $id, $v_id);
        if (!$db->num_rows()) {
            myErr::hack(
                'vuz',
                '/панель вуза/редактирование подразделения',
                'Подмена id подразделения',
                'SpoofParam1',
                $u_id
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $name = htmlspecialchars(trim($_POST['name']));
        $addr = htmlspecialchars(trim($_POST['address']));

        $u_params = self::getUserParams();
        $u_id = $u_params['u_id'];
        $u_ip = $u_params['u_ip'];


        $db->query(
            '
            INSERT INTO `vuz`.`vuzRequests`(
                    `vuz_id`, `type`, `oldVal`, `newVal`, `o_id`, `user_id`, `user_ip`
            ) 
            VALUES 
                   (?, "suName", (SELECT `name` FROM `subvuz` WHERE `id`=?), ?, ?, ?, ?), 
                   (?, "suAddr", (SELECT `address` FROM `subvuz` WHERE `id`=?), ?, ?, ?, ?)',
            $v_id,
            $id,
            $name,
            $id,
            $u_id,
            $u_ip,
            $v_id,
            $id,
            $addr,
            $id,
            $u_id,
            $u_ip
        );
        $db->query(
            '
			UPDATE `vuz`.`subvuz` SET 
				`name`=?,`address`=? 
			WHERE `id`=?',
            $name,
            $addr,
            $id
        );
        echo 'success';
    }

    static function del($u_id, $v_id)
    {
        global $db;
        $id =& $_POST['id'];
        if (!preg_match('/^\d+$/', $id)) {
            myErr::hack(
                'vuz',
                '/панель вуза/удаление подразделения',
                'Некорректный тип id подразделения',
                'BadParam1',
                $u_id
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `id`=? AND `vuz_id`=?', $id, $v_id);
        if (!$db->num_rows()) {
            myErr::hack('vuz', '/панель вуза/удаление подразделения', 'Подмена id подразделения', 'SpoofParam1', $u_id);
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('DELETE FROM `vuz`.`specs` WHERE `subvuz_id`=?', $id);
        $db->query('DELETE FROM `vuz`.`subvuz` WHERE `id`=?', $id);
        $db->query('DELETE FROM `vuz`.`openDays` WHERE `subvuz_id`=?', $id);

        echo 'success';
    }
}

