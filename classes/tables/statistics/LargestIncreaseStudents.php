<?php

require_once 'BaseTablesStatistics.php';

class LargestIncreaseStudents extends BaseTablesStatistics
{
    /**
     * Наименование таблицы
     */
    const HEADER_NAME = '3. Вузы с наибольшим приростом студентов';

    /**
     * @param $cityId
     * @param $year
     */
    public function __construct($cityId, $year)
    {
        $this->cityId = $cityId;
        $this->year = $year;
    }

    /**
     * Создание таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     *
     * @return int
     */
    public function createTable($sheet, $columnPosition, $startLine): int
    {
        // Формируем заголовок первой таблицы
        $startLine = $this->getHeaderTable($sheet, $columnPosition, $startLine, self::HEADER_NAME);

        // Формируем шапку таблицы
        $startLine = $this->getTableHeader($sheet, $columnPosition, $startLine, $this->getNameColumns());

        // Формируем строки таблицы
        $startLine = $this->getTableRows($sheet, $columnPosition, $startLine, $this->getData());

        return $startLine;
    }

    /**
     * Формируем SQL-запрос
     *
     * @return string[]
     */
    private function getQuery(): array
    {
        $lastYear = $this->year;
        $beforeLastYear = $lastYear - 1;

        $queryLastYear = '
            SELECT `abrev`, SUM(CAST(`val` AS UNSIGNED INTEGER)) as s1
            FROM `monit` LEFT JOIN `vuzes` ON `vuzes`.`id`=`vuz_id`
            WHERE
              `vuz_id` IN (SELECT `id` FROM `vuzes` WHERE `city_id`='. $this->cityId .' AND `delReason`="") AND
              `year` = "'. $lastYear .'" AND `label` IN ("o","oz","z") AND `val` IS NOT NULL
            GROUP BY vuz_id
            ORDER BY `s1` DESC
        ';

        $queryBeforeLastYear =  '
            SELECT `abrev`, SUM(CAST(`val` AS UNSIGNED INTEGER)) as s1 
            FROM `monit` LEFT JOIN `vuzes` ON `vuzes`.`id`=`vuz_id`
            WHERE 
              `vuz_id` IN (SELECT `id` FROM `vuzes` WHERE `city_id`='. $this->cityId .' AND `delReason`="") AND 
              `year` = "'. $beforeLastYear .'" AND `label` IN ("o","oz","z") AND `val` IS NOT NULL
            GROUP BY vuz_id
            ORDER BY `s1` DESC
        ';

        return [
            $queryLastYear, $queryBeforeLastYear
        ];
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return array
     */
    protected function getData(): array
    {
        list($queryLastYear, $queryBeforeLastYear) = $this->getQuery();

        $dataLastYear = $this->getDataDB($queryLastYear);
        $dataBeforeLastYear = $this->getDataDB($queryBeforeLastYear);

        $data = [];

        foreach ($dataLastYear as $key => $itemLastYear) {
            foreach ($dataBeforeLastYear as $itemBeforeLastYear) {
                if ($itemLastYear['abrev'] === $itemBeforeLastYear['abrev']) {

                    $difference = $itemLastYear['s1'] - $itemBeforeLastYear['s1'];
                    $increase = round($difference/$itemBeforeLastYear['s1'] * 100);

                    $data[$key]['abrev'] = $itemLastYear['abrev'];
                    $data[$key]['lastYear'] = $itemLastYear['s1'];
                    $data[$key]['beforeLastYear'] = $itemBeforeLastYear['s1'];
                    $data[$key]['growth'] = $difference;
                    $data[$key]['increase'] = $increase;

                    break;
                }
            }
        }

        usort($data, function($a, $b){
            return ($b['increase']-$a['increase']);
        });

        return array_slice($data, 0, 15);
    }

    /**
     * Массив названий полей таблицы
     *
     * @return string[]
     */
    private function getNameColumns(): array
    {
        $lastYear = $this->year;
        $beforeLastYear = $lastYear - 1;
        $numberLastYear = "Численность за 20{$lastYear}";
        $numberBeforeLastYear = "Численность за 20{$beforeLastYear}";

        // Массив с названиями столбцов
        return ['ВУЗ', $numberLastYear, $numberBeforeLastYear, 'Прирост, чел.', 'Прирост, %'];
    }

}