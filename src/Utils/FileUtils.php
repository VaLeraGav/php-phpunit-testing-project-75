<?php

namespace App\Utils;

class FileUtils
{
    public static function create($date, string $fullPath, string $outputDir)
    {
//        if ($outputDir !== null) {
//            echo " \n" . " $normUrl \n" . "$outputDir\n";
//        }

//        @mkdir("/mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/var", 0777, true);
//        file_put_contents("/mnt/c/Users/Ucer/Desktop/Hexlet/php-testing-project-75/var/", $date);

        @mkdir("$outputDir", 0777, true);
        file_put_contents("$fullPath", $date);
    }
}