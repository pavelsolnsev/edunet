<?php

$lmDate         = strtotime('2022/10/18 13:50:55');
/*
if ($_SERVER["HTTP_IF_MODIFIED_SINCE"] && strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]) >= $lmDate) {
    $lmCode     = 200;
    $lmDateText = 'Last-modified: '. gmdate('D, d M Y H:i:s T', $lmDate);
} else {
    $lmCode     = 304;
    $lmDateText = header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');;
}
*/
$lmCode     = 200;
$lmDateText = 'Last-modified: '. gmdate('D, d M Y H:i:s T', $lmDate);

header($lmDateText, $lmCode);
echo 'vuz '.$lmDateText.'<br>';
echo 'server lm: '.$_SERVER["HTTP_IF_MODIFIED_SINCE"].'<br>';
/*
echo 'HOME -> '.$_SERVER['HOME'].'<br>';
echo 'SCFN -> '.$_SERVER['SCRIPT_FILENAME'].'<br>';
echo 'CDRT -> '.$_SERVER['CONTEXT_DOCUMENT_ROOT'].'<br>';
echo 'DCRT -> '.$_SERVER['DOCUMENT_ROOT'].'<br>';
*/