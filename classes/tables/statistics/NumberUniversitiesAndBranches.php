<?php

require_once 'BaseTablesStatistics.php';

class NumberUniversitiesAndBranches extends BaseTablesStatistics
{
    /**
     * Наименование таблицы
     */
    private const HEADER_NAME = '1. Количество вузов и филиалов';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = [
        '', 'Всего', 'Государственные', 'Коммерческие'
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
        // Формируем заголовок таблицы
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
            SELECT `vuzes`.`gos`, `vuzes`.`parent_id` FROM `vuz`.`vuzes` WHERE `city_id` = '. $this->cityId .'
        ';
    }

    /**
     * Обрабатывем данные из БД
     *
     * @param $vuzesCity
     *
     * @return array[]
     */
    private function getNumVuzesAndBrabches($vuzesCity): array
    {
        $state = $commercial = $branch = $stateBranch = $commercialBranch = 0;

        $arrBranches = [];
        $arrVuzes['name'] = 'Вузы';
        $arrVuzes['numVuzes'] = count($vuzesCity);

        foreach ($vuzesCity as $vuz) {
            if (1 == $vuz['gos']) $arrVuzes['state'] = ++$state;
            if (0 == $vuz['gos']) $arrVuzes['commercial'] = ++$commercial;

            if (isset($vuz['parent_id'])) {
                $arrBranches['name'] = 'Филиалы';
                $arrBranches['numBranches'] = ++$branch;
                if (1 == $vuz['gos']) $arrBranches['stateBranch'] = ++$stateBranch;
                if (0 == $vuz['gos']) $arrBranches['commercialBranch'] = ++$commercialBranch;
            }
        }

        return [$arrVuzes, $arrBranches];
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return array[]
     */
    protected function getData(): array
    {
        $query = $this->getQuery();
        $vuzesCity = $this->getDataDB($query);

        return $this->getNumVuzesAndBrabches($vuzesCity);
    }

}