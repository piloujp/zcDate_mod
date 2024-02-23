<?php
/**
 * @copyright Copyright 2003-2024 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Pilou2-PilouJP 2024 Feb 14 Modified in v2.0.0-alpha1 $
 */
class zcDate extends base
{
    protected
        $useIntlDate = false,
        $useStrftime = false,
        $locale,                //- Only used when $this->useIntlDate is true
        $strftime2date,         //- Only used when $this->useStrftime is false
        $strftime2intl,         //- Only used when $this->useStrftime is false
        $debug = false,
        $dateObject;

    // -----
    // Initial construction; initializes the conversion arrays and determines which PHP
    // base function will be used by the output method.
    //
    // The $zen_date_debug is a "soft" configuration setting that can be forced (defaults to false)
    // via the site's /includes/extra_datafiles/site_specific_overrides.php
    //
    public function __construct()
    {
        global $zen_date_debug;

        if (isset($zen_date_debug) && $zen_date_debug === true) {
            $this->debug = true;
        }

        if (version_compare(phpversion(), '8.1', '<')) {
            $this->useStrftime = true;
        } else {
            if (function_exists('datefmt_create')) {
                $this->useIntlDate = true;
            }
//            $this->initializeConversionArrays();
        }
        $this->debug('zcDate construction: ' . PHP_EOL . var_export($this, true));
    }

    // -----
    // Initializes the class-based arrays that define the format conversions
    // from their strftime format (the input requirement) and the formats used
    // by either the 'date' function or the IntlDateFormatter class.
    //
    // Each array's keys start out as the strftime format and a key's value is the converted format.
    // These arrays are then converted into a 'from' and a 'to' array that's used by the
    // method convertFormat's processing (essentially a str_replace on the submitted format string).
    //
    protected function initializeConversionArrays()
    {
        if ($this->useIntlDate === true) {
            // -----
            // First, save the current locale; it's set by the main language file's (presumed) call to the
            // setlocale function.
            //
            $this->locale = setlocale(LC_TIME, 0);

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
                '%m' => 'MM',
                '%M' => 'mm',
                '%S' => 'ss',
                '%T' => 'HH:mm:ss',
                '%x' => $date_short,
                '%X' => $time_short,
                '%y' => 'yy',
                '%Y' => 'y',
				'%z' => 'ZZZZ',
				'%Z' => 'ZZZZ',
            ];
            $this->strftime2intl = [
                'from' => array_keys($strftime2intl),
                'to' => array_values($strftime2intl)
            ];
        } else {
			$strftime2date = [
				'%a' => 'D',
				'%A' => 'l',
				'%b' => 'M',
				'%B' => 'F',
				'%d' => 'd',
				'%H' => 'H',
				'%m' => 'm',
				'%M' => 'i',
				'%S' => 's',
				'%T' => 'H:i:s',
				'%x' => defined('DATE_FORMAT') ? DATE_FORMAT : 'm/d/Y',
				'%X' => 'H:i:s',
				'%y' => 'y',
				'%Y' => 'Y',
				'%z' => 'ZZZZ',
				'%Z' => 'ZZZZ',
			];
			$this->strftime2date = [
				'from' => array_keys($strftime2date),
				'to' => array_values($strftime2date)
			];
		}
    }

    // -----
    // A couple of public functions to control whether or not the class' debug
    // processing is to be enabled or disabled.
    //
    public function enableDebug()
    {
        $this->debug = true;
        $this->debug('Debug enabled: ' . PHP_EOL . var_export($this, true));
    }
    public function disableDebug()
    {
        $this->debug = false;
    }

    /**
     * @param string $format  output method should start with a intlDate-format string
     * @param int    $timestamp
     *
     * @return false|string
     */
    public function output(string $format, int $timestamp = 0)
    {
        $converted_format = '';
		
		if ($timestamp === 0) {
            $timestamp = time();
        }
		
		if (preg_match('/%\w/', $format) == 1) { // Test if strftime parameters are used.
			$this->useStrftime = true;
			$this->initializeConversionArrays();
		}
		
		if ($this->useIntlDate === true) { // Check for presence of PHP extension 'intl'.
			$this->locale = setlocale(LC_TIME, 0);
			if ($this->useStrftime === false) {
				$converted_format = $format;
			} else {
				$converted_format = str_replace($this->strftime2intl['from'], $this->strftime2intl['to'], $format);
			}
			$this->dateObject = datefmt_create(
                $this->locale,
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                date_default_timezone_get(),
                IntlDateFormatter::GREGORIAN,
                $converted_format
            );
            $output = $this->dateObject->format($timestamp);
            if ($output === false) {
                trigger_error(sprintf("Formatting error using '%s': %s (%d)", $format, $this->dateObject->getErrorMessage(), $this->dateObject->getErrorCode()), E_USER_WARNING);
            }
		} else { // Uses Date() when 'intl' extension is not compiled/activated in PHP.
			if ($this->useStrftime === false) {
				$converted_format = $this->convertFormat($format);
			} else {
				$converted_format = str_replace($this->strftime2date['from'], $this->strftime2date['to'], $format);
			}
			$output = date($converted_format, $timestamp);
		}

        $additional_message = ($format === $converted_format) ? '' : ", with format converted to '$converted_format'";
        $this->debug("zcDate output for '$format' with timestamp ($timestamp): '" . json_encode($output) . "'");

        return $output;
    }

    // -----
    // Convert intlDate format to Date function format. Convertion is made in two steps to avoid reconverting already
	// converted strings. These two dates formats are relatively closed and same characters are used in both which could
	// leasd to wrong/multiple conversions during the recursive process.
	// First convertion convert 'safe' part to Date function format and 'mixed' part to neutral unused characters that
	// won't be converted again during the multiple steps process.
    // Second array's keys start out as pre-converted format and a key's value is the converted format.
    // This array is then finaly converted into a 'from' and a 'to' array that's used by a str_replace on the submitted
	// (partly converted) format string.
    //
    protected function convertFormat(string $format)
    {
		if (preg_match_all('/\'[^\']*\'/', $format, $multichaine, PREG_OFFSET_CAPTURE)) { // check for string inside single quotes which should not be converted
			$conststring = @[]; // array to keep escaped strings until the end
			$convstring = @[]; // strings parts that should be converted
			$j = 0;
			foreach ($multichaine[0] as $constchaine) {
				$conststring[$j] = $constchaine[0]; // populate array for constant strings
				$j++;
			}
			($multichaine[0][0][1] == 0) ? $first = 1 : $first = 0; // test is a constant string or a string to convert comes first for final re-assembly
			$multistring = preg_split('/\'[^\']*\'/', $format); // split strings to convert
			$j = 0;
			foreach ($multistring as $chaine) {
				$convstring[$j] = $this->replace_format($chaine); // and populate array with these strings converted
				$j++;
			}
			if ($first === 0) { // begin to rebuilt final string
				$result = $convstring[0];
			} else {
				$result = '';
			}
			for ($i=0;$i<count($conststring);$i++) {
				$result .= $conststring[$i] . $convstring[$i+1]; // merge constant and converted strings
			}
			return $result;
		} else {
			return $this->replace_format($format); // if no constannt string
		}
    }

	protected function replace_format(string $chaine)
	{
		$intl2date = [ // Intermedaite codes have been randomely generated from characters list excluding those used as parameter for IntlDate object.
			'EEEE' => 'l',
			'E' => 'c6wv',
			'MMMM' => 'F',
			'MMM' => 'QQxA',
			'MM' => 'JNtf',
			'M' => 'n',
			'w' => 'W',
			'dd' => 'TVX9',
			'd' => 'j',
			'D' => 'KVA9',
			'hh' => 'cUu0',
			'h' => 'g',
			'HH' => 'cx@T',
			'H' => 'G',
			'mm' => 'i',
			'm' => 'i',
			'ss' => 's',
			'yyyy' => 'q0qB',
			'yyy' => 'q0qB',
			'yy' => 'R#P#',
			'y' => 'q0qB',
			'Y' => 'o',
			'zzzz' => 'e',
			'ZZZZ' => 'P',
		];

		$inter2date = [
			'c6wv' => 'D',
			'JNtf' => 'm',
			'QQxA' => 'M',
			'TVX9' => 'd',
			'KVA9' => 'z',
			'cUu0' => 'h',
			'cx@T' => 'H',
			'q0qB' => 'Y',
			'R#P#' => 'y',
		];

		$inter2date = [
			'from' => array_keys($inter2date),
			'to' => array_values($inter2date)
		];

		$uniq_pat = @[]; // Array to keep track of unique patterns to convert even if they have multiple occurrence.

		foreach ($intl2date as $letpat => $letconv) {
			$firstlet = substr($letpat, 0, 1); // Retrieve first letter of each so regular expression can identify all possible paterns using this letter code.
			if (in_array($firstlet, $uniq_pat, true) == false) { // Only one time for each letter code.
				$uniq_pat[] .= $firstlet;
				$pregpat = '/(?<!' . $firstlet . ')' . $firstlet . '+(?!' . $firstlet . ')/';
				preg_match_all($pregpat, $chaine, $matches, PREG_SET_ORDER);
				$finded = @[];
				$i =0;
				if (!empty($matches)) {
					foreach ($matches as $val) { // Go through all occurrences/patterns of a letter code
							if ((in_array($val[0], $finded, true) == false) AND !empty($intl2date[$val[0]])) { // and if they have an equivalent in Date function format,
								$finded[$i] = $val[0]; // keeping track of already treated patterns
								$pattern = '/(?<!' . $firstlet . ')' . $val[0] . '(?!' . $firstlet . ')/';
								$result = preg_replace($pattern, $intl2date[$val[0]],$chaine);             // replace them
								$chaine = $result;
							}
							$i++;
					}
				}
			}
		}
		// Final conversion and result output. This method can be used because all inter2date keys are uniques and not contained in other keys. This is not the case with intl2date.
		$final_format = str_replace($inter2date['from'], $inter2date['to'], $chaine);
		return $final_format;
	}

    protected function debug(string $message)
    {
        if ($this->debug === true) {
            error_log($message . PHP_EOL);
        }
    }
}
