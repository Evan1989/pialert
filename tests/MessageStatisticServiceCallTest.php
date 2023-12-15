<?php


namespace EvanPiAlert\Test;

use EvanPiAlert\Util\essence\PiSystem;
use EvanPiAlert\Util\HttpProvider;
use EvanPiAlert\Util\ManagePiSystem;
use EvanPiAlert\Util\MessageStatisticServiceCall;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/autoload.php");

class MessageStatisticServiceCallTest extends TestCase {

    public function testAll() {

        $msgStatServiceCall = new MessageStatisticServiceCall();
        $this->assertTrue($msgStatServiceCall->updateStatisticForAllSystem(), 'Вызов сервиса не выполнен');
        $end = date("Y-m-d H:00:00.0");
        $begin = date("Y-m-d H:00:0.0", strtotime($end) - 3600);
        $list = ManagePiSystem::getPiSystems();
        $firstSystem = array_shift($list);
        if ( $firstSystem->getStatisticEnable() ) {
            $this->assertEquals(200, $msgStatServiceCall->serviceCallPerSystem($firstSystem->getSystemName(), $firstSystem->getHost(), $begin, $end), 'Успешный вызов сервиса');
            $this->assertEquals(0, $msgStatServiceCall->serviceCallPerSystem($firstSystem->getSystemName(), 'unknownHostName', $begin, $end), 'Ошибка при вызове сервиса. Неизвестный адрес хоста');
        }
    }
}