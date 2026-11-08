<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2003-2029 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Pilou2-PilouJP 2026 Sept 22 Modified in v3.0.0-dev $
 * @since ZC v1.5.8
 */
class zcDate extends base
{
    protected bool $isStrftime = false;

    protected string $locale = '0';
    protected array $strftime2intl = ['from' => [], 'to' => []];

    protected bool $debug = false;

    /** @var object|null */
    protected ?object $dateObject = null;

    /**
     * The $zen_date_debug is a "soft" configuration setting that can be forced (defaults to false)
     * via the site's /includes/extra_datafiles/site_specific_overrides.php
     *
    */
    public function __construct()
    {
        global $zen_date_debug;

        if (isset($zen_date_debug) && $zen_date_debug === true) {
            $this->debug = true;
        }

        if (!function_exists('datefmt_create')) {
            trigger_error('PHP extension intl was not fount, it must be installed.', E_USER_WARNING);
        }

        $this->debug('zcDate construction: ' . PHP_EOL . var_export($this, true));
    }

    // -----
    // Initializes the class-based arrays that define the format conversions
    // from their strftime format (legacy support) and the formats used
    // by the IntlDateFormatter class.
    //
    // Each array's keys start out as the strftime format and a key's value is the converted format.
    // These arrays are then converted into a 'from' and a 'to' array that's used by the
    // method convertFormat's processing (essentially a str_replace on the submitted format string).
    //
    // strftime reference: https://www.php.net/manual/en/function.strftime.php
    // intl format reference: https://unicode-org.github.io/icu/userguide/format_parse/datetime/#datetime-format-syntax
    //
    /**
     * @since ZC v1.5.8
     */
    protected function initializeConversionArrays()
    {
        // -----
        // First, save the current locale; it's set by the main language file's (presumed) call to the
        // setlocale function.
        //
        $this->locale = setlocale(LC_TIME, '0');

        // -----
        // Using the current locale, retrieve the locale-specific 'short' date and time
        // formats.
        //
        $format = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE
        );
        $date_short = $format->getPattern();

        $format = new IntlDateFormatter(
            $this->locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::SHORT
        );
        $time_short = $format->getPattern();

        $strftime2intl = [
            '%a' => 'E',
            '%A' => 'EEEE',
            '%b' => 'MMM',
            '%B' => 'MMMM',
            '%d' => 'dd',
            '%H' => 'HH',
            '%k' => 'H',
            '%m' => 'MM',
            '%M' => 'mm',
            '%S' => 'ss',
            '%T' => 'HH:mm:ss',
            '%x' => $date_short,
            '%X' => $time_short,
            '%y' => 'yy',
            '%Y' => 'y',
            '%z' => 'ZZZZ',
            '%Z' => 'zzzz',
        ];
        $this->strftime2intl = [
            'from' => array_keys($strftime2intl),
            'to' => array_values($strftime2intl)
        ];
    }

    // -----
    // A couple of public functions to control whether or not the class' debug
    // processing is to be enabled or disabled.
    //
    /**
     * @since ZC v1.5.8
     */
    public function enableDebug(): void
    {
        $this->debug = true;
        $this->debug('Debug enabled: ' . \PHP_EOL . var_export($this, true));
    }
    /**
     * @since ZC v1.5.8
     */
    public function disableDebug(): void
    {
        $this->debug = false;
    }

    /**
     * @param string $format output method should start with a intlDate-format string
     * @param int $timestamp
     * @param string|null $calendar_locale Optional calendar-related locale. eg: 'ja_JP@calendar=japanese'
     *
     * @return false|string
     * @since ZC v1.5.8
     */
    public function output(string $format, int $timestamp = 0, ?string $calendar_locale = null): false|string
    {
        $converted_format = '';

        if ($timestamp === 0) {
            $timestamp = time();
        }

        if (preg_match('/(?<!%)(?:%%)*%\K[a-zA-Z]/', $format) == 1) { // Test if strftime parameters are used.
            $this->isStrftime = true;
            $this->initializeConversionArrays();
        }

        $this->locale = setlocale(LC_TIME, '0');
        if ($this->isStrftime === false) {
            $converted_format = $format;
        } else {
            $converted_format = str_replace($this->strftime2intl['from'], $this->strftime2intl['to'], $format);
        }

        $calendar = IntlDateFormatter::GREGORIAN;
        if (!empty($calendar_locale)) {
            $calendar = IntlCalendar::createInstance(null, $calendar_locale);
        }

        $this->dateObject = datefmt_create(
            $this->locale,
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            date_default_timezone_get(),
            $calendar,
            $converted_format
        );
        $output = $this->dateObject->format($timestamp);
        if ($output === false) {
            trigger_error(sprintf("Formatting error using '%s': %s (%d)", $converted_format, $this->dateObject->getErrorMessage(), $this->dateObject->getErrorCode()), E_USER_WARNING);
        }

        $additional_message = ($format === $converted_format) ? '' : ", with format converted to '$converted_format'";
        $this->debug("zcDate output for '$format' with timestamp ($timestamp)" . $additional_message . ": '" . json_encode($output) . "'");

        return $output;
    }

    /**
     * @param string $date  The date to be validated, according to the same rules as strtotime.
     *
     * @return bool  Indicates whether/not the supplied date is valid
     * @since ZC v2.0.0
     */
    public static function validateDate(string $date): bool
    {
        ['year' => $year, 'month' => $month, 'day' => $day, 'warning_count' => $warning_count, 'error_count' => $error_count] = date_parse($date);

        return ($year !== false && $month !== false && $day !== false && (($warning_count + $error_count) === 0));
    }

    /**
     * @since ZC v1.5.8
     */
    protected function debug(string $message): void
    {
        if ($this->debug === true) {
            error_log($message . \PHP_EOL);
        }
    }
}
