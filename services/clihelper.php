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
    $rows = [];
    foreach ($items as $item) {
      if (is_object($item)) {
        $rows[] = (array) $item;
      } elseif (is_array($item)) {
        $rows[] = $item;
      }
    }

    if (empty($rows)) {
      echo "(empty)\n";
      return;
    }

    if ($columns === null) {
      $columns = array_combine(
        array_keys($rows[0]),
        array_keys($rows[0])
      );
    }

    $keys    = array_keys($columns);
    $headers = array_values($columns);

    // Compute display widths using mb_strwidth
    $widths = [];
    foreach ($keys as $i => $key) {
      $widths[$key] = mb_strwidth((string)$headers[$i], 'UTF-8');
    }
    foreach ($rows as $row) {
      foreach ($keys as $key) {
        $val = isset($row[$key]) ? (string)$row[$key] : '';
        $w   = mb_strwidth($val, 'UTF-8');
        if ($w > $widths[$key]) {
          $widths[$key] = $w;
        }
      }
    }

    // Helper to pad multibyte strings
    $mbPad = function (string $input, int $length): string {
      // str_pad counts bytes, so adjust for multibyte
      $diff = strlen($input) - mb_strlen($input, 'UTF-8');
      return str_pad($input, $length + $diff);
    };

    // Separator line
    $sep = '+';
    foreach ($keys as $key) {
      $sep .= str_repeat('-', $widths[$key] + 2) . '+';
    }

    echo $sep . "\n";

    // Header row
    $line = '|';
    foreach ($keys as $i => $key) {
      $line .= ' ' . $mbPad($headers[$i], $widths[$key]) . ' |';
    }
    echo $line . "\n";
    echo $sep . "\n";

    // Data rows
    foreach ($rows as $row) {
      $line = '|';
      foreach ($keys as $key) {
        $val = isset($row[$key]) ? (string)$row[$key] : '';
        $line .= ' ' . $mbPad($val, $widths[$key]) . ' |';
      }
      echo $line . "\n";
    }

    echo $sep . "\n";
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

          if (! call_user_func($fn, $input)) {
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
