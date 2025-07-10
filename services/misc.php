<?php

namespace Utils\Services;

use SplitPHP\Service;
use SplitPHP\Utils;
use Exception;

class Misc extends Service
{
  private static $shared = [];

  public function matrixUnique($matrix, $innerObj = false)
  {
    foreach ($matrix as $k => $na) {
      $new[$k] = serialize($na);
    }

    $uniq = array_unique($new);

    foreach ($uniq as $k => $ser) {
      if ($innerObj)
        $new1[$k] = (object) unserialize($ser);
      else
        $new1[$k] = unserialize($ser);
    }

    return ($new1);
  }

  public function validateCPF($cpf)
  {
    if (empty($cpf)) {
      return false;
    }

    $cpf = preg_replace("/[^0-9]/", "", $cpf);
    $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

    if (strlen($cpf) != 11) {
      return false;
    } else if (
      $cpf == '00000000000' ||
      $cpf == '11111111111' ||
      $cpf == '22222222222' ||
      $cpf == '33333333333' ||
      $cpf == '44444444444' ||
      $cpf == '55555555555' ||
      $cpf == '66666666666' ||
      $cpf == '77777777777' ||
      $cpf == '88888888888' ||
      $cpf == '99999999999'
    ) {
      return false;
    } else {

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
  }

  public function validateCNPJ($cnpj)
  {
    $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);

    // Valida tamanho
    if (strlen($cnpj) != 14)
      return false;

    // Verifica se todos os digitos são iguais
    if (preg_match('/(\d)\1{13}/', $cnpj))
      return false;

    // Valida primeiro dígito verificador
    for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
      $soma += $cnpj[$i] * $j;
      $j = ($j == 2) ? 9 : $j - 1;
    }

    $resto = $soma % 11;

    if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
      return false;

    // Valida segundo dígito verificador
    for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
      $soma += $cnpj[$i] * $j;
      $j = ($j == 2) ? 9 : $j - 1;
    }

    $resto = $soma % 11;

    return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
  }

  public function validateUF($data)
  {
    $UFs = [
      'RO',
      'AC',
      'AM',
      'RR',
      'PA',
      'AP',
      'TO',
      'MA',
      'PI',
      'CE',
      'RN',
      'PB',
      'PE',
      'AL',
      'SE',
      'BA',
      'MG',
      'ES',
      'RJ',
      'SP',
      'PR',
      'SC',
      'RS',
      'MS',
      'MT',
      'GO',
      'DF'
    ];

    return in_array($data, $UFs, true);
  }

  public function getUserIP()
  {
    //whether ip is from the share internet  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    //whether ip is from the proxy  
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    //whether ip is from the remote address  
    else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  /**
   * Cleans a given string by removing special characters and replacing spaces with underscores.
   * 
   * This function processes the input string to remove any special characters (e.g., punctuation)
   * and converts spaces to underscores for a cleaner format.
   * 
   * @param   String  $string The input string to be processed.
   * @return  String  The cleaned string with special characters removed and spaces replaced by underscores.
   */
  public function stringToSlug(String $string)
  {
    $string = preg_replace('/[^\w\s]/u', '', $string);
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    $string = str_replace(' ', '-', $string);
    $string = strtolower($string);
    return $string;
  }

  public function dataBlackList($data, array $blacklist)
  {
    $data = (array) $data;
    foreach ($data as $key => $value) {
      if (in_array($key, $blacklist)) unset($data[$key]);
    }

    return $data;
  }

  public function dataWhiteList($data, array $whitelist)
  {
    $data = (array) $data;
    foreach ($data as $key => $value) {
      if (!in_array($key, $whitelist)) unset($data[$key]);
    }

    return $data;
  }

  public function dumpToXLS($data, $fileName = "data_dump.xls")
  {
    // File Name & Content Header For Download
    $filename = urlencode($fileName);
    header("Content-Filename: {$filename}");
    header("Content-Disposition: attachment; filename=\"{$fileName}\"");
    header("Content-Type: application/vnd.ms-excel");
    header("Access-Control-Expose-Headers: Content-Filename");

    //To define column name in first row.
    $column_names = false;
    // run loop through each row in $customers_data
    foreach ($data as $row) {
      // Force object to array:
      if (gettype($row) == 'object') $row = (array) $row;

      if (!$column_names) {
        echo implode("\t", array_keys($row)) . "\n";
        $column_names = true;
      }

      // The array_walk() function runs each array element in a user-defined function.
      array_walk($row, function (&$str) {
        $str = preg_replace("/\t/", " ", $str);
        $str = preg_replace("/,/", " ", $str);
        $str = preg_replace("/;/", " ", $str);
        $str = preg_replace("/\r?\n/", " ", $str);
        $str = preg_replace("/\r/", " ", $str);
        if (strstr($str, '"'))
          $str = '"' . str_replace('"', '""', $str) . '"';
      });
      echo implode("\t", array_values($row)) . "\n";
    }
    exit;
  }

  public function secondsToTime($secs)
  {
    $hours = floor($secs / (60 * 60));

    $divisor_for_minutes = $secs % (60 * 60);
    $minutes = floor($divisor_for_minutes / 60);

    $divisor_for_seconds = $divisor_for_minutes % 60;
    $seconds = ceil($divisor_for_seconds);

    return (object) [
      "h" => $hours,
      "m" => $minutes,
      "s" => $seconds
    ];
  }

  public function readCsvFile($filename)
  {
    $csvData = [];

    // Check if the file exists and is readable
    if (!file_exists($filename) || !is_readable($filename)) {
      throw new Exception("The provided filepath could not be found or isn't readable.");
    }

    // Open the CSV file
    if (($handle = fopen($filename, 'r')) !== false) {
      $header = null;

      // Read each row of the CSV file
      while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        // Ensure encoding is consistent and trim values
        $row = array_map(function ($item) {
          return mb_convert_encoding(trim($item), 'UTF-8', 'auto');
        }, $row);

        if (!$header) {
          $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);

          // Use the first row as the header (column names), cleaning it up
          $header = array_map('trim', $row);
        } else {
          // Combine the header with row values
          if (count($header) === count($row)) {
            $csvData[] = (object) array_combine($header, $row);
          } else {
            echo "Skipping row due to mismatched columns\n";
          }
        }
      }

      // Close the file after reading
      fclose($handle);

      return $csvData;
    }
  }

  public function readCsvFromString($csvString)
  {
    $csvData = [];

    // Split the CSV string into lines
    $lines = explode(PHP_EOL, $csvString);

    $header = null;

    // Process each line
    foreach ($lines as $line) {
      // Skip empty lines
      if (trim($line) === '') {
        continue;
      }

      // Parse the CSV row
      $row = str_getcsv($line);

      // Ensure encoding is consistent and trim values
      $row = array_map(function ($item) {
        return mb_convert_encoding(trim($item), 'UTF-8', 'auto');
      }, $row);

      if (!$header) {
        // Remove BOM from the first header cell if present
        $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', $row[0]);

        // Use the first row as the header (column names)
        $header = array_map('trim', $row); // Clean up the header
      } else {
        // Ensure row has the same number of columns as the header
        if (count($header) === count($row)) {
          // Combine the header with row values
          $csvData[] = (object) array_combine($header, $row);
        }
      }
    }

    return $csvData;
  }

  /**
   * Generate a dummy CPF, returning
   * @param   Boolean  $onlyNumbers Whether to return only digits (unformatted) or in CPF format (###.###.###-##)
   * @return  String  The generated CPF, either formatted or as plain digits depending on $onlyNumbers
   */
  public function generateCPF($onlyNumbers = false)
  {
    // Generate Random Body
    $cpf = [];
    for ($i = 0; $i < 9; $i++) {
      $cpf[] = rand(0, 9);
    }

    // Generate First Check Digit
    $sum = 0;
    for ($i = 0; $i < 9; $i++) {
      $sum += $cpf[$i] * (10 - $i);
    }
    $firstDigit = 11 - ($sum % 11);
    $cpf[] = $firstDigit > 9 ? 0 : $firstDigit;

    // Generate Second Check Digit
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
      $sum += $cpf[$i] * (11 - $i);
    }
    $secondDigit = 11 - ($sum % 11);
    $cpf[] = $secondDigit > 9 ? 0 : $secondDigit;

    // Return
    if ($onlyNumbers) return implode('', $cpf);
    return vsprintf('%d%d%d.%d%d%d.%d%d%d-%d%d', $cpf);
  }

  /**
   * Generate a dummy RG, returning
   * @param   Boolean  $onlyNumbers Whether to return only digits (unformatted) or in RG format (##.###.###-#)
   * @return  String  The generated RG, either formatted or as plain digits depending on $onlyNumbers
   */
  public function generateRG($onlyNumbers = false)
  {
    $rg = [];
    for ($i = 0; $i < 9; $i++) {
      $rg[] = rand(0, 9);
    }

    // Return
    if ($onlyNumbers) return implode('', $rg);
    return vsprintf('%d%d.%d%d%d.%d%d%d-%d', $rg);
  }

  public function share($name, $value = null)
  {
    if ($value) {
      self::$shared[$name] = $value;
    } else {
      return self::$shared[$name] ?? null;
    }
  }

  public function formatAddress($addressObj)
  {
    foreach ($addressObj as $key => $value) {
      if ($key == 'ds_addresscomplement' || !str_contains($key, 'address')) continue;

      if (empty($value)) return null;
    }

    $address = $addressObj->ds_addressstreet . ', ' . $addressObj->ds_addressnumber;
    if (!empty($addressObj->ds_addresscomplement)) {
      $address .= ', ' . $addressObj->ds_addresscomplement;
    }
    $address .= ', ' . $addressObj->ds_addressneighborhood . ', ' . $addressObj->ds_addresscity . ' - ' . $addressObj->do_addressuf;
    return $address;
  }

  public function printDataTable(string $cmdTitle, callable $getRows, array $columns, array $args)
  {
    // Extract and normalize our options
    $limit   = isset($args['--limit']) ? (int)$args['--limit'] : 10;
    $sortBy  = $args['--sort-by']         ?? null;
    $sortDir = $args['--sort-direction']  ?? 'ASC';
    unset($args['--limit'], $args['--sort-by'], $args['--sort-direction'], $args['--page']);

    $params = array_merge($args, [
      '$limit' => $limit,
      '$limit_multiplier' => 1, // No multiplier for pagination
      '$page'  => isset($args['--page']) ? (int)$args['--page'] : 1,
    ]);

    if ($sortBy) {
      $params['$sort_by']        = $sortBy;
      $params['$sort_direction'] = $sortDir;
    }

    $sortBy  = $params['$sort_by']         ?? null;
    $sortDir = $params['$sort_direction']  ?? 'ASC';

    // --- <== HERE: open STDIN in BLOCKING mode (no stream_set_blocking) ===>
    $stdin = fopen('php://stdin', 'r');
    // on *nix, disable line buffering & echo
    if (DIRECTORY_SEPARATOR !== '\\') {
      system('stty -icanon -echo');
    }

    $exit = false;
    while (!$exit) {
      // Clear screen + move cursor home
      if (DIRECTORY_SEPARATOR === '\\') {
        system('cls');
      } else {
        echo "\033[2J\033[H";
      }

      // Header & hints
      Utils::printLn($this->getService('utils/clihelper')->ansi("Welcome to the {$cmdTitle}!\n", 'color: cyan; font-weight: bold'));
      Utils::printLn("HINTS:");
      Utils::printLn("  • --limit={$limit}   (items/page)");
      Utils::printLn("  • --sort-by={$sortBy}   --sort-direction={$sortDir}");
      if (DIRECTORY_SEPARATOR === '\\') {
        Utils::printLn("  • Press 'n' = next page, 'p' = previous page, 'q' = quit");
      } else {
        Utils::printLn("  • ←/→ arrows to navigate pages, 'q' to quit");
      }
      Utils::printLn("  • Press 'ctrl+c' to exit at any time");
      Utils::printLn();

      $rows = [];
      try {
        $rows = $getRows($params);
      } catch (Exception $e) {
        // Restore terminal settings on *nix
        if (DIRECTORY_SEPARATOR !== '\\') {
          system('stty sane');
        }

        throw $e;
      }

      // Fetch & render
      if (empty($rows)) {
        Utils::printLn("  >> No items found on page {$params['$page']}.");
      } else {
        Utils::printLn(" Page {$params['$page']} — showing " . count($rows) . " items");
        Utils::printLn(str_repeat('─', 60));
        $this->getService('utils/clihelper')->table($rows, $columns);
      }

      // --- <== HERE: wait for exactly one keypress, blocking until you press ===>
      $c = fgetc($stdin);
      if (DIRECTORY_SEPARATOR === '\\') {
        $input = strtolower($c);
      } else {
        if ($c === "\033") {             // arrow keys start with ESC
          $input = $c . fgetc($stdin) . fgetc($stdin);
        } else {
          $input = $c;
        }
      }

      // Handle navigation
      if (DIRECTORY_SEPARATOR === '\\') {
        switch ($input) {
          case 'n':
            $params['$page']++;
            break;
          case 'p':
            $params['$page'] = max(1, $params['$page'] - 1);
            break;
          case 'q':
            $exit = true;
            break;
        }
      } else {
        switch ($input) {
          case "\033[C": // →
            $params['$page']++;
            break;
          case "\033[D": // ←
            $params['$page'] = max(1, $params['$page'] - 1);
            break;
          case 'q':
            $exit = true;
            break;
        }
      }
    }

    // Restore terminal settings on *nix
    if (DIRECTORY_SEPARATOR !== '\\') {
      system('stty sane');
    }

    // Cleanup
    fclose($stdin);
  }

  public function persistentCliInput(callable $rule, string $msg = 'Please enter a value')
  {
    $input = null;
    $stdin = fopen('php://stdin', 'r');
    while (true) {
      $input = trim(fgets($stdin));
      if ($rule($input)) break;

      Utils::printLn("  >> {$msg}: ");
    }
    fclose($stdin);
    return $input;
  }

  public function waitFor(callable $callback, $timeout = null)
  {
    $startTime = time();
    while (true) {
      // Call the callback function
      if ($callback()) break;

      // Check if the timeout has been reached
      if ($timeout !== null && (time() - $startTime >= $timeout))
        break; // Timeout reached, return null

      // Sleep for a short duration to avoid busy waiting
      usleep(100000); // Sleep for 100 milliseconds
    }
  }
}
