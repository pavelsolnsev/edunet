<?php

define("base_path", "../system/");

require_once(base_path."config.php");
require_once '../vendor/autoload.php';

include('../system/classes/tpl_class.php');
include('classes/functions.php');

$tpl = new tpl;

$tpl->start('tpl/404.html');

$tpl->replace([
    '[head]' => get_head($title, $desc, $kw, '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js"></script>','404.html'),
    '[roof]' => get_roof(),
    '[footer]' => file_get_contents('tpl/footer.html')
]);

$tpl->out();
