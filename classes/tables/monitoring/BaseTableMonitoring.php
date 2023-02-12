<?php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require '../vendor/autoload.php';

abstract class BaseTableMonitoring
{
    /**
     * Формат даты
     */
    private const DATE_TIME_FORMAT = "Y-m-d_H:i";

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
     * Создание excel-файла с таблицей
     *
     * @return mixed
     */
    abstract public function createFile();

    /**
     * Создание листа документа
     *
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function getSpreadsheetData(): array
    {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet(); // Выбираем первый лист в документе

        $columnPosition = 1; // Начальная координата x
        $startLine      = 3; // Начальная координата y

        return [
            $spreadsheet, $sheet, $columnPosition, $startLine
        ];
    }

    protected function getStrNumberRecords($data): string
    {
        $count = count($data);

        return " (количество записей в таблице: $count шт.)";
    }

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
    protected function getTableName($sheet, $columnPosition, $startLine, $tableName): int
    {
        $startLine += 4;
        $currentColumn = $columnPosition; // Указатель на первый столбец
        $currentColumn++;
        $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $tableName);

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

            $this->applyStylesToTableCell($sheet, $currentColumn, $startLine);
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


    /**
     * Изменяется строка типа "Технические вузы" на "Технический"
     *
     * @param $string
     *
     * @return array|string|string[]
     */
    protected function changeNameDirection($string)
    {
        return str_replace('е вузы', 'ое', $string);
    }

    /**
     * Группе ВУЗов без направления присваивается метка 'ВУЗы без направления'
     *
     * @param $value
     *
     * @return mixed|string
     */
    protected function searchUniversityWithoutReferral($value)
    {
        if ('' === $value) {
            $value = 'ВУЗы без направления';
        }

        return $value;
    }

    /**
     * @return false|string
     */
    protected function getDateTime()
    {
        return date(self::DATE_TIME_FORMAT);
    }

    protected function getFileName($date, $className): string
    {
        return "monitoring{$className}_{$date}.xls";
    }

    protected function getHeaders($fileName)
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
    }

    protected function outputFileToBrowser($spreadsheet)
    {
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
    }

    private function applyStylesToTableCell($sheet, $currentColumn, $startLine)
    {
        $coordinate = $sheet->getCellByColumnAndRow($currentColumn, $startLine)->getCoordinate();
        $sheet->getStyle($coordinate)->applyFromArray(self::ARR_STYLES);
    }

}