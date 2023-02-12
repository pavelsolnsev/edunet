<?
class thanks {
	static function show($v_id) {
		global $db;
		$db->query('SELECT `cities`.`id` as city_id,`cities`.`name` as city,`cities`.`rp`, `subj_id` FROM `vuz`.`vuzes` LEFT JOIN `general`.`cities` ON `vuzes`.`city_id`=`cities`.`id` WHERE `vuzes`.`id`=?', $v_id);
		$row=$db->get_row();
		if($row['subj_id']==77 || $row['subj_id']==78) {
			$path.=$row['subj_id'].'/';
		}
		else {
			$path.=$row['subj_id'].'/'.$row['city_id'].'/';
		}
		$msg=
'<div id="thanks">
<h2>Спасибо за участие в проекте EduNetwork.ru!</h2>
<p>Мы искренне верим, что время потраченное на заполнение информации о Вашем вузе на проекте не будет потрачено впустую. Тысячи абитуриентов ежедневно нуждаются в актуальной информации от вузов, приходя в своих поисках и на наш проект. Среди них есть потенциальные студенты именно Вашего вуза.</p>
<p>Проект абсолютно бесплатен для всех участников. Вы можете поддержать развитие проекта и отблагодарить разработчиков, опубликовав новость на сайте Вашего вуза или подразделения с кратким описанием проекта и гиперссылками на страницы нашего ресурса.</p>
<p>Так же можно поддержать проект, разместив одну из ссылок ниже на страницах сайта Вашего вуза. Для этого достаточно скопировать HTML-код одного из блоков, размещенных ниже.</p>

<fieldset>
	<legend>Текстовый блок #1</legend>
	<p>Все <a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">вузы '.(($row['rp'])?($row['rp']):($row['city'])).'</a> в самом полном каталоге <a href="https://vuz.edunetwork.ru/" target="_blank">вузов России</a></p>
		<textarea class="ui-corner-all">Все <a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">вузы '.(($row['rp'])?($row['rp']):($row['city'])).'</a> в самом полном каталоге <a href="https://vuz.edunetwork.ru/" target="_blank">вузов России</a></textarea>
</fieldset>
<fieldset>
	<legend>Текстовый блок #2</legend>
	<p><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Каталог вузов '.(($row['rp'])?($row['rp']):($row['city'])).'</a></p>
	<textarea class="ui-corner-all"><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Каталог вузов '.(($row['rp'])?($row['rp']):($row['city'])).'</a></textarea>
</fieldset>
<fieldset>
	<legend>Текстовый блок #3</legend>
	<p><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Список вузов '.(($row['rp'])?($row['rp']):($row['city'])).'</a></p>
	<textarea class="ui-corner-all"><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Список вузов '.(($row['rp'])?($row['rp']):($row['city'])).'</a></textarea>
</fieldset>
<fieldset>
	<legend>Текстовый блок #4</legend>
	<p>Все <a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">вузы '.(($row['rp'])?($row['rp']):($row['city'])).'</a></p>
	<textarea class="ui-corner-all">Все <a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">вузы '.(($row['rp'])?($row['rp']):($row['city'])).'</a></textarea>
</fieldset>
<fieldset>
	<legend>Текстовый блок #5</legend>
	<p>Все <a href="https://vuz.edunetwork.ru/" target="_blank">вузы России</a></p>
	<textarea class="ui-corner-all">Все <a href="https://vuz.edunetwork.ru/" target="_blank">вузы России</a></textarea>
</fieldset>
<fieldset>
	<legend>Текстовый блок #6</legend>
	<p><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Институты '.(($row['rp'])?($row['rp']):($row['city'])).'</a></p>
	<textarea class="ui-corner-all"><a href="https://vuz.edunetwork.ru/'.$path.'" target="_blank">Институты '.(($row['rp'])?($row['rp']):($row['city'])).'</a></textarea>
</fieldset>
</div>';	
		echo $msg;
	}
}
?>