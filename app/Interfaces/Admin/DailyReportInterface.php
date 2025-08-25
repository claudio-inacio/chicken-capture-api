<?php

namespace App\Interfaces\Admin;

interface DailyReportInterface
{
    public function getData();
    public function sendAlert();
}
