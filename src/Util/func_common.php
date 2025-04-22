<?php

use EvanPiAlert\Util\Text;

////////////////////////////////////////
//           Общие константы          //
////////////////////////////////////////

const ONE_DAY = 24 * 3600;
const ONE_WEEK = 7 * ONE_DAY;
const ONE_MONTH = 30.5 * ONE_DAY;
const ONE_YEAR = 365 * ONE_DAY;

const GITHUB_PROJECT_LINK = "https://github.com/Evan1989/pialert";
const GITHUB_RAW_LINK = "https://raw.githubusercontent.com/Evan1989/pialert";

////////////////////////////////////////
//           Общие функции            //
////////////////////////////////////////
/**
 * @param $interval
 * @param string|null $code_filter Фильтр, например возвращать время только в часах
 * @param string|null $zero
 * @return string
 */
function getIntervalRoundLength($interval, ?string $code_filter = null, ?string $zero = null): string {
	$time_unit = array(
		'year' => array('border' => ONE_MONTH * 24, 'step' => ONE_MONTH * 12, 'names' => Text::yearsArray()),
		'month' => array('border' => ONE_MONTH * 2, 'step' => ONE_MONTH, 'names' => Text::monthsArray()),
		'day' => array('border' => ONE_DAY * 2, 'step' => ONE_DAY, 'names' => Text::daysArray()),
		'hour' => array('border' => 3600 * 2, 'step' => 3600, 'names' => Text::hoursArray()),
		'minute' => array('border' => 60 * 2, 'step' => 60, 'names' => Text::minutesArray()),
		'second' => array('border' => 1, 'step' => 1, 'names' => Text::secondsArray()),
	);
	foreach ($time_unit as $code => $info) {
		if (($interval >= $info['border'] && $code_filter == null) || $code_filter == $code) {
			$count = round($interval / $info['step']);

			$last_two = substr($count, strlen($count)-2);
			if ($last_two >= 10 && $last_two <= 19) {
				return $count." ".$info['names'][2];
			}
            return match (substr($count, strlen($count) - 1)) {
                '1' => $count." ".$info['names'][0],
                '2', '3', '4' => $count." ".$info['names'][1],
                default => $count." ".$info['names'][2],
            };
		}
	}
	return $zero??Text::immediately();
}

function round10($value): float {
    return round($value * 10) / 10;
}

/**
 * Аналог стандартного explode, но если в строке меньше параметров, чем надо, то заполняем их значением $default
 * @param string $separator
 * @param string $string
 * @param int $limit
 * @param null $default
 * @return array|false
 */
function explodeWithDefault(string $separator, string $string, int $limit, $default = null): array|false {
    return array_pad(explode($separator, $string, $limit), $limit, $default);
}

function replaceLinksWithATag(?string $text) : string {
    if ( $text ) {
        /** @noinspection HtmlUnknownTarget */
        return preg_replace('~(https?://[^\s,]+)~iu', '<a href="\\1" target="_blank">\\1</a>', $text);
    }
    return '';
}