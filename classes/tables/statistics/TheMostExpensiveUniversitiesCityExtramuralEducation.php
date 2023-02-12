<?php

require_once 'BaseTablesStatistics.php';

class TheMostExpensiveUniversitiesCityExtramuralEducation extends BaseTablesStatistics
{
    /**
     * Наименование первой таблицы
     */
    const HEADER_NAME = '5. Самые дорогие вузы города для заочного обучения в 20';

    /**
     * Очное образование
     */
    private const EXTRAMURAL = 3;

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = ['ВУЗ', 'Средняя стоимость'];

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
        $startLine = $this->getHeaderTable($sheet, $columnPosition, $startLine, $this->getHeader());

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
            SELECT `vuzes`.`abrev` as name, round(AVG(`specs`.`f_cost`)) as cost
            FROM `vuz`.`vuzes`
            LEFT JOIN `vuz`.`specs` ON `specs`.`vuz_id`=`vuzes`.`id`
            WHERE `city_id`="'. $this->cityId .'" AND `form`="'. self::EXTRAMURAL .'"
            GROUP BY cost
            ORDER BY cost DESC
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

    /**
     * Сформируем заголовок таблицы
     *
     * @return string
     */
    protected function getHeader(): string
    {
        return self::HEADER_NAME . $this->year . ' году';
    }
}