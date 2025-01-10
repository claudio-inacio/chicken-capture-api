<?php

namespace App\Services;

use Illuminate\Support\Collection;

class GenerateExcelService
{
    public static function csv(
        array $header,
        Collection|array $data,
        string|bool $fileName = false,
        string|bool $folder = false,
        $separator = ';',
    ) {
        $path = $folder ? "$folder/" . date("Y/m/d") : date("Y/m/d");
        $storagePath = storage_path() . "/app/public/$path";

        if (!$fileName) {
            $fileName = (date("H_i_s") . rand(0, 99999));
        }
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0777, true);
        }

        $fileName = "$fileName.csv";
        $file = fopen($storagePath . "/$fileName", 'w+');
        fwrite($file, implode($separator, $header) . PHP_EOL);
        foreach ($data as $column) {
            fputcsv($file, (array)$column, $separator);
        }
        fclose($file);


        return "storage/$path/$fileName";
    }
}
