<?php
define("base_path","../system/");
require(base_path."config.php");
require(base_path."classes/pdo_class.php");
require(base_path."classes/tpl_class.php");

$tpl=new tpl;

$db=new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);

switch($_POST['act']) {
	case 'reply-add':
		require(base_path."classes/user_class.php");
		require("classes/vuz.php");
		vuz::replyAdd();
	break;
	case 'reply-del':
		require(base_path."classes/user_class.php");
		require(base_path."classes/date.php");
		require("classes/vuz.php");
		vuz::replyDel();
	break;
	case 'getOkso':
		require("classes/general.php");
		general::oksoByLvl();
	break;
	case 'paneler-req':
		require(base_path."classes/user_class.php");
		require("classes/paneler.php");
		paneler::request();
	break;
	case 'getReps':
		require(base_path."../rep.edunetwork.ru/classes/api.php");
		repApi::getSet();
	break;
	case 'checkVuz':
		require(base_path."classes/date.php");
		//require(base_path."classes/user_class.php");
		require("classes/pages.php");
		require("classes/functions.php");
			pages::checkVuzResult();
		break;
	case 'favor':
		require(base_path."classes/user_class.php");
		require("classes/pages.php");
		pages::favor();
	break;
	case 'monolog':
		require_once(base_path."classes/monolog.php");
		$fname = $_POST['fname'];
		$lines = (int) $_POST['lines'];
		$dir = $_POST['dir'];
		Monolog::throughFile($fname, $lines, $dir);
	break;

	case 'add-dod':
		$_POST['city_id']=(int) $_POST['city_id'];
		$headers="MIME-Version: 1.0\r\n"."Content-type: text/html; charset=utf-8\r\n"."From: edunetwork <support@edunetwork.ru>\r\n";
		mail('support@edunetwork.ru', 'ДОД', 'vuz|'.$_POST['city_id'].'|'.htmlspecialchars($_POST['url']), $headers);
		die('Ok');
	break;
	default:
		switch($_GET['act']) {
			case 'mainSearch':
				require('classes/general.php');
				general::main_search2();
			break;
			case 'oksoSearch':
				require('classes/general.php');
				general::oksoSearch();
			break;
			case 'placeSearch':
				require('classes/general.php');
				general::placeSearch();
			break;
			case 'monolog':
				require_once(base_path."classes/monolog.php");
				$fname = $_GET['fname'];
				$lines = (int) $_GET['lines'];
				$dir = $_GET['dir'];
				Monolog::throughFile($fname, $lines, $dir);
				break;
		}
	break;
}
?>
