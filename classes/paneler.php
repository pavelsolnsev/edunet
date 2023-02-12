<?php

class paneler
{
    static function form($u_id)
    {
        global $tpl;
        if ($u_id) {
            $tpl->start('tpl/paneler.html');
            $tpl->replace([
                '[head]'            => get_head(
                    'Мастер подключения к панели управления вузом',
                    'Форма регистрации для представителей вузов на проекте EduNetwork',
                    'панель управление вуз подключения мастер'
                ),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),
                '[quiz]'            => file_get_contents('tpl/quiz.html'),

                '[footer]' => file_get_contents('tpl/footer.html')
            ]);
            $tpl->out();
        } else {
            header('Location: https://secure' . DOMAIN . '/#1');
        }
    }

    /*
    static function rules() {
        global $tpl, $ACC;
        $tpl->start('tpl/panel-rules.html');
        $tpl->replace(array(
            '[acc]'=>$ACC,
            '[footer]'=>file_get_contents('tpl/footer.html')
        ));
        $tpl->out();
    }
    */
    static function request()
    {
        global $db;

        if (!$u_id = user::check_session()) {
            die('Время авторизации истекло, обновите страницу');
        }
        if (date("n") === '6') {
            die('Заявки на подключение в июне не принимаются');
        }

        $db->query('SELECT 1 FROM `auth`.`users` WHERE `id`=? AND `email` IS NOT NULL', $u_id);
        if (!$db->num_rows()) { // VK user
            die('Зарегистрируйте пользователя на проекте. VK авторизация не подходит.');
        }

        $v_id  =& $_POST['unitId'];
        $email = trim($_POST['email']);
        $url   = htmlspecialchars(trim($_POST['site']));

        if (!preg_match('/^\d+$/', $v_id)) {
            myErr::hack('vuz', '/запрос на подключение', 'Некорректный тип поля вуз POST[vuzId]', 'BadParam1');
            error:
            err500(true);
        }
        if (!preg_match('/^[a-z0-9\._\-]+@[a-z0-9\.\-]+\.[a-z]{2,4}$/i', $email)) {
            myErr::hack('vuz', '/запрос на подключение', 'Некорректный тип поля email POST[email]', 'BadParam1');
            die('Некорректный формат email');
        }

        $db->query('SELECT 1 FROM `vuz`.`user2vuz` WHERE `u_id`=?', $u_id);
        if ($db->num_rows()) {
            die('Ваш пользователь уже управляет учебным заведением');
        }

        $db->query('SELECT 1 FROM `vuzes` WHERE `id`=?', $v_id);
        if (!$db->num_rows()) {
            die('Указанный вуз отсутствует в нашей базе. Обратитесь в службу технической поддержки <b>&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#064;&#101;&#100;&#117;&#110;&#101;&#116;&#119;&#111;&#114;&#107;&#046;&#114;&#117;</b>');
        }
        $db->query('SELECT 1 FROM `user2vuz` WHERE `vuz_id`=?', $v_id);
        if ($db->num_rows()) {
            die('Права на управление данным вузом уже переданы другому пользователю. Если вы считаете что права на управления ВУЗом переданы пользователю неправомерно, свяжитесь с нашей технической поддержкой <b>&#115;&#117;&#112;&#112;&#111;&#114;&#116;&#064;&#101;&#100;&#117;&#110;&#101;&#116;&#119;&#111;&#114;&#107;&#046;&#114;&#117;</b>');
        }

        $db->query('SELECT `endBan` FROM `vuzBan` WHERE `vuz_id`=? AND `disconnect`<NOW() AND `endBan`>NOW()', $v_id);
        if ($db->num_rows()) {
            $row = $db->get_row();
            require(base_path . "classes/date.php");
            die(
                'Администрация ограничила подключение к данному вузу за нарушение правил проекта до ' . date::mysql2Rus(
                    $row['endBan']
                ) . '.<br />Для получения подробной информации свяжитесь с нами: support@edunetwork.ru'
            );
        }


        $db->query(
            'INSERT INTO `user_requests`(`u_id`,`v_id`,`email`,`url`,`ip`) VALUES(?, ?, ?, ?, ?)',
            $u_id,
            $v_id,
            $email,
            $url,
            $_SERVER['REMOTE_ADDR']
        );
        die('success');
    }

    static function result()
    {
        global $db, $tpl;
        $ticket =& $_GET['id'];
        $tpl    = new tpl;
        $tpl->start('tpl/paneler-fin.html');

        $title = 'Завершение подключения к панели управления вузом';
        $desc  = '';
        $kw    = "";

        if (!preg_match('/^[a-f0-9]{32,32}$/', $ticket)) {
            $tpl->replace([
                '[head]'            => get_head($title, $desc, $kw),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),

                '[class]'  => 'err',
                '[header]' => 'Некорректный код активации подключения',
                '[err]'    => 'Если вы уверены что перешли по правильной ссылке, но все равно видите это сообщение - обратитесь в службу технической поддержки support@edunetwork.ru',
                '[quiz]'   => file_get_contents('tpl/quiz.html'),
                '[footer]' => file_get_contents('tpl/footer.html')
            ]);
            $tpl->out();
            die;
        }

        $db->query(
            'SELECT `u_id`,`v_id` FROM `vuz`.`user_requests` WHERE `ticket` = ? AND `status` = "approved" LIMIT 1',
            $ticket
        );
        if (!$db->num_rows()) {
            $tpl->replace([
                '[head]'            => get_head($title, $desc, $kw),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),

                '[header]' => 'Некорректный код активации подключения',
                '[class]'  => 'err',
                "[err]"    => 'Данного кода активации подключения не существует либо данный код был уже использован Вами.
				<p>Возможные причины и решения данной проблемы:</p>
				<ul>
					<li>Вы уже активировали код, попробуйте перейти в <a href="//vuz' . DOMAIN . '/vuz_panel/">панель управления вузом</a>.</li>
					<li>Вы перешли по некорректной ссылке из письма, сверьте ссылку из письма с адресом данной страницы.</li>
				 	<li>Произошла ошибка на сервере, сообщите нам об этом.</li>
				</ul>
				<p>Cлужба технической поддержки support@edunetwork.ru</p>',
                '[quiz]'   => file_get_contents('tpl/quiz.html'),
                '[footer]' => file_get_contents('tpl/footer.html')
            ]);
            $tpl->out();
            die;
        }

        $row = $db->get_row();
        $db->query('SELECT 1 FROM `vuz`.`user2vuz` WHERE `u_id` = ?', $row['u_id']);
        if ($db->num_rows()) {
            $tpl->replace([
                '[head]'            => get_head($title, $desc, $kw),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),

                "[header]" => 'Ошибка подключения',
                '[class]'  => 'err',
                "[err]"    => 'Ваш пользователь уже управляет вузом на проекте. Если вы хотите переключить вашего пользователя на управление другим вузом - обратитесь в службу технической поддержки support@edunetwork.ru',
                '[quiz]'   => file_get_contents('tpl/quiz.html'),
                '[footer]' => file_get_contents('tpl/footer.html')
            ]);
            $tpl->out();
            die;
        }


        $db->query('SELECT 1 FROM `vuz`.`user2vuz` WHERE `vuz_id`=? AND `u_id`>1000 LIMIT 1', $row['v_id']);
        if ($db->num_rows()) {
            $tpl->replace([
                '[head]'            => get_head($title, $desc, $kw),
                '[second-gtm-code]' => getSecondGtmCode(),
                '[roof]'            => get_roof(),

                "[header]" => 'Ошибка подключения',
                '[class]'  => 'err',
                "[err]"    => 'Данным вузом уже управляет другой пользователь, если вы считаете что именно вы должны управлять данным вузом обратитесь в службу технической поддержки support@edunetwork.ru',
                '[quiz]'   => file_get_contents('tpl/quiz.html'),
                '[footer]' => file_get_contents('tpl/footer.html')
            ]);
            $tpl->out();
            die;
        }

        $db->query('SELECT 1 FROM `vuz`.`specs` WHERE `vuz_id`=? LIMIT 1', $row['v_id']); // For vandal check
        if ($db->num_rows()) {
            $specs = '1';
        } else {
            $specs = '0';
        }
        $db->query('SELECT 1 FROM `vuzes` WHERE `packetEnd`>DATE(NOW()) AND `id`=?', $row['v_id']);
        if (!$db->num_rows()) {
            $db->query(
                'UPDATE `vuz`.`specs` SET `lastEdit`=CONCAT(YEAR(NOW())-1, "-01-01") WHERE `vuz_id`=?',
                $row['v_id']
            ); // need user update
        }

        $db->query(
            'INSERT INTO `vuz`.`user2vuz`(`u_id`,`vuz_id`, `specs`) VALUES (?, ?, ?)',
            $row['u_id'],
            $row['v_id'],
            $specs
        );
        $db->query('UPDATE `vuz`.`user_requests` SET `status`="done", `ticket`="" WHERE `ticket`=?', $ticket);


        $tpl->replace([
            '[head]'            => get_head($title, $desc, $kw),
            '[second-gtm-code]' => getSecondGtmCode(),
            '[roof]'            => get_roof(),

            '[class]'  => 'good',
            "[header]" => 'Вы успешно подключены к управления вузом',
            "[err]"    => 'Для перехода в панель вуза вы можете воспользоваться меню в верхей части страницы или <a href="/vuz_panel/">прямой ссылкой</a>',
            '[quiz]'   => file_get_contents('tpl/quiz.html'),
            '[footer]' => file_get_contents('tpl/footer.html')
        ]);
        $tpl->out();
        die;
    }
}

