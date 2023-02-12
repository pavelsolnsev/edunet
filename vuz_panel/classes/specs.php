<?php

class specs
{
    static function show($v_id)
    {
        global $db;

        $page      = intval($_POST['page']); // page num
        $rp        = intval($_POST['rp']); // count per page
        $sortname  = &$_POST['sortname']; // поле для сортировки
        $sortorder = &$_POST['sortorder']; // порядок сортировки

        switch ($sortname) {
            case 'name':
                $sortname = '`okso`.`name`';
                break;
            case 'profile':
                $sortname = '`specs`.`prof`';
                break;
            case 'code':
                $sortname = '`okso`.`code`';
                break;
            case 'form':
                $sortname = '`specs`.`form`';
                break;
            case 'subvuz':
                $sortname = '`subvuz`.`name`';
                break;
            default:
                $sortname = '`specs`.`lastEdit`';
                break;
        }
        if ($sortorder != 'asc') {
            $sortorder = 'desc';
        }

        $sort = 'ORDER BY ' . $sortname . ' ' . $sortorder;

        if (!$page) {
            $page = 1;
        }
        if (!$rp) {
            $rp = 15;
        }

        $start = (($page - 1) * $rp);

        switch ($_POST['qtype']) {
            case 'name':
                $qtype = '`okso`.`name`';
                break;
            case 'profile':
                $qtype = '`okso_profiles`.`name`';
                break;
            case 'subvuz':
                $qtype = '`subvuz`.`name`';
                break;
            default:
                $qtype = '`okso`.`code`';
                if ($_POST['query']) {
                    $_POST['query'] = intval(str_replace('.', '', $_POST['query']));
                }
                break;
        }
        $query = addslashes($_POST['query']); // где

        $where = "";
        if ($query) {
            $where = ' AND ' . $qtype . '="' . $query . '" ';
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Content-type: text/x-json");

        $res = $db->query(
            '
			SELECT SQL_CALC_FOUND_ROWS
				`specs`.`id`, `okso`.`code`, `okso`.`name`, 
				`prof`, `specs`.`form`,
				`subvuz`.`name` as sv, `specs`.`lastEdit`
			FROM `specs` 
				LEFT JOIN `okso` ON `specs`.`okso_id` = `okso`.`id`
				LEFT JOIN `vuz`.`subvuz` ON `specs`.`subvuz_id` = `subvuz`.`id`
		 	WHERE `specs`.`vuz_id`=? ' . $where . ' GROUP BY `specs`.`id` ' . $sort . ' LIMIT ?, ?',
            $v_id,
            $start,
            $rp
        );
        $db->query('SELECT FOUND_ROWS() as cnt');
        $c = $db->get_row();

        $rows = [];

        if ($c['cnt']) {
            while ($row = $db->get_row($res)) {
                $type = substr($row['code'], -3, 1);
                if ($type == 4) {
                    $jType = 'магистратура';
                } elseif ($type == 3) {
                    $jType = 'бакалавриат';
                } else {
                    $jType = 'специалитет';
                }

                switch ($row['form']) {
                    case '1':
                        $jForm = 'очная';
                        break;
                    case '2':
                        $jForm = 'очно-заочная';
                        break;
                    case '3':
                        $jForm = 'заочная';
                        break;
                    case '4':
                        $jForm = 'дистанционная';
                        break;
                }

                $rows[] = [
                    'id'   => $row['id'],
                    'cell' => [
                        $row['id'],
                        substr_replace(substr_replace($row['code'], '.', 2, 0), '.', 5, 0),
                        $row['name'],
                        ($row['prof'] ? $row['prof'] : ""),
                        $jType,
                        $jForm,
                        $row['sv'],
                        ((isSpecActual($row['lastEdit'], "panel")) ? ("Да") : ("Нет"))
                    ]
                ];
            }
        }

        $result = [
            'page' => strval($page),
            'total' => intval($c['cnt']),
            'rows' => $c['cnt'] ? $rows : []
        ];

        echo json_encode($result);
    }

    public static function findOksoCode($u_id, $v_id)
    {
        global $db;

        $code = str_replace('.', '', $_POST['code']);
        if (!preg_match('/^\d{6,6}$/', $code)) {
            myErr::hack(
                2,
                '/панель вуза/добавление специальности',
                'Некорректный тип кода ОКСО POST[code]',
                'BadParam1',
                $u_id
            );
            die('bad');
        }

        $db->query('SELECT `id`, `name` FROM `vuz`.`okso` WHERE `code` = ?', $code);
        if (!$db->num_rows()) {
            die('bad');
        }
        $okso = $db->get_row();

        $db->query('SELECT `id`,`name` FROM `vuz`.`subvuz` WHERE `vuz_id` = ?', $v_id);
        $sv = '';
        while ($row = $db->get_row()) {
            $sv .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }

        $sroks = '';
        $db->query('SELECT `id`, `display` FROM `general`.`edu_periods` WHERE `id`!=1 ORDER BY `val`');
        while ($row = $db->get_row()) {
            $sroks .= '<option value="' . $row['id'] . '">' . $row['display'] . '</option>';
        }

        $tpl = new tpl;
        $lvl = substr($code, -3, 1);
        if ($lvl == 4) {
            $tpl->start('tpl/forms/mag.html');
            $tpl->replace([
                '[name]'  => $okso['name'],
                '[sroks]' => $sroks,
                '[sv]'    => $sv,
            ]);
        } elseif ($lvl == 5 || $lvl == 3) {
            $eges = $egesRu = '';
            $db->query('SELECT `id`, `name` FROM `general`.`ege_exams` WHERE (`ege_exams`.`type` IS NULL OR `ege_exams`.`type` = "v")');
            while ($row = $db->get_row()) {
                $eges .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
                if($row['id'] == 1 )
                    $egesRu .= '<option value="' . $row['id'] . '" selected>' . $row['name'] . '</option>';
                else
                    $egesRu .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
            }

            $tpl->start('tpl/forms/first.html');
            $tpl->replace([
                '[name]'   => $okso['name'],
                '[sroks]'  => $sroks,
                '[eges]'   => $eges,
                '[egesRu]' => $egesRu,
                '[sv]'     => $sv,
            ]);
        } else {
            $eges = '';
            $db->query('SELECT `id`, `name` FROM `general`.`ege_exams` WHERE (`ege_exams`.`type` IS NULL OR `ege_exams`.`type` = "v")');
            while ($row = $db->get_row()) {
                $eges .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
            }

            $tpl->start('tpl/forms/first.html');
            $tpl->replace([
                '[name]'  => $okso['name'],
                '[sroks]' => $sroks,
                '[eges]'  => $eges,
                '[sv]'    => $sv,
            ]);
        }

        $tpl->out();
    }

    static function addForm($v_id)
    {
        global $db;

        $db->query('SELECT `id`,`name` FROM `vuz`.`subvuz` WHERE `vuz_id`=?', $v_id);
        if (!$db->num_rows()) {
            readfile('tpl/forms/noSv.html');
        } else {
            readfile('tpl/forms/specAdd.html');
        }
    }

    static function add(int $v_id)
    {
        global $db;

        $code = str_replace('.', '', $_POST['code']);
        if (!preg_match('/^\d{6}$/', $code)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $db->query('SELECT `id` FROM `vuz`.`okso` WHERE `code`=?', $code);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $okso = $db->get_row();

        $prof = htmlspecialchars($_POST['prof']);
        $prof = str_replace(["\n", "\r"], " ", $prof);
        $prof = preg_replace('/\s{2,}/', ' ', $prof);
        $prof = trim($prof);

        $commercial = 0;

        $form = (int)$_POST['form'];
        if ($form < 1 || $form > 4) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $srok = (int)$_POST['srok'];
        $db->query('SELECT 1 FROM `general`.`edu_periods` WHERE `id` = ?', $srok);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $sv = (int)$_POST['sv'];
        if ($sv) {
            $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `vuz_id` = ? AND `id` = ?', $v_id, $sv);
            if (!$db->num_rows()) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }
        } else {
            die('Пожалуйста укажите учебное подразделение (институт или факультет) реализующее данную образовательную программу');
        }

        $lvl = substr($code, -3, 1);
        $f   = (($_POST['f'] === 'y') ? (1) : (0));
        $s   = (($_POST['s'] === 'y') ? (1) : (0));

        $f_text = $s_text = '';
        $f_cost = $s_cost = $free = null;
        $fscore = $pscore = null;
        if ($lvl === "4" || $f) {
            $free = (int)$_POST['free'];

            $f_cost = str_replace(' ', '', $_POST['f_cost']);
            $f_cost = (int)$f_cost;

            if (!$free && !$f_cost) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            $f_text = trim($_POST['f_text']);
            if ($f_text) {
                $f_text = mb_substr($f_text, 0, 300, 'UTF-8');
                $f_text = htmlspecialchars($f_text);
                $f_text = str_replace(["\n", "\r"], " ", $f_text);
                $f_text = preg_replace('/\s{2,}/', ' ', $f_text);
                $f_text = trim($f_text);
            }
        }

        if ($lvl === "4") {
            $m_lang = $_POST['lang'];
            if (!in_array($m_lang, ['r', 'e', 're'])) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            $m_twin = (($_POST['twin'] === 'y') ? ('1') : ('0'));
            $f      = $s = '0';
        } else {
            $m_lang = '';
            $m_twin = '0';

            if (!$f && !$s) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            if ($f) {
                $min_ege = 36; // rus yaz
                $min     = 0;
                $cnt     = sizeof($_POST['ege']);
                $eges    = [];
                for ($i = 0; $i < $cnt; $i++) {
                    $ege = (int)$_POST['ege'][$i];
                    if ($ege) {
                        $db->query('SELECT `min` FROM `general`.`ege_exams` WHERE `id`=?', $ege);
                        if (!$db->num_rows()) {
                            die('Произошла внутренняя ошибка. Попробуйте еще раз');
                        }
                        $t = $db->get_row();

                        $eges[] = [$ege, ($_POST['selectable'][$i] === '1' ? '1' : '0')];
                        if ($_POST['selectable'][$i] === '1') {
                            $min = ($min ? min($min, $t['min']) : $t['min']);
                        } else {
                            $min_ege += $t['min'];
                        }
                    }
                }
                $min_ege += $min;
                if ($_POST['no-score'] !== 'y') {
                    $fscore = (int)$_POST['fscore'];
                    $pscore = (int)$_POST['pscore'];

                    if (($fscore && $min_ege > $fscore) || ($pscore && $min_ege > $pscore)) {
                        die('Минимальная сумма баллов для экзаменов равна ' . $min_ege);
                    }

                    if (!$fscore) {
                        $fscore = ($free ? $min_ege : null);
                    }
                    if (!$pscore) {
                        $pscore = ($f_cost ? $min_ege : null);
                    }
                }
            }

            if ($s) {
                $s_cost = str_replace(' ', '', $_POST['s_cost']);
                $s_cost = (int)$s_cost;

                $s_text = trim($_POST['s_text']);
                if ($s_text) {
                    $s_text = mb_substr($s_text, 0, 300, 'UTF-8');
                    $s_text = htmlspecialchars($s_text);
                    $s_text = str_replace(["\n", "\r"], " ", $s_text);
                    $s_text = preg_replace('/\s{2,}/', ' ', $s_text);
                    $s_text = trim($s_text);
                }
            }
        }

        $internal_exam = isset($_POST['internal_exam']) ? "1" : "0";

        $db->startTrans();
        $db->query(
            '
			INSERT INTO `vuz`.`specs`(
				`vuz_id`, `subvuz_id`, `okso_id`, `form`, `period`, `free`, `f_score`, `p_score`,
				`f`, `s`, `f_cost`, `s_cost`, `internal_exam`, `f_adv`, `s_adv`, `m_lang`, `m_twin`,
				`prof`, `lastEdit`, `commercial`
			) VALUES(
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
			)',
            $v_id,
            $sv,
            $okso['id'],
            $form,
            $srok,
            $free,
            $fscore,
            $pscore,
            (string)$f,
            (string)$s,
            $f_cost,
            $s_cost,
            $internal_exam,
            $f_text,
            $s_text,
            $m_lang,
            $m_twin,
            $prof,
            $commercial
        );
        $id = $db->insert_id();

        if ($f) {
            $cnt = sizeof($eges);
            for ($i = 0; $i < $cnt; $i++) {
                $db->query(
                    'INSERT INTO `vuz`.`spec2exams`(`exam_id`,`spec_id`, `sel`) VALUES(?, ?, ?)',
                    $eges[$i][0],
                    $id,
                    $eges[$i][1]
                );
            }
        }

        $db->commit();
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
			`specs`.`subvuz_id`, `specs`.`okso_id`, `okso`.`name`, `okso`.`code`, `specs`.`f`, `specs`.`s`, `specs`.`prof`,
			`specs`.`form`, `specs`.`period` as srok, `specs`.`free`, `specs`.`f_score`,  `specs`.`p_score`, 
			`specs`.`f_cost`, `specs`.`s_cost`, `specs`.`f_adv`, `specs`.`s_adv`, 
			`specs`.`m_lang`, `specs`.`m_twin` 
		FROM `vuz`.`specs` LEFT JOIN `vuz`.`okso` ON `specs`.`okso_id`=`okso`.`id`
		WHERE `specs`.`id`=?',
            $id
        );

        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $spec = $db->get_row();

        $db->query('SELECT `id`,`name` FROM `vuz`.`subvuz` WHERE `vuz_id`=?', $v_id);
        if (!$db->num_rows()) {
            readfile('tpl/forms/noSv.html');
            die;
        }

        $sv = '';
        while ($row = $db->get_row()) {
            $sv .= '<option value="' . $row['id'] . '"' . (($row['id'] == $spec['subvuz_id']) ? (' selected="selected"') : ('')) . '>' . $row['name'] . '</option>';
        }

        $name = $spec['name'] . ' – ';
        switch ($lvl = substr($spec['code'], -3, 1)) {
            case '3':
                $name .= 'Бакалавриат';
                break;
            case '4':
                $name .= 'Магистратура';
                break;
            case '5':
                $name .= 'Специалитет';
                break;
        }

        switch ($spec['form']) {
            case '1':
                $formO  = ' selected="selected"';
                $formOZ = $formZ = $formD = '';
                break;
            case '2':
                $formOZ = ' selected="selected"';
                $formO  = $formZ = $formD = '';
                break;
            case '3':
                $formZ = ' selected="selected"';
                $formO = $formOZ = $formD = '';
                break;
            case '4':
                $formD = ' selected="selected"';
                $formO = $formOZ = $formZ = '';
                break;
        }

        $sroks = '';
        $db->query('SELECT `id`,`display` FROM `general`.`edu_periods`');
        while ($row = $db->get_row()) {
            $sroks .= '<option value="' . $row['id'] . '"' . (($spec['srok'] == $row['id']) ? (' selected="selected"') : ('')) . '>' . $row['display'] . '</option>';
        }

        $tpl = new tpl;
        if ($lvl === "4") {
            $free   = $spec['free'];
            $f_cost = $spec['f_cost'];

            switch ($spec['m_lang']) {
                case 'r':
                    $langR = ' selected="selected"';
                    $langE = $langRE = '';
                    break;
                case 'e':
                    $langE = ' selected="selected"';
                    $langR = $langRE = '';
                    break;
                case 're':
                    $langRE = ' selected="selected"';
                    $langR  = $langE = '';
                    break;
                default:
                    $langR = $langE = $langRE = '';
                    break;
            }

            $m_twin = (($spec['m_twin'] === '1') ? (' checked="checked"') : (''));

            $tpl->start('tpl/forms/magEdit.html');
            $tpl->replace([
                '[id]'    => $id,
                '[code]'  => $spec['code'],
                '[lvl]'   => $lvl,
                '[name]'  => $name,
                '[prof]'  => $spec['prof'],
                '[sroks]' => $sroks,

                '[formO]'  => $formO,
                '[formOZ]' => $formOZ,
                '[formZ]'  => $formZ,
                '[formD]'  => $formD,

                '[sv]' => $sv,

                '[free]'   => ($free ? $free : ''),
                '[f_cost]' => ($f_cost ? $f_cost : ''),
                '[f_text]' => $spec['f_adv'],

                '[langR]'  => $langR,
                '[langE]'  => $langE,
                '[langRE]' => $langRE,

                '[twin]' => $m_twin,
            ]);
        } else {
            $ege_list = '';
            $db->query('SELECT `id`, `name` FROM `general`.`ege_exams` WHERE (`ege_exams`.`type` IS NULL OR `ege_exams`.`type` = "v")');
            while ($row = $db->get_row()) {
                $ege_list .= '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
            }

            $f = $s = $f_sokr = $s_sokr = $f_text = $s_text = $f_cost = $s_cost = $free = $eges = $score = $noScore = $internal_exam = '';
            if ($spec['f'] === '1') {
                $f = ' checked="checked"';

                $free   = $spec['free'];
                $f_cost = $spec['f_cost'];
                $fscore = $spec['f_score'];
                $pscore = $spec['p_score'];

                $r = $db->query(
                    'SELECT `exam_id`, `sel` FROM `vuz`.`spec2exams` WHERE `spec_id` = ?',
                    $id
                );

                if ($db->num_rows()) {
                    $eges = '';
                    while ($row = $db->get_row($r)) {
                        $eges .= '<div class="exam">
                                    <input type="hidden" name="selectable[]" value="' . ($row['sel'] ? '1' : '0') . '">
						            <input type="checkbox" class="select_chb" ' . ($row['sel'] ? ' checked="checked"' : '') . ' onchange="return(specs.set_selectable(this));" />
                                    <select name="ege[]"><option value="0">Выберите экзамен</option>';
                        $db->query('SELECT `id`, `name` FROM `general`.`ege_exams` WHERE (`ege_exams`.`type` IS NULL OR `ege_exams`.`type` = "v")');
                        while ($ege = $db->get_row()) {
                            $eges .= '<option value="' . $ege['id'] . '"' . (($ege['id'] == $row['exam_id']) ? (' selected="selected"') : ('')) . '>' . $ege['name'] . '</option>';
                        }
                        $eges .= '</select> <a href="#" onclick="return(specs.delEge(this));" title="Удалить экзамен"><div class="delIcon"></div></a></div>';
                    }

                    $r             = $db->query('SELECT `internal_exam` FROM `vuz`.`specs` WHERE `id` = ?', $id);
                    $exam          = $db->get_row($r);
                    $internal_exam = $exam['internal_exam'] ? 'checked' : '';
                }
            }

            if ($spec['s'] === '1') {
                $s      = ' checked="checked"';
                $s_cost = $spec['s_cost'];
            }

            $tpl->start('tpl/forms/firstEdit.html');
            $tpl->replace([
                '[id]'    => $id,
                '[code]'  => $spec['code'],
                '[lvl]'   => $lvl,
                '[name]'  => $name,
                '[prof]'  => $spec['prof'],
                '[sroks]' => $sroks,

                '[formO]'  => $formO,
                '[formOZ]' => $formOZ,
                '[formZ]'  => $formZ,
                '[formD]'  => $formD,

                '[sv]' => $sv,

                '[internal_exam]' => $internal_exam,

                '[f]' => $f,
                '[s]' => $s,

                '[free]'   => (($free) ? ($free) : ('')),
                '[f_cost]' => (($f_cost) ? ($f_cost) : ('')),
                '[s_cost]' => (($s_cost) ? ($s_cost) : ('')),

                '[egeList]' => $ege_list,
                '[eges]'    => $eges,
                '[fscore]'  => $fscore,
                '[pscore]'  => $pscore,
                '[noScore]' => $noScore,

                '[f_text]' => $spec['f_adv'],
                '[s_text]' => $spec['s_adv'],
            ]);
        }

        $tpl->out();
    }

    static function edit(int $v_id)
    {
        global $db;

        $id =& $_POST['id'];

        $form = (int)$_POST['form'];
        if ($form < 1 || $form > 4) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $srok = (int)$_POST['srok'];
        $db->query('SELECT 1 FROM `general`.`edu_periods` WHERE `id`=?', $srok);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $prof = htmlspecialchars($_POST['prof']);
        $prof = str_replace(["\n", "\r"], " ", $prof);
        $prof = preg_replace('/\s{2,}/', ' ', $prof);
        $prof = trim($prof);

        $sv = (int)$_POST['sv'];
        if ($sv) {
            $db->query('SELECT 1 FROM `vuz`.`subvuz` WHERE `vuz_id`=? AND `id`=?', $v_id, $sv);
            if (!$db->num_rows()) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }
        } else {
            die('Пожалуйста укажите учебное подразделение (институт или факультет) реализующее данную образовательную программу');
        }

        $f = ($_POST['f'] === 'y' ? 1 : 0);
        $s = ($_POST['s'] === 'y' ? 1 : 0);

        if (!preg_match('/^\d+$/', $id)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query(
            'SELECT `vuz`.`okso`.`id`, `vuz`.`okso`.`code` FROM `vuz`.`okso` LEFT JOIN `vuz`.`specs` ON `vuz`.`specs`.`okso_id`=`okso`.`id` WHERE `specs`.`id`=?',
            $id
        );
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }
        $okso = $db->get_row();
        $lvl  = substr($okso['code'], -3, 1);

        $f_text = $s_text = '';
        $f_cost = $s_cost = $free = null;
        $fscore = $pscore = null;
        if ($lvl === "4" || $f) {
            $free = (int)$_POST['free'];

            $f_cost = str_replace(' ', '', $_POST['f_cost']);
            $f_cost = (int)$f_cost;

            if (!$free && !$f_cost) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            $f_text = trim($_POST['f_text']);
            if ($f_text) {
                $f_text = mb_substr($f_text, 0, 300, 'UTF-8');
                $f_text = htmlspecialchars($f_text);
                $f_text = str_replace(["\n", "\r"], " ", $f_text);
                $f_text = preg_replace('/\s{2,}/', ' ', $f_text);
                $f_text = trim($f_text);
            }
        }

        if ($lvl === "4") {
            $m_lang = $_POST['lang'];
            if (!in_array($m_lang, ['r', 'e', 're'])) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            $m_twin = (($_POST['twin'] === 'y') ? ('1') : ('0'));
            $f      = $s = '0';
        } else {
            $m_lang = '';
            $m_twin = '0';

            $db->query('DELETE FROM `vuz`.`spec2exams` WHERE `spec_id` = ?', $id);

            if (!$f && !$s) {
                die('Произошла внутренняя ошибка. Попробуйте еще раз');
            }

            if ($f) {
                $min_ege = 36; // rus yaz
                $min     = 0;
                $cnt     = sizeof($_POST['ege']);
                $eges    = [];
                for ($i = 0; $i < $cnt; $i++) {
                    $ege = (int)$_POST['ege'][$i];
                    if ($ege) {
                        $db->query('SELECT `min` FROM `general`.`ege_exams` WHERE `id`=?', $ege);
                        if (!$db->num_rows()) {
                            die('Произошла внутренняя ошибка. Попробуйте еще раз');
                        }
                        $t = $db->get_row();

                        $eges[] = [$ege, ($_POST['selectable'][$i] === '1' ? '1' : '0')];
                        if ($_POST['selectable'][$i] === '1') {
                            $min = ($min ? min($min, $t['min']) : $t['min']);
                        } else {
                            $min_ege += $t['min'];
                        }
                    }
                }
                $min_ege += $min;

                $fscore = (int)$_POST['fscore'];
                $pscore = (int)$_POST['pscore'];
                if (!$fscore) {
                    $fscore = ($free ? $min_ege : null);
                }
                if (!$pscore) {
                    $pscore = ($f_cost ? $min_ege : null);
                }

                if (($fscore && $min_ege > $fscore) || ($pscore && $min_ege > $pscore)) {
                    die('Минимальная сумма баллов для экзаменов равна ' . $min_ege);
                }

                $cnt = sizeof($eges);
                for ($i = 0; $i < $cnt; $i++) {
                    $db->query(
                        'INSERT INTO `vuz`.`spec2exams`(`exam_id`,`spec_id`, `sel`) VALUES(?, ?, ?)',
                        $eges[$i][0],
                        $id,
                        $eges[$i][1]
                    );
                }
            }

            if ($s) {
                $s_cost = str_replace(' ', '', $_POST['s_cost']);
                $s_cost = (int)$s_cost;

                $s_text = trim($_POST['s_text']);
                if ($s_text) {
                    $s_text = mb_substr($s_text, 0, 300, 'UTF-8');
                    $s_text = htmlspecialchars($s_text);
                    $s_text = str_replace(["\n", "\r"], " ", $s_text);
                    $s_text = preg_replace('/\s{2,}/', ' ', $s_text);
                    $s_text = trim($s_text);
                }
            }
        }

        $internal_exam = isset($_POST['internal_exam']) ? "1" : "0";

        $db->query(
            'UPDATE `vuz`.`specs` SET 
				`subvuz_id`=?, `form`=?, `period`=?, `free`=?, `f_score`=?, `p_score`=?, 
				`f`=?, `s`=?, `f_cost`=?, `s_cost`=?, `internal_exam`=?, `f_adv`=?, `s_adv`=?,
				`m_lang`=?, `m_twin`=?, `prof`=?, `lastEdit`=NOW()
			WHERE `id` = ?',
            $sv,
            $form,
            $srok,
            $free,
            $fscore,
            $pscore,
            (string)$f,
            (string)$s,
            $f_cost,
            $s_cost,
            $internal_exam,
            $f_text,
            $s_text,
            $m_lang,
            $m_twin,
            $prof,
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

        $db->query('SELECT 1 FROM `vuz`.`specs` WHERE `id` = ? AND `vuz_id` = ?', $id, $v_id);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('DELETE FROM `vuz`.`specs` WHERE `id` = ?', $id);

        echo 'success';
    }

    static function dubl(int $v_id)
    {
        global $db;
        $id = intval($_POST['id']);

        if (!preg_match('/^\d+$/', $id)) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $db->query('SELECT 1 FROM `vuz`.`specs` WHERE `id` = ? AND `vuz_id` = ?', $id, $v_id);
        if (!$db->num_rows()) {
            die('Произошла внутренняя ошибка. Попробуйте еще раз');
        }

        $t        = '`vuz_id`, `subvuz_id`, `okso_id`, `prof`, `form`, `period`, `free`, `f_score`, `p_score`, `internal_exam`, `f`, `s`, `f_cost`, `s_cost`, `f_adv`, `s_adv`, `m_lang`, `m_twin`, `commercial`';
        $lastEdit = ', `' . date("Y-m-d") . '`';
        $db->query('INSERT INTO `vuz`.`specs` (' . $t . ') SELECT ' . $t . ' FROM `vuz`.`specs` WHERE `id` = ?', $id);

        echo 'success';
    }
}

