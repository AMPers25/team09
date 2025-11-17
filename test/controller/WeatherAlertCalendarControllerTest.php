<?php
/**
 * File: test/controller/WeatherAlertCalendarControllerTest.php
 * Author: 황혜린
 * Description: 기능 2-4. 특정 월의 지역별 기상 특보 목록(Controller) 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/WeatherAlertCalendarController.php';
require_once __DIR__ . '/../../src/model/WeatherAlertCalendarModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\WeatherAlertCalendarController;

class WeatherAlertCalendarControllerTest extends TestCase
{
    /**
     * @test
     * 정상 요청 시 200 OK와 데이터 배열 반환
     */
    public function getMonthlyAlertsAction_shouldReturn200_withData()
    {
        $query = ['region_code' => '01101', 'year' => '2025', 'month' => '10'];

        $mockData = [
            ['alert_id' => 1001, 'date_id' => '2025-10-03', 'alert_time' => '14:00:00', 'alert_type' => '폭우'],
            ['alert_id' => 1002, 'date_id' => '2025-10-16', 'alert_time' => '11:00:00', 'alert_type' => '폭설'],
        ];

        // PDO -> prepare -> statement -> fetchAll 체인 목킹
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // sendResponse/sendErrorResponse 를 Mock 하므로 실제 echo/headers 없음 → 버퍼 불필요
        $controllerMock = $this->getMockBuilder(WeatherAlertCalendarController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->equalTo('OK'),
                $this->equalTo($mockData)
            );

        $controllerMock->expects($this->never())->method('sendErrorResponse');

        $controllerMock->getMonthlyAlertsAction($query);
    }

    /**
     * @test
     * 필수 파라미터 누락 시 400
     */
    public function getMonthlyAlertsAction_shouldReturn400_onMissingParams()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(WeatherAlertCalendarController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains('필수 데이터(region_code, year, month)가 누락되었습니다.')
            );

        $controllerMock->getMonthlyAlertsAction([]); // 비어있는 파라미터
    }

    /**
     * @test
     * 파라미터 형식 잘못되면 400
     */
    public function getMonthlyAlertsAction_shouldReturn400_onInvalidFormats()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(WeatherAlertCalendarController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains('region_code 형식이 올바르지 않습니다')
            );

        $controllerMock->getMonthlyAlertsAction(['region_code'=>'1101','year'=>'2025','month'=>'10']); // region_code 4자리
    }

    /**
     * @test
     * Model/DB 예외 발생 시 500
     */
    public function getMonthlyAlertsAction_shouldReturn500_onModelException()
    {
        $query = ['region_code' => '01101', 'year' => '2025', 'month' => '10'];

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willThrowException(new \PDOException('Simulated DB error'));

        $controllerMock = $this->getMockBuilder(WeatherAlertCalendarController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains('기상 특보 조회 중 서버 내부 오류가 발생했습니다.')
            );

        $controllerMock->getMonthlyAlertsAction($query);
    }
}
