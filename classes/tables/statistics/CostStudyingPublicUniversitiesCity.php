<?php

require_once 'BaseTablesStatistics.php';

class CostStudyingPublicUniversitiesCity extends BaseTablesStatistics
{
    /**
     * Платное или бесплатное обучение в ВУЗе
     */
    private const IS_FREE = 1;

    /**
     * Очное образование
     */
    private const INTRAMURAL = 1;

    /**
     * Очно-заочное образование
     */
    private const PART_TIME = 2;

    /**
     * Заочное образование
     */
    private const EXTRAMURAL = 3;

    /**
     * Дистанционное образование
     */
    private const REMOTE = 4;

    /**
     * Наименование таблицы
     */
    private const HEADER_NAME = '2.Стоимость обучения в государственных вузах города в 20';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = [
        'Направление 
        ВУЗа',
        "Количество
         ВУЗов",
        'Средняя стоимость
         всех форм обучения',
        'Средняя стоимость 
        на очной форме',
        'Средняя стоимость
         на заочной форме'
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
     * @return string[]
     */
    private function getQuery(): array
    {
        $queryAll = '
            SELECT `dir2specs`.`name` as dir_name, COUNT(*) as count_spec, round(AVG(`specs`.`f_cost`)) as avg_cost
            FROM `general`.`cities`
            LEFT JOIN `vuz`.`vuzes` ON `vuzes`.`city_id`=`cities`.`id`
            LEFT JOIN `vuz`.`vuz2direct` ON `vuz2direct`.`vuz_id`=`vuzes`.`id`
            LEFT JOIN `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`
            LEFT JOIN `vuz`.`specs` ON `specs`.`vuz_id`=`vuzes`.`id`
            WHERE `cities`.`id`="'. $this->cityId .'" AND `vuzes`.`gos`="'. self::IS_FREE .'"
            GROUP BY dir_name
            ORDER BY dir_name DESC
        ';

        $queryIntramural =  '
            SELECT `dir2specs`.`name` as dir_name, round(AVG(`specs`.`f_cost`)) as avg_cost_1
            FROM `general`.`cities`
            LEFT JOIN `vuz`.`vuzes` ON `vuzes`.`city_id`=`cities`.`id`
            LEFT JOIN `vuz`.`vuz2direct` ON `vuz2direct`.`vuz_id`=`vuzes`.`id`
            LEFT JOIN `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`
            LEFT JOIN `vuz`.`specs` ON `specs`.`vuz_id`=`vuzes`.`id`
            WHERE
                `cities`.`id`="'. $this->cityId .'" AND
                `vuzes`.`gos`="'. self::IS_FREE .'" AND
                `specs`.`form`="'. self::INTRAMURAL .'"
            GROUP BY dir_name
            ORDER BY dir_name DESC
        ';

        $queryExtramural =  '
            SELECT `dir2specs`.`name` as dir_name, round(AVG(`specs`.`f_cost`)) as avg_cost_3_4
            FROM `general`.`cities`
            LEFT JOIN `vuz`.`vuzes` ON `vuzes`.`city_id`=`cities`.`id`
            LEFT JOIN `vuz`.`vuz2direct` ON `vuz2direct`.`vuz_id`=`vuzes`.`id`
            LEFT JOIN `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`
            LEFT JOIN `vuz`.`specs` ON `specs`.`vuz_id`=`vuzes`.`id`
            WHERE
                 `cities`.`id`="'. $this->cityId .'" AND
                 `vuzes`.`gos`="'. self::IS_FREE .'" AND
                 `specs`.`form` IN ("'. self::EXTRAMURAL .'", "'. self::REMOTE .'")
            GROUP BY dir_name
            ORDER BY dir_name DESC
        ';

        return [
            $queryAll, $queryIntramural, $queryExtramural
        ];
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return array
     */
    protected function getData(): array
    {
        list($queryAll, $queryIntramural, $queryExtramural) = $this->getQuery();

        $dataAll = $this->getDataDB($queryAll);
        $dataIntramural = $this->getDataDB($queryIntramural);
        $dataExtramural = $this->getDataDB($queryExtramural);

        $data = [];

        foreach ($dataAll as $key => $itemAll) {

            $dirName = $this->changeNameDirection($itemAll['dir_name']);
            $data[$key]['dir_name'] = $this->searchUniversityWithoutReferral($dirName);
            $data[$key]['count_spec'] = $itemAll['count_spec'];
            $data[$key]['avg_cost'] = $itemAll['avg_cost'];

            foreach ($dataIntramural as $itemIntramural) {
                if ($itemAll['dir_name'] == $itemIntramural['dir_name']) {
                    $data[$key]['avg_cost_1'] = $itemIntramural['avg_cost_1'];
                }
            }

            foreach ($dataExtramural as $itemExtramural) {
                if ($itemAll['dir_name'] == $itemExtramural['dir_name']) {
                    $data[$key]['avg_cost_3_4'] = $itemExtramural['avg_cost_3_4'];
                }
            }
        }

        return $data;
    }

    /**
     * Изменяется строка типа "Технические вузы" на "Технический"
     *
     * @param $string
     *
     * @return array|string|string[]
     */
    private function changeNameDirection($string)
    {
        return str_replace('е вузы', 'й', $string);
    }

    /**
     * Группе ВУЗов без направления присваивается метка 'ВУЗы без направления'
     *
     * @param $value
     *
     * @return mixed|string
     */
    private function searchUniversityWithoutReferral($value)
    {
        if ('' === $value) {
            $value = 'ВУЗы без направления';
        }

        return $value;
    }

}