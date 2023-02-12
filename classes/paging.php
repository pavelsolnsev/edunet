<?php
class paging {
	function pages($total, $show) {
		if ($total <= $show) {
			$total = 0;
		}
		elseif ($total % $show == 0) {
			$total = $total / $show;
		}
		else {
			$total = intval($total / $show + 1);
		}
		#total - count of pages
		if(empty($_GET['page']) || $_GET['page'] == 0){
			$curentpage = 0;
			$start = 0;
			if(intval($total-$show) <= 0) {
				$end = $show+($total-$show);
			}
			else {
				$end = $show;
			}
		}
		elseif($_GET['page'] == $total){
			$curentpage = $total;
			if(($curentpage-$show) <= 0) {
				$start = 0; #$curentpage;
			}
			else {
				$start = $curentpage-$show;
			}
			$end = $total;
		}
		else{
			$curentpage = intval($_GET['page']);
			if(($curentpage-intval($show/2)) <= 0) {
				$start = 0;
			}
			else {
				$start = $curentpage-intval($show/2);
			}
			if(($curentpage+intval($show/2)) >= $total) {
				$end = $total;
			}
			elseif($start > 0) {
				$end = $show+$start;
			}
			else {
				$end = $show;
				if($end>$total) {
					$end=$total;
				}
			}
		}
		if($total<10) {
			$hideBE=false;
		}
		else {
			$hideBE=true;
		}
		$_SERVER["QUERY_STRING"] = str_replace('&amp;', '&', $_SERVER["QUERY_STRING"]);
		$_SERVER["QUERY_STRING"] = preg_replace('/page=\d+&*/','',$_SERVER["QUERY_STRING"]);
		$_SERVER["QUERY_STRING"] = preg_replace('/subject=\d+&*/','',$_SERVER["QUERY_STRING"]);
		$_SERVER["QUERY_STRING"] = preg_replace('/city=\d+&*/','',$_SERVER["QUERY_STRING"]);
		$_SERVER["QUERY_STRING"] = preg_replace('/direct=\d+&*/','',$_SERVER["QUERY_STRING"]);
		$_SERVER["QUERY_STRING"] = preg_replace('/lvl=[fsm]&*/','',$_SERVER["QUERY_STRING"]);	
		
		$nav='';
		$pageline='';
		if($curentpage!=0) {
			if($hideBE) {
				$pageline.='<a class="btn center-block" href="?'.$_SERVER["QUERY_STRING"].'">В начало</a>';
			}
			if($curentpage==1) {
				$nav.='<a class="btn center-block" href="?'.(($_SERVER["QUERY_STRING"])?('&'.$_SERVER["QUERY_STRING"]):("")).'">Назад</a>';
			}
			else {
				$nav.='<a class="btn center-block" href="?page='.($curentpage-1).(($_SERVER["QUERY_STRING"])?('&'.$_SERVER["QUERY_STRING"]):("")).'">Назад</a>';
			}
		}

		for($k=$start;$k<$end;$k++) {
			if($k==$curentpage) {
				$pageline.= '<span id="selected">'.($k+1).'</span>';
			}
			elseif($k==0) {
				$pageline.='<span><a href="?'.$_SERVER["QUERY_STRING"].'">'.($k+1).'</a></span>';
			}
			else {
				$pageline.='<span><a href="?page='.$k.(($_SERVER["QUERY_STRING"])?('&'.$_SERVER["QUERY_STRING"]):("")).'">'.($k+1).'</a></span>';
			} 
		}
		if(($curentpage+1)!=$total) {
			$nav.='<a class="btn center-block" href="?page='.($curentpage+1).(($_SERVER["QUERY_STRING"])?('&'.$_SERVER["QUERY_STRING"]):("")).'">Далее</a>';
			if($hideBE) {
				$pageline.='<a class="btn center-block" href="?page='.($total-1).(($_SERVER["QUERY_STRING"])?('&'.$_SERVER["QUERY_STRING"]):("")).'">В конец</a>';
			}
		}
		$pageline='<p>'.$nav.'</p>'.$pageline;
		return($pageline);
	}
	function Page() {
        return ($_GET['page'] ? intval($_GET['page']) : 0);
	}
}
?>