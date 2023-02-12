<?php

require_once 'BaseTablesStatistics.php';

class PassingScoreCommercialPlaces extends BaseTablesStatistics
{
    /**
     * Наименование первой таблицы
     */
    const HEADER_NAME = '4.2 Проходной балл ЕГЭ на коммерческие места';

    /**
     * Бюджетные или коммерческие места
     */
    private const IS_PAYMENT = 'pay';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = ['ВУЗ', 'Балл'];

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
        // Уберем лишние строки
        $startLine -= 2;

        // Формируем заголовок второй таблицы
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
            SELECT `abrev`, round(`val`, 1)
            FROM `monit` LEFT JOIN `vuzes` ON `vuzes`.`id`=`vuz_id`
            WHERE
              `vuz_id` IN (SELECT `id` FROM `vuzes` WHERE `city_id`='. $this->cityId .' AND `delReason`="") 
              AND `year` = "'. $this->year .'" 
              AND `label`="'. self::IS_PAYMENT .'"
              AND `val` IS NOT NULL
            ORDER BY `val` DESC LIMIT '. self::LIMIT
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