<?php

//ini_set('display_errors', '1');
//error_reporting(E_ALL);

require '../vendor/autoload.php';

class Statistics
{
    /**
     * Массив классов таблиц
     */
    private const CLASSES_LIST = [
        NumberUniversitiesAndBranches::class, // Создаем таблицу "Количество вузов и филиалов"
        VuzesLargestNumberStudents::class, // Создаем таблицу "Вузы  с наибольшей численностью студентов"
        LargestIncreaseStudents::class, // Создаем таблицу "Вузы с наибольшим приростом студентов"
        PassingScoreBudgetPlaces::class, // Создаем таблицу "Проходной балл ЕГЭ на бюджетные места"
        PassingScoreCommercialPlaces::class, // Создаем таблицу "Проходной балл ЕГЭ на коммерческие места"
        AverageCostEducationEachCity::class, // Показатель средней стоимости получения первого высшего образования в ... году (по каждому городу)
        CostStudyingPublicUniversitiesCity::class, // Стоимость обучения в государственных вузах города в ... году
        CostStudyingNonStateUniversitiesCity::class, // Стоимость обучения в негосударственных вузах города в ... году
        TheMostExpensiveUniversitiesCityIntramuralEducation::class, // Самые дорогие вузы города для очного обучения в ... году
        TheMostExpensiveUniversitiesCityExtramuralEducation::class, // Самые дорогие вузы города для заочного обучения в ... году
    ];

    public function createTableData($cityId, $year)
    {
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet(); // Выбираем первый лист в документе

        $columnPosition = 1; // Начальная координата x
        $startLine      = 3; // Начальная координата y

        // Формируем заголовок листа
        $startLine = $this->getHeaderSheet($sheet, $columnPosition, $startLine, $cityId);

        // Создаем перечень таблиц
        foreach (self::CLASSES_LIST as $item) {
            require_once "tables/statistics/{$item}.php"; // Подключаем файл используемой таблицы

            $className = new $item($cityId, $year);
            $startLine = $className->createTable($sheet, $columnPosition, $startLine);
        }

        $date = date("Y-m-d_H:i"); // Текущие дата и время
        $city = $this->getCity($cityId);
        $fileName = "monitoring_vuzes_{$city}_{$date}.xls";

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$fileName.'"');
        header('Cache-Control: max-age=0');

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
    }

    /**
     * Формируем название листа документа
     *
     * @param $sheet
     * @param $columnPosition
     * @param $startLine
     * @param $cityId
     *
     * @return int
     */
    private function getHeaderSheet($sheet, $columnPosition, $startLine, $cityId): int
    {
        $currentColumn = $columnPosition; // Указатель на первый столбец
        $currentColumn++;
        $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, 'ВУЗы ' . $this->getCity($cityId));

        return $startLine ++;
    }

    /**
     * Получить название текущего города
     *
     * @param $cityId
     *
     * @return string
     */
    private function getCity($cityId): string
    {
        $cities = $this->getCityDB();
        $cityArr = [];

        foreach ($cities as $city) {
            $cityArr[$city['id']] = $city['name'];
        }

        return $cityArr[$cityId];
    }

    private function getCityDB()
    {
        global $db;

        $query = '
            SELECT `id`, `rp` as name
            FROM `general`.`cities`
            WHERE `name` IN (
                "Москва", "Санкт-Петербург", "Новосибирск", "Екатеринбург", "Нижний Новгород", "Казань",
                "Челябинск", "Омск", "Самара", "Ростов-на-Дону", "Уфа", "Красноярск",
                "Пермь", "Воронеж", "Волгоград", "Краснодар", "Саратов", "Тюмень"
            )
        ';

        $statement = $db->query($query);

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

}