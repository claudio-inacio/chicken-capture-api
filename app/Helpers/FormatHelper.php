<?php

namespace App\Helpers;

use App\Models\Campain\Config;
use App\Services\Campain\LogService;
use Illuminate\Support\Facades\DB;

class FormatHelper
{
    public static function removeSpecialCaracterTel($value): array|string
    {
        return str_replace(['(', ')','-',' ','+55'], '', $value);
    }

    public static function moneyToUS($value){
        if(empty($value)) return null;
        return str_replace(',','.', str_replace('.','', $value));
    }

    public static function formatCnpjCpf($value)
    {
        if (strlen($value) < 11){
            $value = self::addZeroCpf($value);
        }

        $newValue = preg_replace("/\D/", '', $value);
        if (strlen($newValue) === 11) {
            $newValue = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $newValue);
        } else if (strlen($newValue) === 14) {
            $newValue = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $newValue);
        }

        //debug('CPF/CNPJ', $value, $newValue);
        return $newValue;
    }

    static public function removePunctuationCpf($value)
    {
        $cpf = preg_replace('/[^0-9]/', "", $value);
        return $cpf;
    }

    public static function cpfIsValid($cpf): bool
    {
        // Extrai somente os números
        $cpf = preg_replace('/[^0-9]/is', '', $cpf);

        // Verifica se foi informado todos os digitos corretamente
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;

    }

    public static function formatDecimal(string $value) : string
    {
        if (empty($value))
            return "0";

        return number_format($value, 2, ',', '.');
    }

    public static function formatCep($value)
    {
        $length = strlen($value);
        if ($length < 9) {
            return substr_replace($value, '-', 5, 0);
        }
        return $value;
    }

    public static function addZeroCpf($value){
        return str_pad($value, 11, "0", STR_PAD_LEFT);
    }
    public static function monthExtenseToNumber(string $monthExtense){
        return match (strtoupper(trim($monthExtense))) {
            "JAN" => "01",
            "FEV" => "02",
            "MAR" => "03",
            "ABR" => "04",
            "MAI" => "05",
            "JUN" => "06",
            "JUL" => "07",
            "AGO" => "08",
            "SET" => "09",
            "OUT" => "10",
            "NOV" => "11",
            "DEZ" => "12",
            default => "",
        };
    }

    public static function monthYearToDateUS(string $monthYear, string  $separator): ?string
    {
        if(strlen($monthYear) < 5 || !str_contains($monthYear, $separator)) {
            return null;
        }

        $mesAno = explode($separator, $monthYear);
        $mes = FormatHelper::monthExtenseToNumber($mesAno[0]);

        return  $mes ? "20{$mesAno[1]}". "-" . $mes ."-01": "";
    }
}
