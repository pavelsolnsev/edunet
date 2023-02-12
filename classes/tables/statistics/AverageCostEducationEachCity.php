<?php

require_once 'BaseTablesStatistics.php';

class AverageCostEducationEachCity extends BaseTablesStatistics
{
    /**
     * Наименование таблицы
     */
    private const TABLE_NAME = 'Стоимость обучения в вузах';

    /**
     * Наименование первой таблицы
     */
    private const HEADER_NAME = '1. Показатель средней стоимости получения первого высшего образования в 20';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = [
        'Город', 'Средняя стоимость'
    ];

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
        // Формируем заголовок таблиц
        $startLine = $this->getNameTable($sheet, $columnPosition, $startLine, self::TABLE_NAME);

        // Формируем заголовок первой таблицы
        $startLine = $this->getHeaderTable($sheet, $columnPosition, $startLine, self::HEADER_NAME . $this->year);

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
            SELECT `cities`.`name`, round(AVG(`specs`.`f_cost`)) as `cost`
            FROM `general`.`cities` 
            LEFT JOIN `vuz`.`vuzes` ON `vuzes`.`city_id`=`cities`.`id`
            LEFT JOIN `vuz`.`specs` ON `specs`.`vuz_id`=`vuzes`.`id`
            WHERE `f_cost` != 0 AND `f_cost` IS NOT NULL
            GROUP BY `cities`.`name`
            ORDER BY `cost` DESC
            LIMIT '. self::LIMIT
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