<?php
    use Edunetwork\Common\Domain;

    function isSpecActual($ymd, $mode=false)
    {
        $y=date("Y");
        if($mode === "panel") {
            $ymd=explode("-", $ymd);
            if($ymd[0]==$y || ($ymd[0]==($y-1) && $ymd[1]>'10')) {
                return (true);
            } else {
                return (false);
            }
        }

	if($mode) {
        return (true);
    }
    else {
       if ($ymd == $y) {
            return (true);
        } else {
            return (false);
        }
    }
}

    function firstCharUp($s) {
	return(mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($s, 1, mb_strlen($s), 'UTF-8')); 
}

    function get_roof($region=false) {
        global $ACC;
        $_tpl=new tpl;
        $home = HOME.'vuz.edunetwork.ru/';

        $_tpl->start($home.'tpl/header.html');

        if($region) {
            $region='
                <div id="region" class="col m3 l1 hide-on-small-only truncate">
                    <a href="#"><i class="material-icons small grey-text text-darken-1">expand_more</i><span>'.
                    (($region == 99) ? ('Выбрать город') : ($region))
                    .'</span></a>
                </div>';
                $regList = file_get_contents($home.'tpl/header_region.html');
            } else {
                $region  = '&nbsp;';
                $regList = '';
            }

        $html=$_tpl->replace(array(
            "[acc]"=>$ACC,
            "[reg]"=>$region,
            "[regList]"=>$regList
        ));

        return($html);
    }

    function get_head($title, $desc, $kw, $extra = '', $canonicalUrl = '')
    {
        $v = 'asd1001';

        $canonical = '';
        if($canonicalUrl)
            $canonical = '<link rel="canonical" href="'.$canonicalUrl.'"/>';
        $o = '
            <head>
                <meta charset="utf-8">
                <title>'.$title.'</title>
                <meta name="Description" content="'.$desc.'" />
                <meta name="Keywords" content="'.$kw.'" />    
                <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no"/>
                <link rel="Icon" type="image/x-icon" href="/favicon.ico" />
                <link rel="shortcut icon" type="image/x-icon" href="/favicon32.ico" />
                '.$canonical.'        
                <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
                <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" />
                <link href="//static'.DOMAIN.'/css2/cache/vuzPack.css?'.$v.'" rel="stylesheet" />
                <link href="/assets/styles.css?v=523452" rel="stylesheet" />
        
                <link href="/assets/quiz.css" rel="stylesheet" />
                <link href="/assets/swiper-bundle.css" rel="stylesheet" />                
                '.Domain::generateTplDomain().'
                <!-- Google Tag Manager -->
                <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push(
        
                {"gtm.start": new Date().getTime(),event:"gtm.js"}
                );var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";j.async=true;j.src=
                "https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,"script","dataLayer","GTM-T2WP5BF");</script>
                <!-- End Google Tag Manager -->
            
                <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
                <script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
                <script src="//static'.DOMAIN.'/js2/comps/matSelectFix.js"></script>
                <script src="//static'.DOMAIN.'/js2/cache/vuzPack.js?'.$v.'" type="text/javascript"></script>'.$extra.'
                </head>';

        return ($o);

    }

    function getSecondGtmCode() {
    return '<!-- Google Tag Manager (noscript) -->
            <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-T2WP5BF"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
                    <!-- End Google Tag Manager (noscript) -->';
}

    function rodpad($total, $words) {
    $total = abs($total) % 100;
    $total1 = $total % 10;
    if ($total > 10 && $total < 20) {
        return($words[0]);
    } elseif($total1 > 1 && $total1 < 5) {
        return($words[1]);
    } elseif($total1 == 1) {
        return($words[2]);
    } else {
        return($words[3]);
    }
}

    function PR($o)
    {
        $bt = debug_backtrace();
        $bt = $bt[0];
        $dRoot = $_SERVER["DOCUMENT_ROOT"];
        $dRoot = str_replace("/", "\\", $dRoot);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);
        $dRoot = str_replace("\\", "/", $dRoot);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);
        ?>
        <div style='font-size:9pt; color:#000; background:#fff; border:1px dashed #000;'>
            <div style='padding:3px 5px; background:#99CCFF; font-weight:bold;'>File: <?php echo $bt["file"] ?>
                [<?php echo $bt["line"] ?>]
            </div>
            <pre style='padding:10px;'><?php
                print_r($o) ?></pre>
        </div>
        <?php
    }
