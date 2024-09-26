<?php

namespace EvanPiAlert\Util;

class TextAnalysisUtil {

    /**
     * Получить из текста ошибки основную смысловую часть
     * @param string $text
     * @return string
     */
    public static function getMainPartOfPiErrorText(string $text) : string {
        // Уберем message_id
        $text = self::replaceMessageIdToMask($text);
        // Уберем порты
        $text = self::replacePortNumberToMask($text);
        // Уберем номера документов
        $text = self::replaceDocumentNumberToMask($text);
        // Уберем название каналов
        $text = preg_replace('/Channel Name: [^ ]+/', '*', $text);
        // Уберем ip адреса, хосты
        $text = preg_replace('/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}/', '*', $text);
        $text = preg_replace('/[a-z\d\-.]+\.(net|com|ru|local)(:\d+)?/', '*', $text);
        $text = str_replace('**', '*', $text);
        // Возьмем конец текста, тк.к. в начале обычно название классов и Exception
        $result = static::getTextAfterLastColon($text);
        if ( $result && mb_strlen($result) < 15 ) {
            $result = static::getTextAfterLastColon($text, true);
        }
        // Удалим лишние символы на концах
        return preg_replace('/^[.*;: ]*?(.*?)[.*;:\' ]*?$/', '\\1', $result);
    }

    public static function replaceMessageIdToMask(string $text) : string {
        // Уберем message_id (4aece44e-0cb6-11ed-bddd-00000c31d092)
        return preg_replace('/[a-z\d]{8}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{4}-[a-z\d]{12}/', '*', $text);
    }
    public static function replacePortNumberToMask(string $text) : string {
        // port 10232
        return preg_replace('/port \d+/', 'port *', $text);
    }
    public static function replaceDocumentNumberToMask(string $text) : string {
        // 1409407
        return preg_replace('/\d{7,}/', '*', $text);
    }

    private static function getTextAfterLastColon(string $text, bool $skipLastColon = false) : string {
        $parts = explode(': ', $text);
        if ( $skipLastColon && count($parts) > 1) {
            $last = array_pop($parts);
            $prevLast = array_pop($parts);
            $parts[] = $prevLast.': '.$last;
        }
        return trim(array_pop($parts));
    }

    /**
     * Получить маску (с символом *), которой будут удовлетворять оба текста
     * @param string $text1
     * @param string $text2
     * @return ?string Возвращает null, если тексты совсем не похожие
     */
    public static function getMaskFromTexts(string $text1, string $text2) : ?string {
        if ( $text1 == $text2 ) {
            return $text1;
        }
        if ( empty($text1) || empty($text2) ) {
            return null;
        }
        $sameSymbolCount = similar_text($text1, $text2, $percent);
        if ($sameSymbolCount < 500 && $percent < 55) {
            return null;
        }
        // Если строки совпали на 500 символов или на 55%, то они похожи, надо пробовать найти маску
        return self::getMaskFromTextsInternalStep1($text1, $text2);
    }

    /**
     * Шаг первый, ищем общие подстроки в начале и в конце строк
     * @param string $text1
     * @param string $text2
     * @return string
     */
    protected static function getMaskFromTextsInternalStep1(string $text1, string $text2) : string {
        // Поиск общей части в начале строки
        $sameSymbolFromBegin = self::findSameSubstringLengthFromBegin($text1, $text2);
        if ( $sameSymbolFromBegin > 0 ) {
            $mask1 = mb_substr($text1, 0, $sameSymbolFromBegin);
            $text1 = mb_substr($text1, $sameSymbolFromBegin);
            $text2 = mb_substr($text2, $sameSymbolFromBegin);
        } else {
            $mask1 = '';
        }
        $sameSymbolFromEnd = self::findSameSubstringLengthFromEnd($text1, $text2);
        if ( $sameSymbolFromEnd > 0 ) {
            $length = mb_strlen($text1);
            $mask2 = mb_substr($text1, $length - $sameSymbolFromEnd, $sameSymbolFromEnd);
            $text1 = mb_substr($text1, 0, $length - $sameSymbolFromEnd);
            $text2 = mb_substr($text2, 0, mb_strlen($text2) - $sameSymbolFromEnd);
        } else {
            $mask2 = '';
        }
        return $mask1.self::getMaskFromTextsInternalStep2($text1, $text2).$mask2;
    }

    /**
     * Шаг второй, ищем общую подстроку посередине
     * @param string $text1
     * @param string $text2
     * @return string
     */
    protected static function getMaskFromTextsInternalStep2(string $text1, string $text2) : string {
        $text1_length = mb_strlen($text1);
        $text2_length = mb_strlen($text2);
        $text1_part = floor(3*$text1_length/4);
        $text2_part = floor(3*$text2_length/4);
        if ( $text1_length == 0 ||
             $text2_length == 0 ||
             similar_text($text1, $text2) < 0.5 * min($text1_length, $text2_length) ) {
            return '*';
        }
        $maxSameLength = 0;
        $maxSameText = '';
        $text1 = mb_str_split($text1);
        $text2 = mb_str_split($text2);
        for ($i1 = 1; $i1 < $text1_part; $i1++ ) {
            for ($i2 = 1; $i2 < $text2_part; $i2++) {
                if ( $text1[$i1] == $text2[$i2] ) {
                    $sameText = $text1[$i1];
                    // нашли общий кусок, определим его длину
                    for ($l = 1; $i1 + $l < $text1_length && $i2 + $l < $text2_length; $l++) {
                        if ( $text1[$i1+$l] == $text2[$i2+$l] ) {
                            $sameText .= $text1[$i1+$l];
                        } else {
                            break;
                        }
                    }
                    if (mb_strlen($sameText) > $maxSameLength) {
                        $maxSameLength = mb_strlen($sameText);
                        $maxSameText = $sameText;
                    }
                }
            }
        }
        return '*'.$maxSameText.'*';
    }

    protected static function findSameSubstringLengthFromBegin(string $text1, string $text2) : int {
        $text1 = mb_str_split($text1);
        $text2 = mb_str_split($text2);
        $max_length = min(count($text1), count($text2));
        for ($i = 0; $i < $max_length; $i++ ) {
            if ( $text1[$i] != $text2[$i] ) {
                return $i;
            }
        }
        return 0;
    }
    protected static function findSameSubstringLengthFromEnd(string $text1, string $text2) : int {
        $result = 0;
        $text1 = mb_str_split($text1);
        $text2 = mb_str_split($text2);
        $i1 = count($text1)-1;
        $i2 = count($text2)-1;
        for (; $i1 >= 0 && $i2 >= 0; $i1-- ) {
            if ( $text1[$i1] != $text2[$i2] ) {
                return $result;
            }
            $i2--;
            $result++;
        }
        return $result;
    }

    const REGEXP_META_SYMBOLS = array(
        '\\', '.', '|', '(', ')', '[', ']', '+', '+', '^', '$', '{', '}', '=', '\''
    );
    protected static array $regexpMetaSymbolsWithEscape = array();
    protected static function escapeMetaSymbols(string $string) : string {
        if ( empty(self::$regexpMetaSymbolsWithEscape) ) {
            foreach (self::REGEXP_META_SYMBOLS as $symbol) {
                self::$regexpMetaSymbolsWithEscape[] = '\\'.$symbol;
            }
        }
        return str_replace(self::REGEXP_META_SYMBOLS, self::$regexpMetaSymbolsWithEscape, $string);
    }

    /**
     * Сверка по маске, где * - группа любых символов, ? - строго один любой символ
     * @param string $text
     * @param string $mask
     * @return bool
     */
    public static function isTextFitToMask(string $text, string $mask) : bool {
        // Удалим символы регулярных выражений
        $newMask = self::escapeMetaSymbols($mask);
        // Поддержка ?
        $newMask = implode(').(', explode('?', $newMask));
        // Поддержка *
        $newMask = implode(').*(', explode('*', $newMask));
        // Поиск ведем на всю строку
        $newMask = '~^('.$newMask.')$~siu';
        return preg_match($newMask, $text);
    }

    /**
     * Схожи ли тексты, возвращает true при совпадении на 95% - 100% (целевой процент зависит от длины строк)
     * @param string $text1
     * @param string $text2
     * @return bool
     */
    public static function isSimilarText(string $text1, string $text2) : bool {
        if ( $text1 == $text2 ) { // точное совпадение
            return true;
        }
        // Если ошибки совпадают, но в них указан одинаковый xpath ссылающийся на разные повторения unbounded тега
        $text1 = preg_replace('/\[\d+]\//', '[]', $text1);
        $text2 = preg_replace('/\[\d+]\//', '[]', $text2);
        if ( $text1 == $text2 ) {
            return true;
        }
        return false;
        /*
         * Ранее было неточное сравнение текстов, но от него отказались
        $text1_length = mb_strlen($text1);
        $text2_length = mb_strlen($text1);
        // для длинных строк 97% (9 символов из 300 совпадает)
        if ( $text1_length > 255 || $text2_length > 255 ) {
            similar_text($text1, $text2, $percent);
            return $percent >= 97;
        }
        // для коротких строк пусть совпадают точно (более 95%)
        if ( $text1_length < 20) {
            return false;
        }
        $levenshtein = levenshtein($text1, $text2);
        // для средних строк от 90% до 98% (2 символа из 20-100)
        if ( $text1_length > 20 && $text1_length < 100 ) {
            return $levenshtein <= 2;
        }
        // для чуть более длинных от 95% до 98% (5 символов из 101-255)
        return $levenshtein <= 5;
        */
    }
}