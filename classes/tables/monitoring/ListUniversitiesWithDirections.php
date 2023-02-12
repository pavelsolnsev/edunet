<?php

require_once 'BaseTableMonitoring.php';

class ListUniversitiesWithDirections extends BaseTableMonitoring
{
    /**
     * Наименование таблицы
     */
    private const TABLE_NAME = 'Список ВУЗов с направлениями';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = [
        '#', 'Аббревиатура', 'Полное название', 'Ссылка', 'Направление'
    ];

    /**
     * Создаем excel-файл с таблицой данных
     *
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function createFile()
    {
        list($spreadsheet, $sheet, $columnPosition, $startLine) = $this->getSpreadsheetData();

        // Получаеме обработанные данные из БД
        $data = $this->getData();

        // Получаем строковыю запись о количестве строк в таблице
        $numberRecordsStr = $this->getStrNumberRecords($data);

        // Формируем заголовок таблиц
        $startLine = $this->getTableName($sheet, $columnPosition, $startLine, self::TABLE_NAME . $numberRecordsStr);

        // Формируем шапку таблицы
        $startLine = $this->getTableHeader($sheet, $columnPosition, $startLine, self::COLUMN_ARR);

        // Формируем строки таблицы
        $this->getTableRows($sheet, $columnPosition, $startLine, $data);

        $date = $this->getDateTime();

        $fileName = $this->getFileName($date, self::class);

        $this->getHeaders($fileName);

        $this->outputFileToBrowser($spreadsheet);
    }

    /**
     * Формируем SQL-запрос
     *
     * @return string
     */
    private function getQuery(): string
    {
        return '
            SELECT `vuzes`.`abrev` as abrev, `vuzes`.`name` as name, `site`, `dir2specs`.`name` as dir_name
            FROM `vuz`.`vuzes`
            LEFT JOIN `vuz`.`vuz2direct` ON `vuz2direct`.`vuz_id`=`vuzes`.`id`
            LEFT JOIN `vuz`.`dir2specs` ON `dir2specs`.`id`=`vuz2direct`.`dir_id`
            ORDER BY `vuzes`.`id`
        ';
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return array
     */
    protected function getData(): array
    {
        $query = $this->getQuery();
        $result = $this->getDataDB($query);

        $data = [];
        $i = 0;

        foreach ($result as $key => $item) {
            $data[$key] = ["#" => strval(++$i)] + $item;
            $dir_name = $this->changeNameDirection($item['dir_name']);
            $data[$key]['dir_name'] = $this->searchUniversityWithoutReferral($dir_name);
        }

        return $data;
    }

}