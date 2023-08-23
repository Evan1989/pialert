<?php


namespace EvanPiAlert\Test;

use EvanPiAlert\Util\essence\PiSystem;
use EvanPiAlert\Util\HttpProvider;
use EvanPiAlert\Util\ManagePiSystem;
use EvanPiAlert\Util\MessageStatisticServiceCall;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . "/../src/autoload.php");

class MessageStatisticServiceCallTest extends TestCase {
    /**
     * @throws \Exception
     */
    public function testAll()
    {

        $msgStatServiceCall = new MessageStatisticServiceCall();
        $this->assertTrue($msgStatServiceCall->serviceCall(), 'Вызов сервиса не выполнен');
        $this->assertNotEmpty($msgStatServiceCall->username, 'Не задан логин пользователя для вызова сервиса статистики');
        $this->assertNotEmpty($msgStatServiceCall->password, 'Не задан пароль для вызова сервиса статистики');
        $serviceCall = new HttpProvider();
        $end = date("Y-m-d H:00:00.0");
        $begin = date("Y-m-d H:00:0.0", strtotime($end) - 3600);
        $piSystems = new ManagePiSystem();
        $piSystemKey = array_key_first($piSystems->getPiSystems());
        $piSystemValue = $piSystems->getPiSystems()[$piSystemKey];
        if ($piSystemValue->getStatisticEnable() == 'true') {
            $this->assertEquals(200, $msgStatServiceCall->serviceCallPerSystem($serviceCall, $piSystemValue->getSystemName(), $piSystemValue->getHost(), $begin, $end), 'Успешный вызов сервиса');
            $this->assertEquals(0, $msgStatServiceCall->serviceCallPerSystem($serviceCall, $piSystemValue->getSystemName(), 'uknownHostName', $begin, $end), 'Ошибка при вызове сервиса. Неизвестный адрес хоста');
           $msgStatServiceCall->username = 'none';
            $this->assertEquals(400, $msgStatServiceCall->serviceCallPerSystem($serviceCall, $piSystemValue->getSystemName(), $piSystemValue->getHost(), $begin, $end), 'Ошибка авторизации при вызове сервиса');
        }
    }
}