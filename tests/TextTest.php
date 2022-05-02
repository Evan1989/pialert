<?php

namespace EvanPiAlert\Test;

use EvanPiAlert\Util\Text;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once(__DIR__ . "/../src/autoload.php");

class TextTest extends TestCase {

    public function testLocalization(): void {
        Text::language(Text::RU);

        $reflectionClass = new ReflectionClass(Text::class);
        $ruTexts = array();
        $enTexts = array();
        try {
            $property = $reflectionClass->getProperty('texts');
            $property->setAccessible(true);
            $ruTexts = $property->getValue(Text::instance());
            Text::language(Text::EN);
            $enTexts = $property->getValue(Text::instance());
        } catch (Exception $e) {
            error_log('Error while get $texts from UText ' . $e->getMessage());
        }
        $this->assertNotEmpty($ruTexts, 'Не удалось загрузить русскую локализацию');
        $this->assertSameSize($ruTexts, $enTexts, 'Разное количество лексем в русской и английской локализации');

        foreach ($ruTexts as $key => $value) {
            $this->assertArrayHasKey($key, $enTexts, 'В английской локализации не хватает ' . $key);
            if (!is_array($value)) {
                $this->assertEquals(
                    mb_substr_count($value, Text::REPLACE_PATTERN),
                    mb_substr_count($enTexts[$key], Text::REPLACE_PATTERN),
                    'Разное количество параметров в русской и английской локализации');
            }
        }
    }
}