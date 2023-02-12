<?php

abstract class BaseTablesStatistics
{
    /**
     * Количество выбираемых ВУЗов из БД
     */
    protected const LIMIT = 15;

    /**
     * Стиль ячейки таблицы
     */
    private const ARR_STYLES = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['argb' => '000000'],
            ],
        ],
    ];

    /**
     * Идентификатор города
     *
     * @var
     */
    protected $cityId;

    /**
     * За какой год выбраны данные
     *
     * @var
     */
    protected $year;

    abstract public function __construct($cityId, $year);

    /**
     * Создание таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     *
     * @return int
     */
    abstract public function createTable($sheet, $columnPosition, $startLine): int;

    /**
     * Формируем заголовок таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     * @param $tableName
     *
     * @return int
     */
    protected function getNameTable($sheet, $columnPosition, $startLine, $tableName): int
    {
        $startLine += 4;
        $currentColumn = $columnPosition; // Указатель на первый столбец
        $currentColumn++;
        $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $tableName);

        return $startLine -= 2;
    }

    /**
     * Формируем заголовок таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     * @param $nameHeader
     *
     * @return int
     */
    protected function getHeaderTable($sheet, $columnPosition, $startLine, $nameHeader): int
    {
        $startLine += 4;
        $currentColumn = $columnPosition; // Указатель на первый столбец
        $currentColumn++;
        $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $nameHeader);

        return $startLine += 2;
    }

    /**
     * Формируем шапку таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     * @param $columns
     *
     * @return mixed
     */
    protected function getTableHeader($sheet, $columnPosition, $startLine, $columns)
    {
        // Указатель на первый столбец
        $currentColumn = $columnPosition;

        foreach ($columns as $column) {
            // Смещаемся вправо
            $currentColumn++;

            $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $column);
        }

        return $startLine;
    }

    /**
     * Формируем строки таблицы
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     * @param $data
     *
     * @return int
     */
    protected function getTableRows($sheet, $columnPosition, $startLine, $data): int
    {
        foreach ($data as $item) {
            // Перекидываем указатель на следующую строку
            $startLine++;
            // Указатель на первый столбец
            $currentColumn = $columnPosition;

            foreach ($item as $value) {
                $currentColumn++;

                $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $value);

                $this->applyStylesToTableCell($sheet, $currentColumn, $startLine);
            }
        }

        return $startLine;
    }

    /**
     * Получаем данные из БД
     */
    protected function getDataDB($query)
    {
        global $db;

        $statement = $db->query($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Обработка данных, полученных из БД
     *
     * @return mixed
     */
    abstract protected function getData();

    private function applyStylesToTableCell($sheet, $currentColumn, $startLine)
    {
        $coordinate = $sheet->getCellByColumnAndRow($currentColumn, $startLine)->getCoordinate();
        $sheet->getStyle($coordinate)->applyFromArray(self::ARR_STYLES);
    }

}