<?php

require_once 'BaseTablesStatistics.php';

class VuzesLargestNumberStudents extends BaseTablesStatistics
{
    /**
     * Наименование таблицы
     */
    private const HEADER_NAME = '2. Вузы  с наибольшей численностью студентов';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = ['Наименование ВУЗа', 'Количество'];

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
        $startLine = $this->getTableHeader($sheet, $columnPosition, $startLine, self::COLUMN_ARR);

        // Формируем строки таблицы
        $startLine = $this->getTableRows($sheet, $columnPosition, $startLine, $this->getData());

        return $startLine;
    }

    /**
     * Формируем SQL-запрос
     *
     * @return string
     */
    private function getQuery(): string
    {
        return '
            SELECT `abrev`, SUM(CAST(`val` AS UNSIGNED INTEGER)) as s1 
            FROM `monit` LEFT JOIN `vuzes` ON `vuzes`.`id`=`vuz_id`
            WHERE 
              `vuz_id` IN (SELECT `id` FROM `vuzes` WHERE `city_id`='. $this->cityId .' AND `delReason`="") AND 
              `year` = "'. $this->year .'" AND `label` IN ("o","oz","z") AND `val` IS NOT NULL
            GROUP BY vuz_id
            ORDER BY `s1` DESC  LIMIT '. self::LIMIT
        ;
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return mixed
     */
    protected function getData()
    {
        $query = $this->getQuery();

        return $this->getDataDB($query);
    }

}