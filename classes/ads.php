<?php
class ads {
	static function get(&$ads, $subj_id=0, $place=0, $oksoId=false) {
		global $db;
		$subj_id=(int) $subj_id;

		$maxPlace=(($_COOKIE['noBotAd'] === "1") ? (6) : (7));

        $sql=$sql1=$sql2='';
		if($place === 1) {
			$place = '`banners`.`place_id` = '.$place;
		} else {
            if ($place === 4) { // catalog
                $place = '`banners`.`place_id` <= ' . $maxPlace;
                $rotator = 1;

                if($oksoId) {
                    $sql1 = ' AND (`ban_okso`.`okso_id`=' . $oksoId . '  OR `ban_okso`.`okso_id` IS NULL) ';
                    $sql2 = ' `ban_okso`.`okso_id` DESC,';
                    /*
                    SELECT a.*
					FROM (
							SELECT
								`banners`.`id`, `banners`.`place_id`, `banners`.`header`,
								`banners`.`text`, `banners`.`ext`, `banners`.`bg`
							FROM `ads`.`banners`
							WHERE `banners`.`place_id` <= 7  AND DATE(NOW()) BETWEEN `banners`.`start` AND `banners`.`end`
						) a LEFT JOIN
						`ads`.`targets` ON `targets`.`ban_id` = a.`id`
						 LEFT JOIN `ads`.`ban_okso` ON `ban_okso`.`ban_id`=a.`id`
					WHERE
						(`targets`.`subj_id`=0 OR `subj_id`=77) AND (`ban_okso`.`okso_id`=62  OR `ban_okso`.`okso_id` IS NULL)
					ORDER BY  `ban_okso`.`okso_id` DESC, RAND()
                     */
                } else {
                    $sql1 = ' AND `okso_id` IS NULL'; // dont show okso target banners on non spec pages
                }
            } else {
                $place = '`banners`.`place_id` <= ' . $maxPlace . ' AND `banners`.`place_id` != 4';
                $sql1 = ' AND `okso_id` IS NULL'; // dont show okso target banners on non spec pages
            }
        }

		$db->query('
			SELECT a.* 
			FROM (					
					SELECT
						`banners`.`id`, `banners`.`place_id`, `banners`.`header`, 
						`banners`.`text`, `banners`.`ext`, `banners`.`bg`, `banners`.`button`
					FROM `ads`.`banners`
					WHERE '.$place.'  AND DATE(NOW()) BETWEEN `banners`.`start` AND `banners`.`end`
				) a LEFT JOIN 
				`ads`.`targets` ON `targets`.`ban_id` = a.`id` LEFT JOIN
				`ads`.`ban_okso` ON `ban_okso`.`ban_id`=a.`id`
			WHERE
				'.(($subj_id) ? ('(`targets`.`subj_id`=0 OR `subj_id`='.$subj_id.')') : ('`targets`.`subj_id`=0')).
            	$sql1.'
			ORDER BY '.$sql2.' RAND()');
		if($db->num_rows()) {
			while($ban=$db->get_row()) {
                $ban['place_id']=(int) $ban['place_id'];
				if($ban['place_id'] === 1) {/*
					if(!$ads[1] || $ban['subj_id']) { // priority for local target
						$ads[1]='
							<div id="head-rek"'.(($ban['bg'])?(' style="background:#'.$ban['bg'].'"'):('')).'>
								<a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" target="_blank">&nbsp;</a>
								<div class="center-block">
									<img src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" />
								</div>	
							</div>';
					}*/
                    $ads[1]='
                        <div id="head-yrek">
                            <a href="//priem.edunetwork.ru"></a> 
                            <div class="container">
                                <div class="row valign-wrapper">
                                    <div class="col s12 m8 l6 yrek-text-holder">
                                        <p class="yrek-title">Подбери ВУЗ</p>
                                    </div>
                                    <div class="col l2 hide-on-med-and-down yrek-icon-holder">
                                        <img src="//static.edunetwork.ru/imgs/ylead/image1.png" />
                                    </div>
                                    <div class="col s12 m4 l4 yrek-button-holder">
                                        <span>Узнать подробнее</span>
                                    </div>
                                </div>
                            </div>
							<i class="material-icons ads__close">close</i>
                        </div>';
				} elseif($ban['place_id'] === 2 || $ban['place_id'] === 5) {
					$ads[$ban['place_id']]='
						<div class="tgb-aside">
							<a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" target="_blank">&nbsp;</a>
							<div class="left hide-on-small-only">
								<img src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" />
							</div>
							<div class="text-holder">
								<p class="title">'.$ban['header'].'</p>
								'.$ban['text'].'
							</div>
						</div>';
				} elseif($ban['place_id'] === 3) {
					if(!$ads[3]) {
						$ads[3]='
							<div id="top-banner">
								<a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" target="_blank">
									<img class="img-responsive" src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" alt="Реклама" />
								</a>
							</div>';
					}
				} elseif($ban['place_id'] === 4) {
					if($rotator<4) {
						$ads[4].='
							<div class="tgb-main">
								<a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" target="_blank">&nbsp;</a>
								<div>
									<div class="left hide-on-small-only">
										<img src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" alt="rek-icon"/>
									</div>
									<div class="text-holder">
										<p class="title">'.$ban['header'].'</p>
										'.$ban['text'].'
									</div>
								</div>
								
							</div>';
						$rotator++;
						//<script type="text/javascript">window.onload=function(){ym(1493507, "params", {banId: '.$ban['id'].'});};</script>
					}
				} elseif($ban['place_id'] === 6) {
					if(!$ads[6]) {
						$ads[6]='
							<div id="bot-banner"><a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" class="img-responsive" target="_blank"><img src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" alt="Реклама" /></a>
							</div>';
					}
				} elseif($ban['place_id'] === 7) {
                    if(!$ads[7]) {
                        $ads[7]='
							<noindex>
								<div id="tgb-bottom"'.(($ban['bg'])?(' style="background:#'.$ban['bg'].'"'):('')).'>
									<a onclick="ym(1493507, \'reachGoal\', \'bannerClick\', {\'banClick\': '.$ban['id'].'});return(true);" href="//mods'.DOMAIN.'/banners/c'.$ban['id'].'" class="container" rel="nofollow" target="_blank">
										<div class="card horizontal">
											<div class="card-image valign-wrapper">
												<div><img src="//mods'.DOMAIN.'/images/v'.$ban['id'].'" alt="rek-icon" /></div>
												<div class="hide-on-med-and-down">'.$ban['header'].'</div>
											</div>
											<div class="card-stacked">
												<div class="card-content">
													<div class="valign-wrapper">'.$ban['text'].'</div>
													<div class="btn hide-on-small-only"><span class="hide-on-med-and-down">'.$ban['button'].'</span><i class="material-icons">arrow_forward</i></div>
												</div>
											</div>
										</div>
									</a>
									<i class="material-icons" id="closeBotTgb">close</i>
								</div>
							</noindex>';
                    }
                }
			}
			if($ads[4]) {
				$ads[4]='<div id="tgb-block">'.$ads[4].'</div>';
			}
		}
	}
}
/*<noindex>
<div id="tgb-bottom" style="background: #1D105F">
	<a href="#" class="container" rel="nofollow">
		<div class="card horizontal">
			<div class="card-image valign-wrapper">
				<div><img src="//43.img.avito.st/640x480/5310232443.jpg" ></div>
				<div class="hide-on-med-and-down">
					<p style="color: white">Московский финансово-промышленный университет</p>
				</div>
			</div>
			<div class="card-stacked">
				<div class="card-content">
					<div class="valign-wrapper"><span style="color: white">Более 100 направлений подготовки, гос. диплом, все формы обучения</span></div>
					<div class="btn hide-on-small-only"><span class="hide-on-med-and-down">Поступить</span><i class="material-icons">arrow_forward</i></div>
				</div>
			</div>
		</div>
	</a>
	<i class="material-icons" id="closeBotTgb">close</i>
</div>
</noindex>*/
?>