<?php

use Edunetwork\Root\AuthHelper;
use Edunetwork\Common\Domain;

define('base_path', '../../system/');
require_once(base_path . 'config.php');
require_once '../../vendor/autoload.php';

/*
if($_SERVER['REMOTE_ADDR']!='80.251.112.199') {
	header('HTTP/1.1 503 Service Temporarily Unavailable');
	header('Status: 503 Service Temporarily Unavailable');
	header('Retry-After: 3600');
	header('Location: https://static.edunetwork.ru/pages/underConstruction/');
	die;
}*/
global $cDbLogin, $cDbPass, $vuzDbName;

$db = new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);

if (!$userId = user::check_session()) {
    header('Location: https://secure' . DOMAIN . '/#vuz' . DOMAIN . '/vuz_panel/');
    die;
}

$site = new Domain('vuz');

$vuzId     = 0;
$vuzesList = AuthHelper::getEditVuzesList($userId);
if ($vuzesList) {
    $vuzesIds = array_column($vuzesList, 'vuz_id');
}

if ($vuzId = intval($_GET['vuzId'])) {
    setcookie("panelVuzId", $vuzId, time() + 1200);
} elseif (isset($_COOKIE['panelVuzId'])) {
    $vuzId = intval($_COOKIE['panelVuzId']);
} elseif (AuthHelper::isAllVuzezAdmin($userId) && empty($vuzesIds) && empty($vuzId)) {
    $vuzId = 1;
} elseif (!empty($vuzesIds) && empty($vuzId)) {
    $vuzId = $vuzesIds[0];
}

if (AuthHelper::isAllVuzezAdmin($userId) || in_array($vuzId, $vuzesIds)) {
    if ($vuzId) {
        switch ($_POST['act']) {
            case 'vuzGenForm':
                require('classes/vuzAbout.php');
                vuzAbout::generalForm($vuzId);
                break;
            case 'vuzGenEdit':
                require('classes/vuzAbout.php');
                vuzAbout::generalEdit($userId, $vuzId);
                break;
            case 'vuzAdvForm':
                require('classes/vuzAbout.php');
                vuzAbout::advForm($vuzId);
                break;
            case 'vuzAdvEdit':
                require('classes/vuzAbout.php');
                vuzAbout::advEdit($vuzId);
                break;
            case 'vuzLicForm':
                require('classes/vuzAbout.php');
                vuzAbout::licForm($vuzId);
                break;
            case 'vuzLicEdit':
                require('classes/vuzAbout.php');
                vuzAbout::licEdit($vuzId);
                break;
            case 'vuzPriemForm':
                require('classes/vuzAbout.php');
                vuzAbout::priemForm($vuzId);
                break;
            case 'vuzPriemEdit':
                require('classes/vuzAbout.php');
                vuzAbout::priemEdit($userId, $vuzId);
                break;
            case 'svAddForm':
                require('classes/subvuzs.php');
                subvuzs::addForm($vuzId);
                break;
            case 'svAdd':
                require('classes/subvuzs.php');
                subvuzs::add($vuzId);
                break;
            case 'svEditForm':
                require('classes/subvuzs.php');
                subvuzs::editForm($userId, $vuzId);
                break;
            case 'svEdit':
                require('classes/subvuzs.php');
                subvuzs::edit($userId, $vuzId);
                break;
            case 'svDel':
                require('classes/subvuzs.php');
                subvuzs::del($userId, $vuzId);
                break;
            case 'findOksoCode':
                require('classes/specs.php');
                specs::findOksoCode($userId, $vuzId);
                break;
            case 'specAddForm':
                require('classes/specs.php');
                specs::addForm($vuzId);
                break;
            case 'specAdd':
                require('classes/specs.php');
                specs::add($vuzId);
                break;
            case 'specEditForm':
                require('classes/specs.php');
                specs::editForm($vuzId);
                break;
            case 'specEdit':
                require('classes/specs.php');
                specs::edit($vuzId);
                break;
            case 'specDel':
                require('classes/specs.php');
                specs::del($vuzId);
                break;
            case 'specClone':
                require('classes/specs.php');
                specs::dubl($vuzId);
                break;
            case 'openDayAddForm':
                require('classes/openDays.php');
                openDays::addForm($vuzId);
                break;
            case 'openDayAdd':
                require('classes/openDays.php');
                openDays::add($vuzId);
                break;
            case 'openDayEditForm':
                require('classes/openDays.php');
                openDays::editForm($vuzId);
                break;
            case 'openDayEdit':
                require('classes/openDays.php');
                openDays::edit($vuzId);
                break;
            case 'openDayDel':
                require('classes/openDays.php');
                openDays::del($userId, $vuzId);
                break;
            case 'thanks':
                require('classes/thanks.php');
                thanks::show($vuzId);
                break;
            case 'getOkrugs':
                if ($_POST['subj'] == 77 || $_POST['subj'] == 78) {
                    echo placement::get_okrugs(0, $_POST['subj']);
                } else {
                    die('err');
                }
                break;
            case 'main_page':
                readfile('tpl/forms/news.html');
                break;
            case 'getVuzAddr':
                require('classes/vuzAbout.php');
                vuzAbout::getVuzAddr($vuzId);
                break;
            default:
                switch ($_GET['act']) {
                    case 'svShow':
                        require('classes/subvuzs.php');
                        subvuzs::show($vuzId);
                        break;
                    case 'specsShow':
                        require('classes/specs.php');
                        require('../classes/functions.php');
                        specs::show($vuzId);
                        break;
                    case 'openDaysShow':
                        require('classes/openDays.php');
                        openDays::show($vuzId);
                        break;
                    case 'leadsShow':
                        require('classes/leads.php');
                        leads::show($vuzId);
                        break;
                    default:
                        $db->query('SELECT `abrev`, `subj_id`, `city_id` FROM `vuz`.`vuzes` WHERE `id` = ?', $vuzId);
                        $row = $db->get_row();

                        if ($vuzesList && count($vuzesList) > 1 && !AuthHelper::isAllVuzezAdmin($userId)) {
                            $links = '<h3 style="padding: 10px 0">Доступные вузы:</h3> |';
                            foreach ($vuzesList as $vl) {
                                $links .= ' <a href="/vuz_panel/?vuzId=' . $vl['vuz_id'] . '">' . $vl['name'] . '</a> |';
                            }
                        }

                        $tpl = new tpl;
                        $tpl->start('tpl/main.html');

                        $leads_button = '';
                        $leads_script = '';

                        //$show_leads =  AuthHelper::isSuperAdmin($userId) || $userId == 555;
                        $show_leads =  AuthHelper::showLeads($userId, $vuzId, 'vuz') || $userId == 555;

                        if ($show_leads) {
                            $leads_button = '<li><a href="javascript:leads.show()">Контакты</a></li>';
                            $leads_script = '<script type="text/javascript" src="/vuz_panel/tpl/leads.js"></script>';
                            /*
                            $debug_leads = [
                                863,
                                1147
                            ];
                            if (in_array($vuzId , $debug_leads)) {
                                $leads_button = '<li><a href="javascript:leads.show()">Контакты</a></li>';
                                $leads_script = '<script type="text/javascript" src="/vuz_panel/tpl/leads.js"></script>';
                            }
                            */
                        }

                        $tpl->replace([
                            '[LINKS]' => $links,
                            '[myVuz]' => (($row['subj_id'] == 77 || $row['subj_id'] == 78) ? ($row['subj_id']) : ($row['subj_id'] . '/' . $row['city_id'])) . '/v' . $vuzId,
                            '[abrev]' => $row['abrev'],
                            '[leads_button]' => $leads_button,
                            '[leads_script]' => $leads_script,
                        ]);
                        $tpl->out();
                        break;
                }
                break;
        }
        $db->close();
    } else {
        die;
    }
} else {
    setcookie("panelVuzId", $vuzId, time() - 1200);
    header('Location: http://vuz' . DOMAIN . '/');
    die;
}

