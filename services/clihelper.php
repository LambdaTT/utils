<?php

namespace Utils\Services;

use SplitPHP\Service;

class CliHelper extends Service
{
  /**
   * Mapping of foreground colors to ANSI codes.
   * @var int[]
   */
  protected array $colorMap = [
    'black'   => 30,
    'red'     => 31,
    'green'   => 32,
    'yellow'  => 33,
    'blue'    => 34,
    'magenta' => 35,
    'cyan'    => 36,
    'white'   => 37,
  ];

  /**
   * Mapping of background colors to ANSI codes.
   * @var int[]
   */
  protected array $bgMap = [
    'black'   => 40,
    'red'     => 41,
    'green'   => 42,
    'yellow'  => 43,
    'blue'    => 44,
    'magenta' => 45,
    'cyan'    => 46,
    'white'   => 47,
  ];

  /**
   * Applies ANSI styles to text using a CSS-like string.
   * If the terminal does not support ANSI, returns the plain text.
   *
   * @param string $text   Text to style.
   * @param string $styles CSS-like (color, background, font-weight, text-decoration, font-style).
   * @return string        Styled or plain text.
   */
  public function ansi(string $text, string $styles): string
  {
    if (! $this->supportsAnsi()) {
      return $text;
    }

    $codes = [];
    foreach (explode(';', $styles) as $part) {
      $part = trim($part);
      if ($part === '' || strpos($part, ':') === false) {
        continue;
      }

      [$prop, $val] = array_map('trim', explode(':', $part, 2));
      $val = strtolower($val);

      switch ($prop) {
        case 'color':
          if (isset($this->colorMap[$val])) {
            $codes[] = $this->colorMap[$val];
          }
          break;
        case 'background':
        case 'background-color':
          if (isset($this->bgMap[$val])) {
            $codes[] = $this->bgMap[$val];
          }
          break;
        case 'font-weight':
          if ($val === 'bold') {
            $codes[] = 1;
          }
          break;
        case 'text-decoration':
          if ($val === 'underline') {
            $codes[] = 4;
          } elseif (in_array($val, ['strike-through', 'line-through'], true)) {
            $codes[] = 9;
          }
          break;
        case 'font-style':
          if ($val === 'italic') {
            $codes[] = 3;
          }
          break;
      }
    }

    if (empty($codes)) {
      return $text;
    }

    $prefix = "\033[" . implode(';', $codes) . "m";
    $suffix = "\033[0m";

    return $prefix . $text . $suffix;
  }

  /**
   * Prints a collection of objects or arrays in table format, handling multibyte characters correctly.
   *
   * @param iterable       $items    Collection of objects (stdClass) or arrays.
   * @param array|null     $columns  Optional map of columns: ['field' => 'Header'].
   *                                If null, keys of first item are used.
   * @return void
   */
  public function table(iterable $items, ?array $columns = null): void
  {
    // 1) normalize rows
    $rows = [];
    foreach ($items as $item) {
      $rows[] = is_object($item) ? (array)$item : $item;
    }
    if (empty($rows)) {
      echo "(empty)\n";
      return;
    }

    // 2) columns & headers
    if ($columns === null) {
      $columns = array_combine(
        array_keys($rows[0]),
        array_keys($rows[0])
      );
    }
    $keys    = array_keys($columns);
    $headers = array_values($columns);
    $colCount = count($keys);

    // 3) natural max widths per column
    $natural = [];
    foreach ($keys as $i => $key) {
      $natural[$key] = mb_strwidth($headers[$i], 'UTF-8');
    }
    foreach ($rows as $row) {
      foreach ($keys as $key) {
        $w = mb_strwidth((string)($row[$key] ?? ''), 'UTF-8');
        if ($w > $natural[$key]) {
          $natural[$key] = $w;
        }
      }
    }

    // 4) get terminal width (fallback to 80)
    $termWidth = (int) @shell_exec('tput cols') ?: 80;

    // 5) compute available for content:
    // table total width = sum(widths) + 3*colCount + 1
    // so availableContent = termWidth - (3*colCount + 1)
    $available = $termWidth - (3 * $colCount + 1);
    if ($available < $colCount) {
      $available = array_sum($natural);
    }

    // 6) distribute budget across columns (small-to-large “water-filling”)
    $final = [];
    $remainCols = $colCount;
    $remainBudget = $available;
    // sort keys by natural width ascending
    $sorted = $keys;
    usort($sorted, function ($a, $b) use ($natural) {
      return $natural[$a] <=> $natural[$b];
    });
    foreach ($sorted as $key) {
      $fair = (int) floor($remainBudget / $remainCols);
      if ($natural[$key] <= $fair) {
        $final[$key] = $natural[$key];
      } else {
        $final[$key] = $fair;
      }
      $remainBudget -= $final[$key];
      $remainCols--;
    }

    // 7) padding helper
    $mbPad = function (string $s, int $len) {
      $diff = strlen($s) - mb_strlen($s, 'UTF-8');
      return str_pad($s, $len + $diff);
    };

    // 8) word-wrap helper
    $wrap = function (string $text, int $max) {
      $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
      $lines = [''];
      foreach ($words as $w) {
        $cw = mb_strwidth($w, 'UTF-8');
        $lw = mb_strwidth($lines[count($lines) - 1], 'UTF-8');
        if ($lw + $cw <= $max) {
          $lines[count($lines) - 1] .= $w;
        } else {
          if ($cw > $max) {
            // break the long chunk
            $s = $w;
            while (mb_strwidth($s, 'UTF-8') > $max) {
              $part = '';
              for ($i = 0; $i < mb_strlen($s, 'UTF-8'); $i++) {
                $c = mb_substr($s, $i, 1, 'UTF-8');
                if (mb_strwidth($part . $c, 'UTF-8') > $max) break;
                $part .= $c;
              }
              $lines[] = $part;
              $s = mb_substr($s, mb_strlen($part, 'UTF-8'), null, 'UTF-8');
            }
            $lines[] = $s;
          } else {
            $lines[] = ltrim($w);
          }
        }
      }
      return $lines;
    };

    // 9) build separator
    $sep = '+';
    foreach ($keys as $key) {
      $sep .= str_repeat('-', $final[$key] + 2) . '+';
    }

    // 10) print header
    echo $sep . "\n|";
    foreach ($keys as $i => $key) {
      echo ' ' . $mbPad($headers[$i], $final[$key]) . ' |';
    }
    echo "\n" . $sep . "\n";

    // 11) print rows
    foreach ($rows as $row) {
      $wrappedCols = [];
      foreach ($keys as $key) {
        $wrappedCols[$key] = $wrap((string)($row[$key] ?? ''), $final[$key]);
      }
      $lines = max(array_map('count', $wrappedCols));
      for ($i = 0; $i < $lines; $i++) {
        echo '|';
        foreach ($keys as $key) {
          $part = $wrappedCols[$key][$i] ?? '';
          echo ' ' . $mbPad($part, $final[$key]) . ' |';
        }
        echo "\n";
      }
      echo $sep . "\n";
    }
  }

  /**
   * Prints a simple list of values to the terminal, ordered or unordered.
   *
   * @param iterable<string> $items           Values to list.
   * @param bool             $ordered         True for ordered list; false for unordered. Default: false.
   * @param string           $unorderedBullet Bullet for unordered list (e.g. '-'). Default: '-'.
   * @param string           $orderedFormat   Format for ordered list with '%d' placeholder (e.g. '%d.'). Default: '%d.'.
   * @return void
   */
  public function listItems(
    iterable $items,
    bool $ordered = false,
    string $unorderedBullet = '-',
    string $orderedFormat = '%d.'
  ): void {
    $index = 1;
    foreach ($items as $item) {
      $text = (string) $item;
      if ($ordered) {
        $prefix = sprintf($orderedFormat, $index) . ' ';
        $index++;
      } else {
        $prefix = $unorderedBullet . ' ';
      }
      echo $prefix . $text . PHP_EOL;
    }
  }

  /**
   * Prompts user interactively to fill an associative array of data using standardized field configs.
   *
   * Each field config can be:
   *  - string: the prompt label (implicitly optional)
   *  - array or stdClass with keys:
   *      - 'label'      => string        Prompt label
   *      - 'default'    => mixed         Default value (optional)
   *      - 'validators' => array         Validators with keys:
   *           'required'   => bool        Whether field is required (default: false)
   *           'length'     => int         Maximum length (default: null). If null, no length check.
   *           'type'       => string      'int' | 'float' | 'string'
   *           'callback'   => callable    Custom validator or ['fn'=>callable,'message'=>string]
   *
   * Displays each prompt as:
   *     -> {label}(default: {default}){promptSuffix}
   * Reads from STDIN, applies default on empty input, validates, shows error, and retries until valid.
   *
   * @param array<string, string|array|stdClass> $fields       Field configurations to prompt
   * @param string                               $promptSuffix Suffix to display after prompt (default: ': ')
   * @return array<string, mixed>                             Associative array of user responses
   */
  public function inputForm(array $fields, string $promptSuffix = ': '): object
  {
    $results = [];
    $stdin = fopen('php://stdin', 'r');

    foreach ($fields as $key => $config) {
      // Support string, array, or stdClass
      if (is_string($config)) {
        $label = $config;
        $default = null;
        $validators = ['required' => false];
      } elseif (is_array($config) || $config instanceof \stdClass) {
        $cfg = is_array($config) ? $config : (array) $config;
        $label = $cfg['label'] ?? $key;
        $default = $cfg['default'] ?? null;
        $validators = $cfg['validators'] ?? ['required' => false];
      } else {
        throw new \InvalidArgumentException("Invalid configuration for field '{$key}'");
      }

      $value = null;
      do {
        $prompt = "    -> {$label}";
        if ($default !== null) {
          $prompt .= " (default: {$default})";
        }
        $prompt .= $promptSuffix;

        echo $prompt;
        $input = trim(fgets($stdin));
        if ($input === '' && $default !== null) {
          $input = (string) $default;
        }

        $error = null;

        // Required validator
        if (($validators['required'] ?? false) && $input === '') {
          $error = "{$label} is required.";
        }

        // Max Length validator
        if (! $error && isset($validators['length']) && !is_null($validators['length'])) {
          $length = $validators['length'];
          if (strlen($input) > $length) {
            $error = "{$label} must be at most {$length} characters.";
          }
        }

        // Type validator
        if (! $error && isset($validators['type']) && $input !== '') {
          $type = $validators['type'];
          if ($type === 'int' && !ctype_digit($input)) {
            $error = "{$label} must be an integer.";
          } elseif ($type === 'float' && !is_numeric($input)) {
            $error = "{$label} must be a number.";
          }
        }

        // Callback validator
        if (! $error && isset($validators['callback'])) {
          $cb = $validators['callback'];
          $message = null;
          if (is_array($cb) && isset($cb['fn'], $cb['message'])) {
            $fn = $cb['fn'];
            $message = $cb['message'];
          } elseif (is_callable($cb)) {
            $fn = $cb;
          } else {
            throw new \InvalidArgumentException("Invalid callback validator for field '{$key}'");
          }

          if (! call_user_func_array($fn, [$input, $results])) {
            $error = $message ?? "{$label} failed validation.";
          }
        }

        if ($error) {
          echo $this->ansi($error, 'color: red') . PHP_EOL;
        }
      } while ($error);

      // Cast according to type
      if (isset($validators['type']) && $input !== '') {
        if ($validators['type'] === 'int') {
          $input = (int) $input;
        } elseif ($validators['type'] === 'float') {
          $input = (float) $input;
        }
      }

      $results[$key] = $input;
    }

    return (object) $results;
  }

  /**
   * Automatically detects if the terminal supports ANSI codes.
   * @return bool
   */
  private function supportsAnsi(): bool
  {
    static $supported;
    if (null !== $supported) {
      return $supported;
    }

    // Respect NO_COLOR
    if (getenv('NO_COLOR') !== false) {
      return $supported = false;
    }

    // Windows detection
    if (DIRECTORY_SEPARATOR === '\\') {
      if (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON' || getenv('WT_SESSION') !== false) {
        return $supported = true;
      }
      return $supported = false;
    }

    // UNIX-like: check if STDOUT is a TTY
    $isTty = false;
    if (function_exists('posix_isatty')) {
      $isTty = @posix_isatty(STDOUT);
    } elseif (function_exists('stream_isatty')) {
      $isTty = @stream_isatty(STDOUT);
    }

    $term = getenv('TERM');
    if ($isTty && $term && strtolower($term) !== 'dumb') {
      return $supported = true;
    }

    return $supported = false;
  }
}
