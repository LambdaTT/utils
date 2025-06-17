<?php

namespace Utils\Migrations;

use SplitPHP\DbMigrations\Migration;
use SplitPHP\Database\DbVocab;

class ProcedureGenerateDateseries extends Migration
{
  public function apply()
  {
    $this->Procedure('generate_dateseries')
      ->withArg('start_date', DbVocab::DATATYPE_DATE)
      ->withArg('end_date', DbVocab::DATATYPE_DATE)
      ->setInstructions(
        "DECLARE dt DATE;
    
        -- Drop the temporary table if it already exists
        DROP TEMPORARY TABLE IF EXISTS dateseries;
        
        -- Create a temporary table to hold the dateseries data
        CREATE TEMPORARY TABLE dateseries (
            day INT,
            month INT,
            year INT,
            weekday INT,
            full_date DATE
        );
        
        SET dt = start_date;
        
        WHILE dt <= end_date DO
            INSERT INTO dateseries(day, month, year, weekday, full_date)
            VALUES (DAY(dt), MONTH(dt), YEAR(dt), WEEKDAY(dt) + 1, dt);
            
            SET dt = DATE_ADD(dt, INTERVAL 1 DAY);
        END WHILE;"
      );
  }
}
