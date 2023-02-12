<? 
set_time_limit(0);
define("base_path","../../system/");
require(base_path."config.php");
require(base_path."classes/pdo_class.php");
$db=new db;
$db->connect($cDbLogin, $cDbPass, $vuzDbName);
$r=$db->query('SELECT vuzes.id, parent_id, cities.name AS city, cities.`type` FROM vuzes  LEFT JOIN general.cities ON city_id=cities.id WHERE parent_id IS NOT NULL');
while($row=$db->get_row($r)) {
	$r1=$db->query('SELECT vuzes.name, abrev FROM vuzes WHERE vuzes.id=?', $row['parent_id']);
	while($f=$db->get_row($r1)) {
		$name=$f['name'].' — филиал в '.$row['type'].' '.$row['city'];
		$abr=$f['abrev'].' в '.$row['type'].' '.$row['city'];
		$db->query('UPDATE vuzes SET name=?, abrev=? WHERE id=?', $name, $abr, $row['id']);
	}
}

