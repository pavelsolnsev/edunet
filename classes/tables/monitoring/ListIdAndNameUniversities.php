<?php

require_once 'BaseTableMonitoring.php';

class ListIdAndNameUniversities extends BaseTableMonitoring
{
    /**
     * Наименование таблицы
     */
    private const TABLE_NAME = 'Список названий ВУЗов с их идентификаторами';

    /**
     * Массив названий полей таблицы
     */
    private const COLUMN_ARR = [
        '#', 'ID', 'Аббревиатура', 'Название',
    ];

    /**
     * Создаем excel-файл с таблицой данных
     *
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function createFile()
    {
        // Создаем лист документа
        list($spreadsheet, $sheet, $columnPosition, $startLine) = $this->getSpreadsheetData();

        // Получаем обработанные данные из БД
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
//        ORDER BY `vuzes`.`id`
        return '
            SELECT id, abrev, name
            FROM `vuz`.`vuzes`
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

//        global $db;
//        $db::d($result);
//        die;

        $data = [];
        $i = 0;

        foreach ($result as $key => $item) {
            $data[$key] = ["#" => strval(++$i)] + $item;
        }

        return $data;
    }
}