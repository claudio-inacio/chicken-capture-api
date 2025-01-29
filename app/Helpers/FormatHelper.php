<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\DB;

class FormatHelper
{
    public static function removeSpecialCaracterTel($value): array|string
    {
        return str_replace(['(', ')','-',' ','+55'], '', $value);
    }

    public static function formatPhoneNumber($n): bool|string
    {
        $tam = strlen(preg_replace("/[^0-9]/", "", $n));

        if ($tam == 13) {
            // COM CÓDIGO DE ÁREA NACIONAL E DO PAIS e 9 dígitos
            return "+".substr($n, 0, $tam-11)." (".substr($n, $tam-11, 2).") ".substr($n, $tam-9, 5)."-".substr($n, -4);
        }
        if ($tam == 12) {
            // COM CÓDIGO DE ÁREA NACIONAL E DO PAIS
            return "+".substr($n, 0, $tam-10)." (".substr($n, $tam-10, 2).") ".substr($n, $tam-8, 4)."-".substr($n, -4);
        }
        if ($tam == 11) {
            // COM CÓDIGO DE ÁREA NACIONAL e 9 dígitos
            return " (".substr($n, 0, 2).") ".substr($n, 2, 5)."-".substr($n, 7, 11);
        }
        if ($tam == 10) {
            // COM CÓDIGO DE ÁREA NACIONAL
            return " (".substr($n, 0, 2).") ".substr($n, 2, 4)."-".substr($n, 6, 10);
        }
        if ($tam <= 9) {
            // SEM CÓDIGO DE ÁREA
            return substr($n, 0, $tam-4)."-".substr($n, -4);
        }

        return false;
    }

    public static function moneyToUS($value){
        if(empty($value)) return null;
        return str_replace(',','.', str_replace('.','', $value));
    }

    public static function brlTodecimal($brl, $casasDecimais = 2) {
        if(empty($brl) || $brl == "") return 0;
        // Se já estiver no formato USD, retorna como float e formatado
        if(preg_match('/^\d+\.{1}\d+$/', $brl))
            return (float) number_format($brl, $casasDecimais, '.', '');
        // Tira tudo que não for número, ponto ou vírgula
        $brl = preg_replace('/[^\d\.\,]+/', '', $brl);
        // Tira o ponto
        $decimal = str_replace('.', '', $brl);
        // Troca a vírgula por ponto
        $decimal = str_replace(',', '.', $decimal);
        return (float) number_format($decimal, $casasDecimais, '.', '');
    }

    public static function brlToFourDecimal($brl): float|int
    {
        if (empty($brl) || $brl == "") return 0;

        // Se já estiver no formato USD, retorna como float e formatado
        if (preg_match('/^\d+\.{1}\d{1,4}$/', $brl)) {
            return (float) number_format($brl, 4, '.', '');
        }

        // Tira tudo que não for número, ponto ou vírgula
        $brl = preg_replace('/[^\d\.\,]+/', '', $brl);

        // Tira o ponto
        $decimal = str_replace('.', '', $brl);

        // Troca a vírgula por ponto
        $decimal = str_replace(',', '.', $decimal);

        // Retorna com até 4 casas decimais
        return (float) number_format($decimal, 4, '.', '');
    }

    public static function decimalToBr($value): string
    {
        return number_format($value,2,",",".");
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

    /**
     * @throws Exception
     */
    public static function dateToUs(string $date): string
    {
        return (new \DateTime(str_replace('/','-', $date)))->format("Y-m-d");
    }

    /**
     * @throws Exception
     */
    public static function dateToBr(string $date): string
    {
        return (new \DateTime(str_replace('/','-', $date)))->format("d-m-Y");
    }

    /**
     * @throws Exception
     */
    public static function dateToUsTimeStamp(string $date): string
    {
        return (new \DateTime(str_replace('/','-', $date)))->format("Y-m-d H:i:s");
    }
}
