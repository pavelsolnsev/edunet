<?php

use Edunetwork\Common\Tools\ImgHelper;
use Edunetwork\Root\AuthHelper;
use Edunetwork\Common\Logger\Mlog;
use Edunetwork\Common\Logger\Monolog\Helpers\MattermostHelper;

class vuzAbout
{
    static function getUserParams()
    {
        //$u_ip   = $_SERVER['REMOTE_HOST'];
        $u_ip = $_SERVER['REMOTE_ADDR'] != null ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
        return [
            'u_id'       => AuthHelper::getActiveUserId(),
            'u_ip'       => $u_ip,
            'time_stamp' => date('Y-m-d H:i:s')
        ];
    }

    static function generalForm(int $vuzId)
    {
        global $db;
        $db->query(
            '
			SELECT 
				`vuzes`.`fullName`, `vuzes`.`name`, `vuzes`.`abrev`, 
				`vuzes`.`subj_id`, `subjects`.`name` AS subject, `cities`.`name` AS city, 
				`cities`.`type`, `cities`.`type`, 
				`vuzes`.`post`, `vuzes`.`address`,
				`vuzes`.`phone`,  `vuzes`.`email`, `vuzes`.`site`, `vuzes`.`telNew`, `vuzes`.`parent_id`
			FROM `vuz`.`vuzes`
				LEFT JOIN `general`.`subjects` ON `vuzes`.`subj_id` = `subjects`.`id`
				LEFT JOIN `general`.`cities` ON `vuzes`.`city_id` = `cities`.`id`
			WHERE `vuzes`.`id` =? ',
            $vuzId
        );
        $row      = $db->get_row();
        $subjCity = $row['subject'];
        if ($row['subj_id'] != 77 && $row['subj_id'] != 78) {
            $subjCity .= ', ' . $row['type'] . ' <span id="locat">' . $row['city'] . '</span>';
        } else {
            $subjCity = '<span id="locat">' . $subjCity . '</span>';
        }
        if ($row['parent_id']) {
            $dis = ' disabled="disabled"';
        } else {
            $dis = '';
        }
        $html = '
			( <input type="text" name="telCode" class="telCode" maxlength="5" rel="natural" value="%s" /> )
		    <input type="text" name="tel1" class="tel1" maxlength="3" rel="natural" value="%s" /> –
	        <input type="text" name="tel2" class="tel2" maxlength="2" rel="natural" value="%s" /> – 
	        <input type="text" name="tel3" class="tel3" maxlength="2" rel="natural" value="%s" /> доб.
	        <input type="text" name="telAdv" class="telAdv" maxlength="20" value="%s" />';
        if ($row['telNew'] == "1") {
            $phone = explode("@", $row['phone']);
            $phone = sprintf(
                $html,
                $phone[0],
                substr($phone[1], 0, (6 - strlen($phone[0]))),
                substr($phone[1], -4, 2),
                substr($phone[1], -2, 2),
                $phone[2]
            );
        } else {
            $phone = sprintf($html, '', '', '', '', '');
        }
        $tpl = new tpl;
        $tpl->start('tpl/forms/vuzGen.html');
        $tpl->replace([
            '[dis]'      => $dis,
            '[fullName]' => $row['fullName'],
            '[name]'     => $row['name'],
            '[abbr]'     => $row['abrev'],
            '[com]'      => (($row['gos'] == "0") ? (' selected="selected"') : ('')),
            '[subjCity]' => $subjCity,
            '[index]'    => $row['post'],
            '[address]'  => $row['address'],
            '[phone]'    => $phone,
            '[site]'     => $row['site'],
            '[email]'    => $row['email'],
        ]);
        $tpl->out();
    }

    static function generalEdit(int $userId, int $vuzId)
    {
        global $db;

        $abbr  = htmlspecialchars(trim($_POST['abbr']));
        $addr  = htmlspecialchars(trim($_POST['address']));
        $index =& $_POST['index'];
        $site  = htmlspecialchars(trim($_POST['site']));
        $email = htmlspecialchars(trim($_POST['email']));

        $db->query(
            '
                SELECT 
                        `vuzes`.`post`, `vuzes`.`address`, `vuzes`.`parent_id`
                FROM 
                        `vuz`.`vuzes`
                WHERE `vuzes`.`id` = ?',
            $vuzId
        );
        $row = $db->get_row();

        if (!preg_match('/^\d{6,6}$/', $index)) {
            myErr::hack(
                'vuz',
                '/панель вуза/изменение общей информации',
                'Некорректный тип почтового индекса POST[index]',
                'BadParam1',
                $userId
            );
            die('Некорректный формат почтового индекса');
        }

        $site = preg_replace('/^http:\/\//i', '', $site);

        if ($_POST['telCode']) {
            if (!preg_match('/^\d{3,5}$/', $_POST['telCode'])) {
                die('Некорректно указан код телефона');
            }
            if (!preg_match('/^\d{1,3}$/', $_POST['tel1']) || !preg_match('/^\d{2,2}$/', $_POST['tel2']) || !preg_match(
                    '/^\d{2,2}$/',
                    $_POST['tel3']
                )) {
                die('Некорректно указан телефон');
            }
            if (strlen($_POST['telCode'] . $_POST['tel1'] . $_POST['tel2'] . $_POST['tel3']) != 10) {
                die('Телефон должен состоять из 10 цифр');
            }
            $phone = $_POST['telCode'] . '@' . $_POST['tel1'] . $_POST['tel2'] . $_POST['tel3'] . '@' . htmlspecialchars(
                    str_replace(["@"], '', $_POST['telAdv'])
                );
        }

        $u_params = self::getUserParams();
        $u_id     = $u_params['u_id'];
        $u_ip     = $u_params['u_ip'];
        $u_ts     = $u_params['time_stamp'];

        if (!$row['parent_id'] && $row['abrev'] != $abbr) {
            $db->query(
                '
                INSERT INTO `vuz`.`vuzRequests`(`vuz_id`, `type`, `oldVal`, `newVal`,`user_id`, `user_ip`, `time`) 
                VALUES(?, "vuzAbbr", ?, ?, ?, ?, ?)',
                $vuzId,
                $row['abrev'],
                $abbr,
                $u_id,
                $u_ip,
                $u_ts
            );
        }
        if ($row['address'] != $addr) {
            $db->query(
                '
                INSERT INTO `vuz`.`vuzRequests`(`vuz_id`, `type`, `oldVal`, `newVal`,`user_id`, `user_ip`, `time`) 
                VALUES(?, "vuzAddr", ?, ?, ?, ?, ?)',
                $vuzId,
                $row['address'],
                $addr,
                $u_id,
                $u_ip,
                $u_ts
            );
        }
        if ($row['parent_id']) {
            $db->query(
                'UPDATE `vuz`.`vuzes` SET `post`=?,`address`=?,`phone`=?,`email`=?,`site`=?,`telNew`="1" WHERE `id`=?',
                $index,
                $addr,
                $phone,
                $email,
                $site,
                $vuzId
            );
        } else {
            $db->query(
                'UPDATE `vuz`.`vuzes` SET `abrev`=?, `post`=?,`address`=?,`phone`=?,`email`=?,`site`=?,`telNew`="1" WHERE `id`=?',
                $abbr,
                $index,
                $addr,
                $phone,
                $email,
                $site,
                $vuzId
            );
        }
        $db->query(
            'INSERT INTO `vuz`.`vuzRequests`
                (`vuz_id`, `type`, `oldVal`, `newVal`,`user_id`, `user_ip`, `time`,`moder_id`, `moder_ip`, `approved`) 
            VALUES (?, "vuzPack", "Пакетное обновление", ?, ?, ?, ?, "0", "SYSTEM", ?)',
            $vuzId,
            json_encode(
                [
                    'vuzAbbr'  => $abbr,
                    'vuzZIP'   => $index,
                    'vuzAddr'  => $addr,
                    'vuzPhone' => $phone,
                    'vuzEmail' => $email,
                    'vuzSite'  => $site
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            ),
            $u_id,
            $u_ip,
            $u_ts,
            $u_ts
        );

        echo 'success';
    }

    static function advForm(int $vuzId)
    {
        global $db, $site;

        $db->query(
            'SELECT `logo`, `about`, DATE(DATE_ADD(`connect`, INTERVAL 1 MONTH)) AS `unblock` 
            FROM `vuz`.`vuzes` LEFT JOIN `vuz`.`user2vuz` ON `vuzes`.`id` = `user2vuz`.`vuz_id` 
            WHERE `vuzes`.`id` = ?',
            $vuzId
        );
        $row = $db->get_row();

        if ($row['logo']) {
            $row['logo'] = 'Текущий <a href="/files/' . $vuzId . '/logo.' . $row['logo'] . '" target="_blank">логотип</a> вашего вуза';
        } else {
            $row['logo'] = '<b>Логотип Вашего вуза отсутствует</b>';
        }

        if ($row['military'] == "1") {
            $milY = ' selected="selected"';
            $milN = '';
        } else {
            $milY = '';
            $milN = ' selected="selected"';
        }

        if ($row['unblock'] >= date('Y-m-d')) {
            $text = '<div id="text-order">Редактирование текста «О Вузе» станет доступно <b>' . date::mysql2Rus(
                    $row['unblock']
                ) . '</b></div>';
        } else {
            $c_sql = 'SELECT 1 FROM vuz.vuzRequests WHERE type = "vuzAbout" AND vuz_id=? AND result <> "1"';
            $db->query($c_sql, $vuzId);
            //echo $c_sql.'<br>';
            if ($db->num_rows()) {
                $text = '
				<div id="text-order">
					Текст о вузе стоит в очереди на подтверждение администрацией проекта к публикации. Если текст будет одобрен, то будет опубликован автоматически. В противном случае Вы получите уведомление с обязательным указанием причины отказа в публикации.
				</div>';
            } else {
                $text = '<textarea name="text">' . strip_tags(str_replace('</p>', "\n", $row['about'])) . '</textarea>';
            }
        }

        $gallery = '';
        $db->query(
            'SELECT `id` FROM `vuz`.`gallery` WHERE `gallery`.`vuz_id` = ?',
            $vuzId
        );
        while ($rr = $db->get_row()) {
            $imgUrl = '/files/gallery/' . $vuzId . '/' . $rr["id"] . '.jpg';
            if (!file_exists($site->getRoot() . $imgUrl)) {
                $imgUrl = '/files/' . $vuzId . '/gallery/' . $rr["id"] . '.jpg';
            }
            $gallery .= '<img src="' . $imgUrl . '" width="200" /><br>';
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/vuzAdv.html');
        $tpl->replace([
            '[text]'    => $text,
            '[logo]'    => $row['logo'],
            '[gallery]' => $gallery
        ]);
        $tpl->out();
    }

    static function advEdit(int $vuzId)
    {
        global $db, $site;

        $u_params = self::getUserParams();

        if ($_POST['textEdit'] === '1') {
            $text = htmlspecialchars(trim($_POST['text']));
            $text = str_replace("\t", ' ', $text);
            $text = preg_replace("/\R{2,}/", "\n", $text);
            $text = str_replace("\n", '</p><p>', $text);
            $text = preg_replace('/\s{2,}/', ' ', $text);
            $text = str_replace('<p> ', '<p>', $text);
            $text = '<p>' . $text . '</p>';

            if (mb_strlen($text, 'UTF-8') < 1000) {
                die('Текст "О вузе" слишком короткий. Минимальный объем 1000 символов.');
            }
            if (($t = mb_strlen($text, 'UTF-8')) > 5000) {
                die('Текст "О вузе" слишком длинный. Максимальная объем 5000 символов. Текущая объем текста: ' . $t);
            }
            $db->query('SELECT `about` FROM `vuz`.`vuzes` WHERE `id` = ?', $vuzId);
            $row    = $db->get_row();
            $tAbout = strip_tags($row['about']);

            if (mb_strlen($tAbout, 'UTF-8') > 200) {
                require(base_path . 'classes/shingles.php');
                $perc = shingles::check($text, $tAbout);
            } else {
                $perc = 0;
            }

            $u_id = $u_params['u_id'];
            $u_ip = $u_params['u_ip'];
            $u_ts = $u_params['time_stamp'];

            if ($perc < 95) {
                $db->query('INSERT `vuz_texts`(`vuz_id`,`text`) VALUES(?, ?)', $vuzId, $text);
                $db->query(
                    'INSERT INTO `vuz`.`vuzRequests`(`vuz_id`, `type`, `oldVal`, `newVal`, `user_id`, `user_ip`,`time`) 
                    VALUES( ?, "vuzAbout", "Ожидает проверки", "", ?, ?, ?)',
                    $vuzId,
                    $u_id,
                    $u_ip,
                    $u_ts
                );
            } else {
                $db->query('UPDATE `vuz`.`vuzes` SET `about` = ? WHERE `id` = ?', $text, $vuzId);
                $db->query(
                    '
                    INSERT INTO `vuz`.`vuzRequests`
                        (`vuz_id`, `type`, `oldVal`, `newVal`, `user_id`, `user_ip`, `time`, `approved`, `moder_id`, `moder_ip`) 
                    VALUES ( ?, "vuzAbout", "Ожидает проверки", "", ?, ?, ?, ?, ?, ?)',
                    $vuzId,
                    $u_id,
                    $u_ip,
                    $u_ts,
                    $u_ts,
                    1,
                    'SYSTEM'
                );
            }
        }

        $file = "logoFile";
        if (isset($_FILES[$file]['tmp_name']) && $_FILES[$file]['tmp_name']) {
            if (filesize($_FILES[$file]['tmp_name']) > 30720) {
                die('Превышен максимальный размер файла (30 кб)');
            }
            @$f = fopen($_FILES[$file]['tmp_name'], "r");
            @$data = fread($f, 8);
            @fclose($f);

            if (!$ext = ImgHelper::isLogoTypeAcceptable($data)) {
                die('Можно загружать только файлы jpg, png');
            }

            $maxWidth  = 198;
            $maxHeight = 198;
            $imgInfo   = getimagesize($_FILES[$file]["tmp_name"]);
            if (!$imgInfo[0] || !$imgInfo[1]) {
                die('Загруженное изображение повреждено или имеет неизвестный формат');
            }
            $db->query('SELECT `logo` FROM `vuz`.`vuzes` WHERE `id` = ?', $vuzId);
            $row = $db->get_row();
            if (!is_dir('../files/' . $vuzId)) {
                mkdir('../files/' . $vuzId);
            }
            if ($imgInfo[0] > $maxWidth || $imgInfo[1] > $maxHeight) {
                if ($imgInfo[0] >= $imgInfo[1]) {
                    $k = $imgInfo[0] / $maxWidth;
                } else {
                    $k = $imgInfo[1] / $maxHeight;
                }
                $width  = round($imgInfo[0] / $k);
                $height = round($imgInfo[1] / $k);
                $src    = imagecreatefromjpeg($_FILES[$file]["tmp_name"]);
                if (!$src) {
                    die('Не удалось обработать изображение');
                }
                if ($row["logo"]) {
                    @unlink("../files/" . $vuzId . "/logo." . $row["logo"]);
                }
                $new = imagecreatetruecolor($width, $height);
                imagecopyresampled($new, $src, 0, 0, 0, 0, $width, $height, $imgInfo[0], $imgInfo[1]);
                imagejpeg($new, "../files/" . $vuzId . "/logo." . $ext, 100);
                imagedestroy($src);
                imagedestroy($new);
            } else {
                if ($row["logo"]) {
                    @unlink("../files/" . $vuzId . "/logo." . $row["logo"]);
                }
                @move_uploaded_file($_FILES[$file]["tmp_name"], "../files/" . $vuzId . "/logo." . $ext);
            }
            $db->query('UPDATE `vuz`.`vuzes` SET `logo` = ? WHERE `id` = ?', $ext, $vuzId);
        }

        $file = "galleryFile";
        if (isset($_FILES[$file]['tmp_name']) && $_FILES[$file]['tmp_name']) {
            try {
                if (filesize($_FILES[$file]['tmp_name']) > 1048576) {
                    die('Превышен максимальный размер файла');
                }
                @$f = fopen($_FILES[$file]['tmp_name'], "r");
                @$data = fread($f, 8);
                @fclose($f);

                if (!$ext = ImgHelper::isLogoTypeAcceptable($data)) {
                    die('Можно загружать только файлы jpg');
                }

                $maxWidth  = 3000;
                $maxHeight = 2000;
                $imgInfo   = getimagesize($_FILES[$file]["tmp_name"]);
                if (!$imgInfo[0] || !$imgInfo[1]) {
                    die('Загруженное изображение повреждено или имеет неизвестный формат');
                }
                if (!is_dir('../files/gallery/' . $vuzId)) {
                    mkdir('../files/gallery/' . $vuzId);
                }

                $db->query('SELECT MAX(id) as max FROM `vuz`.`gallery`');
                $row = $db->get_row();
                $img = $row['max'] + 1;

                Mlog::getInstance($site, 'advEdit', false, true)
                    ->info("_FILES ", [$_FILES[$file], $row, $img]);

                if ($imgInfo[0] > $maxWidth || $imgInfo[1] > $maxHeight) {
                    Mlog::getInstance($site, 'advEdit', false, true)
                        ->info(print_r($row, true), $imgInfo);

                    if ($imgInfo[0] >= $imgInfo[1]) {
                        $k = $imgInfo[0] / $maxWidth;
                    } else {
                        $k = $imgInfo[1] / $maxHeight;
                    }
                    $width  = round($imgInfo[0] / $k);
                    $height = round($imgInfo[1] / $k);
                    $src    = imagecreatefromjpeg($_FILES[$file]["tmp_name"]);
                    if (!$src) {
                        die('Не удалось обработать изображение');
                    }
                    $new = imagecreatetruecolor($width, $height);
                    imagecopyresampled($new, $src, 0, 0, 0, 0, $width, $height, $imgInfo[0], $imgInfo[1]);
                    imagejpeg($new, "../files/gallery/" . $vuzId . "/" . $img . "." . $ext, 100);
                    imagedestroy($src);
                    imagedestroy($new);
                } else {
                    @move_uploaded_file(
                        $_FILES[$file]["tmp_name"],
                        "../files/gallery/" . $vuzId . "/" . $img . "." . $ext
                    );
                }
                $db->query(
                    'INSERT `vuz`.`gallery` (
                        `id`,`vuz_id`,`date`,`enw`
                    ) VALUES (
                        ?, ?, ?, ?
                    )',
                    $img,
                    $vuzId,
                    date('Y-m-d'),
                    1
                );
            } catch (\Exception $e) {
                Mlog::getInstance($site, 'advEdit', false, true)
                    ->info("Exception " . $e->getMessage());
            }
        }
        echo 'success';
    }

    static function licForm(int $vuzId)
    {
        global $db;

        $db->query(
            '
			SELECT 
				`vuzes`.`lic_num`, `vuzes`.`lic_start`,  
				`vuzes`.`acr_num`, `vuzes`.`acr_start`,
				`vuzes`.`parent_id`
			FROM `vuzes` WHERE `id` = ?',
            $vuzId
        );
        $lic = $db->get_row();
        if ($lic['parent_id']) {
            $parent = "active";
            $db->query(
                '
				SELECT 
					`vuzes`.`lic_num`, `vuzes`.`lic_start`, `vuzes`.`acr_num`, `vuzes`.`acr_start`
				FROM `vuzes` WHERE `id` = ?',
                $lic['parent_id']
            );
            $lic = $db->get_row();
        } else {
            $parent = "";
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/vuzLic.html');
        $tpl->replace([
            '[lic_num]'   => $lic['lic_num'],
            '[lic_start]' => $lic['lic_start'],
            '[parent]'    => $parent,
            '[acr_num]'   => $lic['acr_num'],
            '[acr_start]' => $lic['acr_start'],
        ]);
        $tpl->out();
    }

    static function licEdit(int $vuzId)
    {
        global $db;

        $lic_num = trim($_POST['lic_num']);
        if ($lic_num) {
            if (!preg_match('/^\d+$/', $lic_num)) {
                die('Введите корректный регистрационный номер лицензии');
            }
            $lic_start = $_POST['lic_start'];
            if (!preg_match('/^\d{2,2}\.\d{2,2}\.\d{4,4}$/', $lic_start)) {
                die('Введите корректную дату начала срока действия лицензии');
            }
            $db->query(
                'UPDATE `vuzes` SET `lic_num` = ?, `lic_start` = ?, `lic_end` = ? WHERE `id` = ?',
                $lic_num,
                $lic_start,
                $vuzId
            );
        }

        $acr_num = intval($_POST['acr_num']);
        if ($acr_num) {
            $acr_start = $_POST['acr_start'];
            if (!preg_match('/^\d{2,2}\.\d{2,2}\.\d{4,4}$/', $acr_start)) {
                die('Введите корректную дату начала срока действия аккредитации');
            }
        } else {
            $acr_num   = null;
            $acr_start = '';
        }
        $db->query('UPDATE `vuzes` SET `acr_num` = ?, `acr_start` = ? WHERE `id` = ?', $acr_num, $acr_start, $vuzId);
        echo 'success';
    }

    static function priemForm(int $vuzId)
    {
        global $db;

        $db->query(
            '
			SELECT
				`vuzes`.`priem_address`, `vuzes`.`priem_index`, `vuzes`.`priem_phone` AS phone,
				`vuzes`.`priem_site`, `vuzes`.`priem_email`,  `vuzes`.`schedule`, 
				`vuzes`.`priem_start` AS start, `vuzes`.`priem_end` AS end,
				`subjects`.`id` AS subj_id, `subjects`.`name` AS subject, `cities`.`type`, `cities`.`name` AS city
			FROM 
				`vuz`.`vuzes` LEFT JOIN 
				`general`.`subjects` ON `vuzes`.`subj_id` = `subjects`.`id` LEFT JOIN 
				`general`.`cities` ON `vuzes`.`city_id` = `cities`.`id` 
			WHERE `vuzes`.`id` = ?',
            $vuzId
        );
        $row = $db->get_row();

        $subjCity = $row['subject'];
        if ($row['subj_id'] != 77 && $row['subj_id'] != 78) {
            $subjCity .= ', ' . $row['type'] . ' <span id="locat">' . $row['city'] . '</span>';
        } else {
            $subjCity = '<span id="locat">' . $subjCity . '</span>';
        }

        $html = '
			(<input type="text" name="telCode[]" class="telCode" maxlength="5"[req] value="%s" />)
		    <input type="text" name="tel1[]" class="tel1" maxlength="3"[req] value="%s" /> –
	        <input type="text" name="tel2[]" class="tel2" maxlength="2"[req] value="%s" /> – 
	        <input type="text" name="tel3[]" class="tel3" maxlength="2"[req] value="%s" /> доб.
	        <input type="text" name="telAdv[]" class="telAdv" maxlength="20" value="%s" />';

        $phones = explode("\n", $row['phone']);
        $i      = 1;
        foreach ($phones as $phone) {
            $phone = explode("@", $phone);
            if ($i == 1) {
                $fPhone = str_replace(
                    '[req]',
                    ' rel="natural"',
                    sprintf(
                        $html,
                        $phone[0],
                        substr($phone[1], 0, (6 - strlen($phone[0]))),
                        substr($phone[1], -4, 2),
                        substr($phone[1], -2, 2),
                        $phone[2]
                    )
                );
            } elseif ($i == 2) {
                $tels = '<div class="tel">' . str_replace(
                        "[req]",
                        "",
                        sprintf(
                            $html,
                            $phone[0],
                            substr($phone[1], 0, (6 - strlen($phone[0]))),
                            substr($phone[1], -4, 2),
                            substr($phone[1], -2, 2),
                            $phone[2]
                        )
                    ) . '</div>';
            } elseif ($i == 3) {
                $tels = '<div class="tel">' . str_replace(
                        "[req]",
                        "",
                        sprintf(
                            $html,
                            $phone[0],
                            substr($phone[1], 0, (6 - strlen($phone[0]))),
                            substr($phone[1], -4, 2),
                            substr($phone[1], -2, 2),
                            $phone[2]
                        )
                    ) . '</div>';
            }
            $i++;
        }

        if ($row['schedule']) {
            $sch = explode("|", $row['schedule']);
            $c   = sizeof($sch);
            $out = '';
            for ($i = 0; $i < $c; $i++) {
                $out .= '<tr>';
                $t   = substr($sch[$i], 0, 7);
                for ($j = 0; $j < 7; $j++) {
                    if (substr($t, $j, 1) == '1') {
                        $out .= '<td><input type="checkbox" class="cb" checked="checked" /></td>';
                    } else {
                        $out .= '<td><input type="checkbox" class="cb" /></td>';
                    }
                }

                $out .= '
					<td class="time">
						<select name="hours[]">';
                $t   = intval(substr($sch[$i], 7, 2));
                for ($j = 7; $j < 23; $j++) {
                    $out .= '<option value="' . $j . '" ' . (($j == $t) ? (' selected="selected"') : ('')) . '>' . $j . '</option>';
                }

                $out .= '</select> : <select name="mins[]">';
                $t   = intval(substr($sch[$i], 9, 2));
                for ($j = 0; $j < 60; $j += 15) {
                    $out .= '<option value="' . $j . '" ' . (($j == $t) ? (' selected="selected"') : ('')) . '>' . sprintf(
                            '%1$02d',
                            $j
                        ) . '</option>';
                }

                $out .= '</select> – <select name="hours[]">';
                $t   = intval(substr($sch[$i], 11, 2));
                for ($j = 7; $j < 23; $j++) {
                    $out .= '<option value="' . $j . '" ' . (($j == $t) ? (' selected="selected"') : ('')) . '>' . $j . '</option>';
                }

                $out .= '</select> : <select name="mins[]">';
                $t   = intval(substr($sch[$i], 13, 2));
                for ($j = 0; $j < 60; $j += 15) {
                    $out .= '<option value="' . $j . '" ' . (($j == $t) ? (' selected="selected"') : ('')) . '>' . sprintf(
                            '%1$02d',
                            $j
                        ) . '</option>';
                }

                $out .= '</select>
					</td>
					<td>
						<input type="text" name="adv[]" class="adv" maxlength="10" value="' . substr($sch[$i], 15) . '">
						<input type="hidden" class="pdays" name="days[]" value="" />
					</td>
				</tr>';
            }
        } else {
            $out = '<tr>';
            for ($j = 0; $j < 7; $j++) {
                $out .= '<td><input type="checkbox" class="cb" /></td>';
            }
            $hours = '';
            for ($j = 7; $j < 23; $j++) {
                $hours .= '<option value="' . $j . '">' . $j . '</option>';
            }
            $mins = '';
            for ($j = 0; $j < 60; $j += 15) {
                $mins .= '<option value="' . $j . '">' . $j . '</option>';
            }

            $out .= '
				<td class="time">
					<select name="hours[]">' . $hours . '</select> : <select name="mins[]">' . $mins . '</select> 
					&ndash; 
					<select name="hours[]">' . $hours . '</select> : <select name="mins[]">' . $mins . '</select>
				</td>
				<td>
					<input type="text" maxlength="10" class="adv" name="adv[]">
					<input type="hidden" name="days[]" class="pdays">
				</td>
			</tr>';
        }

        if ($row['start']) { // definded
            if ($row['start'] == '1') {
                $allyear = ' checked="checked"';
                $start   = $end = '';
            } else {
                $allyear = '';
                $start   = $row['start'];
                $end     = $row['end'];
            }
        } else { // Not defined
            $allyear = $start = $end = '';
        }

        $tpl = new tpl;
        $tpl->start('tpl/forms/vuzPriem.html');
        $tpl->replace([
            '[subjCity]' => $subjCity,
            '[index]'    => $row['priem_index'],
            '[address]'  => $row['priem_address'],
            '[fPhone]'   => $fPhone,
            '[tels]'     => $tels,
            '[site]'     => $row['priem_site'],
            '[email]'    => $row['priem_email'],
            '[allyear]'  => $allyear,
            '[start]'    => $start,
            '[end]'      => $end,
            '[schedule]' => $out,
        ]);
        $tpl->out();
    }

    static function priemEdit(int $userId, int $vuzId)
    {
        global $db;

        $addr  = htmlspecialchars(trim($_POST['addr']));
        $site  = htmlspecialchars(trim($_POST['site']));
        $site  = preg_replace('/^http:\/\//i', '', $site);
        $email = htmlspecialchars(trim($_POST['email']));

        $index =& $_POST['index'];
        if (!preg_match('/^\d{6,6}$/', $index)) {
            myErr::hack(
                'vuz',
                '/панель вуза/приемная комиссия',
                'Некорректный тип индекса POST[index]',
                'BadParam1',
                $userId
            );
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $phone = '';
        for ($i = 0; $i < 3; $i++) {
            if ($_POST['telCode'][$i]) {
                if (!preg_match('/^\d{3,5}$/', $_POST['telCode'][$i])) {
                    die('Некорректно указан код телефона №' . ($i + 1));
                }
                if (!preg_match('/^\d{1,3}$/', $_POST['tel1'][$i]) || !preg_match(
                        '/^\d{2,2}$/',
                        $_POST['tel2'][$i]
                    ) || !preg_match('/^\d{2,2}$/', $_POST['tel3'][$i])) {
                    die('Некорректно указан телефон №' . ($i + 1));
                }
                if (strlen(
                        $_POST['telCode'][$i] . $_POST['tel1'][$i] . $_POST['tel2'][$i] . $_POST['tel3'][$i]
                    ) != 10) {
                    die('Телефон №' . ($i + 1) . ' должен состоять из 10 цифр');
                }
                $phone .= $_POST['telCode'][$i] . '@' . $_POST['tel1'][$i] . $_POST['tel2'][$i] . $_POST['tel3'][$i] . '@' . htmlspecialchars(
                        str_replace(["@", "\n"], '', $_POST['telAdv'][$i])
                    ) . "\n";
            }
        }
        if ($phone) {
            $phone = substr($phone, 0, -1);
        }

        if ($_POST['allyear'] == 'y') {
            $start = '1';
            $end   = '';
        } else {
            $start = $_POST['start'];
            $end   = $_POST['end'];
            if (!preg_match('/^\d{2,2}\.\d{2,2}\.\d{4,4}$/', $start) || !preg_match(
                    '/^\d{2,2}\.\d{2,2}\.\d{4,4}$/',
                    $end
                )) {
                myErr::hack(
                    'vuz',
                    '/панель вуза/приемная комиссия',
                    'Некорректный тип индекса POST[index]',
                    'BadParam1',
                    $userId
                );
                die('Некорректная дата периода работы приемной комиссии');
            }
        }

        if ($c = sizeof($_POST['days'])) {
            $hash = '';
            for ($i = 0; $i < $c; $i++) {
                $week = $_POST['days'][$i];
                if ($week != '0000000') {
                    if (!preg_match('/^[01]{7,7}$/', $week)) {
                        myErr::hack(
                            'vuz',
                            '/панель вуза/приемная комиссия',
                            'Некорректный тип расписания POST[days]',
                            'BadParam1',
                            $userId
                        );
                        die('Произошла внутренняя ошибка. Попробуйте еще раз');
                    }
                    $hash .= $week;

                    $hour1 = $_POST['hours'][$i * 2];
                    if (!preg_match('/^\d{1,2}$/', $hour1)) {
                        myErr::hack(
                            'vuz',
                            '/панель вуза/приемная комиссия',
                            'Некорректный тип расписания час POST[hours]',
                            'BadParam1',
                            $userId
                        );
                        die('Произошла внутренняя ошибка. Попробуйте еще раз');
                    }
                    $hour1 = sprintf('%1$02d', $hour1);

                    $hour2 = $_POST['hours'][$i * 2 + 1];
                    if (!preg_match('/^\d{1,2}$/', $hour2)) {
                        myErr::hack(
                            'vuz',
                            '/панель вуза/приемная комиссия',
                            'Некорректный тип расписания час POST[hours]',
                            'BadParam1',
                            $userId
                        );
                        die('Произошла внутренняя ошибка. Попробуйте еще раз');
                    }
                    $hour2 = sprintf('%1$02d', $hour2);

                    $min1 = $_POST['mins'][$i * 2];
                    if (!preg_match('/^\d{1,2}$/', $min1)) {
                        myErr::hack(
                            'vuz',
                            '/панель вуза/приемная комиссия',
                            'Некорректный тип расписания час POST[mins]',
                            'BadParam1',
                            $userId
                        );
                        die('Произошла внутренняя ошибка. Попробуйте еще раз');
                    }
                    $min1 = sprintf('%1$02d', $min1);

                    $min2 = $_POST['mins'][$i * 2 + 1];
                    if (!preg_match('/^\d{1,2}$/', $min2)) {
                        myErr::hack(
                            'vuz',
                            '/панель вуза/приемная комиссия',
                            'Некорректный тип расписания час POST[mins]',
                            'BadParam1',
                            $userId
                        );
                        die('Произошла внутренняя ошибка. Попробуйте еще раз');
                    }
                    $min2 = sprintf('%1$02d', $min2);

                    $hour1 = $hour1 . $min1;
                    $hour2 = $hour2 . $min2;
                    if ($hour1 >= $hour2) {
                        die('Некорректно указано время в строке ' . ($i + 1) . '. Начало позже и равно концу');
                    }
                    $adv  = htmlspecialchars(mb_substr(trim($_POST['adv'][$i]), 0, 10, 'UTF-8'));
                    $hash .= $hour1 . $hour2 . $adv . '|';
                }
            }
            $hash = mb_substr($hash, 0, -1, 'UTF-8');
        }

        $u_params = self::getUserParams();

        $u_id = $u_params['u_id'];
        $u_ip = $u_params['u_ip'];
        $u_ts = $u_params['time_stamp'];


        $db->query(
            '
                INSERT INTO `vuz`.`vuzRequests`(
                            `vuz_id`, `type`, `oldVal`, `newVal`, `user_id`, `user_ip`
			) 
                VALUES(?, "priemAddr", (SELECT `priem_address` FROM `vuzes` WHERE `id` = ?), ?, ?, ?)',
            $vuzId,
            $vuzId,
            $addr,
            $u_id,
            $u_ip
        );

        $db->query(
            '
			UPDATE 
				`vuz`.`vuzes` 
			SET 
				`priem_address`=?, `priem_phone`=?, `priem_site`=?, `priem_email`=?,  `priem_index`=?,  
				`schedule`=?, `priem_start`=?, `priem_end`=?
			WHERE `id`=?',
            $addr,
            $phone,
            $site,
            $email,
            $index,
            $hash,
            $start,
            $end,
            $vuzId
        );

        echo 'success';
    }

    static function getVuzAddr($vuzId)
    {
        global $db;

        $db->query('SELECT `address` FROM `vuz`.`vuzes` WHERE `id` = ?', $vuzId);
        $row = $db->get_row();

        echo $row['post'] . "|" . $row['address'];
    }
}

