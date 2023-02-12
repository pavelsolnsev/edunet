<?php
use Edunetwork\Common\Domain;
use Edunetwork\Root\AuthHelper;
//error_reporting(E_ALL);

define("base_path", "../system/");
define("INCLUDE_FLAG", true);

require_once(base_path."config.php");
require_once '../vendor/autoload.php';

$host = new Domain('vuz');

$utm = new UTM_class();
$utm->parseUTM('vuz');

$tpl = new tpl;

require("classes/functions.php");
require("classes/ads.php");

global $cDbLogin, $cDbPass, $vuzDbName;
$db = new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);

if ($userId = user::check_session()) {
    $ACC = ((user::service2group($userId, "vuz") || AuthHelper::isAllVuzezAdmin($userId)) ? ('acc-unit') : ('acc-user'));
} else {
    $ACC = 'acc-guest';
}

switch ($_GET['act']) {
    case 'pages':
        require("classes/pages.php");
        switch ($_GET['id']) {
            case 'dods':
                pages::dods();
                break;
            case 'map':
                pages::map();
                break;
            case 'cities':
                pages::cities();
                break;
            case 'checkVuz':
                pages::checkVuz($userId);
                break;
            case 'favor':
                pages::favorPage($userId);
                break;
            default:
                myErr::err404();
                break;
        }
        break;
    case 'api':
        header('Access-Control-Allow-Origin: *');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
        header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" );
        header("Cache-Control: no-cache, must-revalidate" );
        header("Pragma: no-cache" );
        header("Content-type: text/x-json");

        $zero_output = [
            'message' => 'wrong request',
        ];

        $has_get = false;
        if (isset($_GET['type']) && (isset($_GET['list_type']) || isset($_GET['entt_type'])  || (isset($_GET['table']) ))) {
            $type = $_GET['type'];
            $list_type = $_GET['list_type'];
            $entt_type = $_GET['entt_type'];
            $entt_type = $_GET['entt_type'];
            $tabl_type = $_GET['table'] != '' ? $_GET['table'] : '';
            $token = '';
            $limit = (isset($_GET['limit']) && $_GET['limit'] > 0) ? (int)$_GET['limit'] : 0;
            $offset = (isset($_GET['offset']) && $_GET['offset'] > 0) ? (int)$_GET['offset'] : 0;
            $e_id = (isset($_GET['id']) && $_GET['id'] > 0) ? (int)$_GET['id'] : 0;
            $e_name = (isset($_GET['e_name']) && $_GET['e_name'] != '') ? $_GET['e_name'] : null;
            $a_name = (isset($_GET['a_name']) && $_GET['a_name'] != '') ? $_GET['a_name'] : null;
            $vuz_id = (isset($_GET['vuz_id']) && $_GET['vuz_id'] != '') ? $_GET['vuz_id'] : null;
            //$keys      = (isset($_GET['keys']) && $_GET['keys'] != '') ? $_GET['keys'] : '';
            $keys = (isset($_GET['keysTxt']) && $_GET['keysTxt'] != '') ? $_GET['keysTxt'] : '';
            $dir_id = (isset($_GET['dir_id']) && $_GET['dir_id'] != '') ? $_GET['dir_id'] : -1;
            $subj_id = (isset($_GET['subj_id']) && $_GET['subj_id'] != '') ? $_GET['subj_id'] : -1;
            $has_get = true;
        }

        $has_post = false;
        if (isset($_POST['type']) && (isset($_POST['list_type']) || isset($_POST['entt_type'])  || (isset($_POST['table']) ))) {
            $type = $_POST['type'];
            $list_type = $_POST['list_type'];
            $entt_type = $_POST['entt_type'];
            $entt_type = $_POST['entt_type'];
            $tabl_type = $_POST['table'] != '' ? $_POST['table'] : '';
            $token = $_POST['tkn'] != '' ? $_POST['tkn'] : '';

            $limit = (isset($_POST['limit']) && $_POST['limit'] > 0) ? (int)$_POST['limit'] : 0;
            $offset = (isset($_POST['offset']) && $_POST['offset'] > 0) ? (int)$_POST['offset'] : 0;
            $e_id = (isset($_POST['id']) && $_POST['id'] > 0) ? (int)$_POST['id'] : 0;
            $e_name = (isset($_POST['e_name']) && $_POST['e_name'] != '') ? $_POST['e_name'] : null;
            $a_name = (isset($_POST['a_name']) && $_POST['a_name'] != '') ? $_POST['a_name'] : null;
            $vuz_id = (isset($_POST['vuz_id']) && $_POST['vuz_id'] != '') ? $_POST['vuz_id'] : null;
            //$keys      = (isset($_POST['keys']) && $_POST['keys'] != '') ? $_POST['keys'] : '';
            $keys = (isset($_POST['keysTxt']) && $_POST['keysTxt'] != '') ? $_POST['keysTxt'] : '';
            $dir_id = (isset($_POST['dir_id']) && $_POST['dir_id'] != '') ? $_POST['dir_id'] : -1;
            $subj_id = (isset($_POST['subj_id']) && $_POST['subj_id'] != '') ? $_POST['subj_id'] : -1;
            $start = (isset($_POST['start']) && $_POST['start'] != '') ? $_POST['start'] : null;
            $finish = (isset($_POST['finish']) && $_POST['finish'] != '') ? $_POST['finish'] : null;
            $has_post = true;
        }

        if ($has_get || $has_post) {
            $filter    = [
                'limit'  => $limit,
                'offset' => $offset,
                'id'     => $e_id,
                'vuz_id' => $vuz_id,
                'dir_id' => $dir_id,
                'subj_id'=> $subj_id,
                'start'  => $start,
                'finish' => $finish,
            ];
            $data = [
                'success' => false,
            ];

            if ($e_name != null) {
                $filter['name'] = $e_name;
            }

            if ($a_name != null) {
                $filter['a_name'] = $a_name;
            }
            $school = new School();
            switch ($list_type) {
                case 'vuz' :
                    $data = $school->getVuzList($type, $filter);
                    break;
                /*
                case 'asp' :
                    $filter['id'] = 8;
                    $data = $school->getVuzProfession($type,$filter);
                    break;
                */
                case 'college' :
                    $data = $school->getCollegeList($type, $filter);
                    break;
                case 'college_specs' :
                    $data = $school->getCollegeSpecsList($type, $filter);
                    break;
                case 'vuz_specs' :
                    $data = $school->getVuzSpecsList($type, $filter);
                    break;
                case 'college_specs_rels' :
                    $data = $school->getCollegeSpecsRelationship($type, $filter);
                    break;
                case 'vuz_specs_rels' :
                    $data = $school->getVuzSpecsRelationship($type, $filter);
                    break;
                case 'vuz_study_forms' :
                    $data = $school->getVuzStudyForms('csv');
                    break;
                case 'vuz_dirs' :
                    $data = $school->getVuzByDirection($type,$filter);
                    break;
                case 'vuz_profs' :
                    $data = $school->getVuzProfessionList($type,$filter);
                    break;
                case 'college_profs' :
                    $data = $school->getCollegeProfessionList($type,$filter);
                    break;
                case 'check_keys' :
                    if ($keys != '') {
                        $profs = ['Проверка по ключам'=>[$keys]];
                        //$profs = ['Проверка по ключам'=>['дизайн%аритектур%сред, ландшаф%архитектура, сред%проектирование, архитек%реставрация, дизайн%промыш%сооружений, арх%город%среды']];
                        $data = $school->arrangeProfessionVuzBinding($profs);
                    } else {
                        $json =
                            '{"total": 0,"realm":"vuz.check_keys","location":"vuz.index","rows":[]}';
                    }
                    break;
                case 'arrange_profs' :
                    $profs = [
                        "Архитектор" => ["архитект%проект, дизайн%аритектур%сред"],
                        "Дизайнер" => ["граф%дизайн, дизайн%архитект%сред, промышл%дизайн"],
                        "Логист" => ["операционн%логист, торгов%дело, технолог%транспортн%процессов"],
                        "Медиатор" => ["медиатор, юриспруденция, социальн%психолог"],
                        "Повар" => ["повар, пищевое%производ, биоиндустр%продукт"],
                        "Программист" => ["программист, информатика, информатика%вычислитель%техник, информационн%безопасно"],
                        "Психолог" => ["Психолог"],
                        "Репортер" => ["репортер, журналистика, спортивн%журналист, телерадиожурналист, печатн%сми"],
                        "Следователь" => ["следователь, следственн%комитет, ФСБ, МВД, "],
                        "Юрист" => ["Юрист, судебн%эксперти, правоохранитель%деятельно"],
                        "Аналитик" => ["аналитик, прикладная%математ, прикладная%информати, экономика, бизнес%информатика"],
                        "Врач" => ["врач"],
                        "Дизайнер интерьера" => ["дизайн%интерьер"],
                        "Кинолог" => ["кинолог, зоотехни, биотехнология"],
                        "Кондитер" => ["кондитер, продукты%питания%из%растительного%сырья, технология%продукции%и%организация%общественного%питания"],
                        "Маркетолог" => ["маркетолог, торговое%дело, социология, менеджмент, экономика"],
                        "Переводчик" => ["переводчик, перевод%и%переводоведение, лингвистика"],
                        "Пожарный" => ["пожарный, академия%противопожарной%службы%МЧС%РФ, Институт%противопожарной%службы%МЧС%РФ"],
                        "Учитель" => ["учитель, профессиональн%обучение%по%отраслям, коррекционн%педагогика, педагогика%начальных%классов"],
                        "Эколог" => ["эколог, эколог%и%природопользован, экологическ%менеджмент, геоэкология"],
                        "SMM-специалист" => ["smm-специалист, реклама,  маркетинг, связ%с%общественност"],
                        "Андеррайтер" => ["андеррайтер, страховое%дело, управление%трудовыми%отношениями%соцзащитой%и%страхованием"],
                        "Биолог" => ["биолог, биотехнолог, водные%биоресурсы%и%аквакультура, биоинженер, биоинформатика"],
                        "Бионик" => ["бионик, Биотехнолог"],
                        "Биофизик" => ["биофизик, медицинск%биофизика, биология"],
                        "Инженер-проектировщик" => ["инженер-проектировщик, проектирование, строительство, гражданск%строительство"],
                        "Инженер-электроник" => ["инженер-электроник, применение%и%эксплуатация%автоматизированных%систем%специального%назначения, радиотехник, конструирование%и%технология%электронных%средств, электроника, наноэлектроника"],
                        "Кадастровый инженер" => ["кадастровый%инженер, землеустройств%и%кадастр, картограф%и%геоинформатика"],
                        "Казначей" => ["казначей"],
                        "Корпоративный юрист" => ["корпоративн%юрист, юриспруденция"],
                        "Логопед" => ["логопед, специальн%дефектологическ%образован, логопедия"],
                        "Менеджер по персоналу" => ["менеджер%по%персоналу, управлен%персонал"],
                        "Модератор сайта" => ["модератор%сайта"],
                        "Педагог-психолог" => ["педагог-психолог, Педагогик, психолог"],
                        "Пилот" => ["пилот, испытание%летательн%аппаратов, летная%эксплуатация%и%применение%авиационных%комплексов, эксплуатация%воздушных%судов%и%организация%воздушного%движения"],
                        "Психоаналитик" => ["психоаналитик"],
                        "Тимлид" => ["тимлид, технолог%программирован, разработка%и%администрирован%информационн%систем, прикладн%информационн%систем, математическ%и%информационн%обеспечен%производственн%деятельности"],
                        "Социальный педагог" => ["социальный%педагог, психолог%и%педагогик%девиантно%поведения, педагогическ%образован, психолого%педагогическое%образование"],
                        "Инженер-строитель" => ["инженер%строитель, строительство, техника%и%технологии%строительства"],
                        "Инспектор по делам несовершеннолетних" => ["инспектор%по%делам%несовершеннолетних, социальн%работ, организац%работ%с%молодеж, Педагогик%и%психологи%девиантно%поведения"],
                        "Воспитатель детского сада" => ["воспитатель%детского%сада, педагогическ%образован, психолого%педагогическое%образование"],
                        "Биоинженер" => ["биоинженер, биоинженерия%и%биоинформатика, биотехнолог"],
                        "Инженер-энергетик" => ["инженер%энергетик, электроэнергетик%и%электротехник, теплоэнергетик%и%теплотехник, энергетическо%машиностроени, ядерная%энергетика%и%теплофизика"],
                        "Гидролог" => ["гидролог, гидрометеоролог, прикладн%гидрометеоролог"],
                        "Инженер КИПиА" => ["инженер%кипиа, автоматизац%техническ%процессов%и%производств, приборостроение, управлен%в%техническ%систем"],
                        "Бизнес-тренер" => ["бизнес%тренер, психология%в%бизнесе, организационн%психология, управлен%персонал, психология%профессиональн%деятельност, профессиональн%обучен"],
                        "Олигофренопедагог" => ["олигофренопедагог, психолого%педагогическ%образован, специальн%дефектологическ%образован"],
                        "Web-дизайнер" => ["web%дизайнер, веб%дизайнер, веб, дизайн, информатика и вычислительная техника"],
                        "Налоговый консультант" => ["налоговый%консультант, экономическ%безопасность, налогов%консульт"],
                        "Администратор баз данных" => ["администратор%баз%данных, математическ%обеспечен%и%администрирован%информационн%систем, прикладн%математик%и%информатик, прикладн%информатик, информатик%и%вычислительн техник"],
                    ];
                    $profs = [
                        'Архитектор' => ['дизайн%аритектур%сред, ландшаф%архитектура, сред%проектирование, архитек%реставрация, дизайн%промыш%сооружений, арх%город%среды'],
                        'Дизайнер' => ['граф%дизайн, дизайн%архитект%сред, промышл%дизайн, цифр%дизайн, дизайн%костюма, дизайн%интерьера'],
                        'Логист' => ['операционн%логист, торгов%дело, технолог%транспортн%процессов, трансп%менеджмент, логис%системы, тамож%логистика, трансп%логистика'],
                        'Медиатор' => ['соц%конфликтология, организац%психология, соц%психология, организац%управ%конфликты,управ%конфликтами, конфликт%менеджмент'],
                        'Повар' => ['пищевое%производ, биоиндустр%продукт, повар%кондитер, технология%продукции'],
                    ];
                    foreach ($profs as $prof => $data) {
                        //$buf = $data[0];
                        $buf = str_replace(' ','',$data[0]);
                        $profs[$prof] = explode(',',$buf);
                    }
                    $data = $school->arrangeProfessionVuzBinding($profs);
            }

            //$entt_type = 'vuz_prof';
            //$data = ['txt' => 'fuck U spielberg'];

            switch ($entt_type) {
                case 'vuz_prof' :
                    $data = $school->getVuzProfession($type,$filter);
                break;
                case 'vuz_prof_add' :
                    $fields = [
                        'name',
                        'h1',
                        'slug',
                        'preview_image',
                        'image',
                        'short_text',
                        'full_text',
                        'avg_salary'
                    ];
                    $srtr_tst = mt_rand(1040, 1103);
                    $lead_l   = mb_chr($srtr_tst).'_';
                    $values = [
                        $lead_l."fxtr name",
                        "fxtr h1",
                        time(),
                        "http://mods.edunetwork.loc/banners/fxtr.jpg",
                        "http://mods.edunetwork.loc/banners/fxtr_big.jpg",
                        "fxtr short desc",
                        "fxtr full desc ",
                        100500
                    ];
                    $data = $school->createVuzProfession($fields, $values);
                break;

                case 'vuz_prof_upd' :
                    $fields = [
                        'name',
                        'h1',
                        'slug',
                        'preview_image',
                        'image',
                        'short_text',
                        'full_text',
                        'avg_salary'
                    ];
                    $srtr_tst = mt_rand(1040, 1103);
                    $lead_l   = mb_chr($srtr_tst).'_';
                    $values = [
                        $lead_l."fxtr name",
                        "fxtr h1",
                        date('Y.m.d H:i:s',time()),
                        "http://mods.edunetwork.loc/banners/fxtr.jpg",
                        "http://mods.edunetwork.loc/banners/fxtr_big.jpg",
                        "fxtr short desc ".date('Y.m.d H:i:s',time()),
                        "fxtr full desc ",
                        100500
                    ];
                    $data = $school->updateVuzProfession($fields, $values,$filter);
                    break;

                case 'vuz_prof_import' :
                    $data = $school->importVuzProfession('profs.json','json');
                    //$data = ['success' => 'n/a'];
                break;

                case 'college_prof' :
                    $data = $school->getCollegeProfession($type,$filter);
                break;
                case 'college_prof_add' :
                    $fields = [
                        'name',
                        'h1',
                        'slug',
                        'preview_image',
                        'image',
                        'short_text',
                        'full_text',
                        'avg_salary'
                    ];
                    $values = [
                        "fxtr name",
                        "fxtr h1",
                        time(),
                        "http://mods.edunetwork.loc/banners/fxtr.jpg",
                        "http://mods.edunetwork.loc/banners/fxtr_big.jpg",
                        "fxtr short desc",
                        "fxtr full desc ",
                        100500
                    ];
                    $data = $school->createCollegeProfession($fields, $values);
                    break;
            }

            if ($tabl_type != '') {
                $can_export = AuthHelper::canExportData();

                $token = $_POST['tkn'] != '' ? $_POST['tkn'] : '';
                $tokens = [
                    'Mwi7nRyczUgNf8O9pXaHqo5n2HcYK5fE'  => '2023.01.31',
                    'hKp7NxWCHKEJb1qT9dNO0ORR92zqcR3a'  => '2023.02.15',
                    'vvPodPx8sc9HXJUKCXal8ZarLSsasLMO'  => '2023.02.28',
                    'XejCy0fHx0WTFBL1PsyEJM4k7lbHmpFw'  => '2023.03.15',
                ];

                $dt = date('Y.m.d', time());
                $has_token = false;
                if (isset($tokens[$token])) {
                    $f_time = strtotime($tokens[$token]);
                    if ($f_time <= $dt) {
                        $has_token = true;
                    }
                }
                if ($can_export || $has_token) {
                    $data = $school->getAnyTable($tabl_type,$type,$filter);
                } else {
                    $result = [
                        'message'   => 'У вас нет доступа к экспорту данных',
                        'data'      => '',
                        'success'   => false
                    ];
                }

            }

            if ($data['success']) {
                //echo json_encode($data['data'], JSON_UNESCAPED_UNICODE );
                if ($type == 'json') {
                    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    echo $data['data'];
                }
            } else {
                /*
                $data['token'] = $token;
                $data['h_post'] = $has_post;
                $data['h_get'] = $has_get;
                $data['h_tkn'] = $has_token;
                $data['t_type'] = $tabl_type;
                $data['dt'] = $dt;

                $data['post'] = $_POST;
                $data['get'] = $_GET;
                */
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
        } else {
            echo json_encode($zero_output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        break;
    case 'paneler':
        require("classes/paneler.php");
        paneler::form($userId);
        break;
    case 'panel-rules':
        require("classes/paneler.php");
        paneler::rules();
        break;
    case 'paneler-active':
        require("classes/paneler.php");
        paneler::result();
        break;
    case 'professii':
        require("classes/general.php");
        general::professii($userId);
    break;
    case 'profTest':
        require("classes/general.php");
        general::profTest($userId);
    break;
    case 'professiiArticle':
        require("classes/general.php");
        general::professiiArticle($userId);
    break;
    /*********************/
    case 'art-cat':
        require("classes/article.php");
        article::showCat();
        break;
    case 'art-redir':
        require("classes/article.php");
        article::redir();
        break;
    case 'article':
        require("classes/article.php");
        article::show();
        break;
    /********************/
    case 'faq':
        require("classes/pages.php");
        pages::faq();
        break;
    case 'vuz':
        require("classes/vuz.php");
        vuz::show();
        break;
    case 'faculties':
        require("classes/vuz.php");
        if ($_GET['faculty']) {
            vuz::faculty($userId, $_GET['vuz'], $_GET['faculty']);
        } else {
            vuz::faculties($userId);
        }
        //vuz::faculties($userId);
        /*
        $faculty = new FacultiesHelper($container->get('Doctrine\ORM\EntityManager'), $userId);
        $faculty->getList();
        */
        break;
    case 'specs':
        require("classes/vuz.php");
        if ($_GET['spec']) {
            vuz::specokso($userId);
        } else {
            vuz::specs($userId);
        }

        break;
    case 'spec':
        require("classes/vuz.php");
        vuz::spec($userId);
        break;
    case 'sect':
        require("classes/vuz.php");
        vuz::sect($userId);
        break;
    case 'opinions':
        require("classes/vuz.php");
        vuz::opinions($userId);
        break;
    case 'data':
        require("classes/vuz.php");
        vuz::data($userId);
        break;
    case 'specsList':
        require("classes/pages.php");
        pages::specsList();
        break;
    case 'specDesc':
        require("classes/pages.php");
        pages::specDesc();
        break;
    case 'ege':
        require("classes/general.php");
        general::ege($userId);
        break;
    case 'thanks':
        require("classes/general.php");
        general::thanks($userId);
        break;
    case 'limit':
        require("classes/general.php");
        general::limit($userId);
        break;
    case 'jour':
        //echo 'jour';
        require("classes/Journal.php");
        require("classes/UrlHelper.php");
        $params = UrlHelper::getParamsFromUrl($_SERVER['REQUEST_URI']);
        Journal::ShowList($params);
        break;
    case 'jourItem':
        //echo 'jourItem';
        require("classes/vuz.php");
        require("classes/Journal.php");
        Journal::ShowItemById(intval($_GET['id']));
        break;
    case 'statistics':
        require("classes/Statistics.php");
        (new Statistics())->createTableData(intval($_GET['city_id']), intval($_GET['year']));
        break;
    case 'monitoring':
        $className = $_GET['table'];
        require_once "classes/tables/monitoring/{$className}.php";
        (new $className())->createFile();
        break;
    default:
        //echo 'FY PHD';
        require("classes/general.php");
        if ($_GET['subject']) {
            //echo 'default city';
            general::catalog($userId);
        } else {
            //echo 'default general';
            general::main($userId);
        }
        break;
}
