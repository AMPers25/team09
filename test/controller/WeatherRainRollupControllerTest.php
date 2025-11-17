<?php
/**
 * File: test/controller/WeatherRainRollupControllerTest.php
 * Author: 황혜린
 * Description: 기능 2-3. 특정 지역/기간의 일별 강수량 + 월 합계(ROLLUP) 컨트롤러 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/WeatherRainRollupController.php';
require_once __DIR__ . '/../../src/model/WeatherRainRollupModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\WeatherRainRollupController;

class WeatherRainRollupControllerTest extends TestCase
{
    /**
     * @test
     * 정상 요청 시 200 OK와 데이터 배열 반환
     */
    public function getRainRollupAction_shouldReturn200_withData()
    {
        $query = [
            'region_code' => '01101',
            'from' => '2025-10-01',
            'to'   => '2025-10-31',
        ];

        $mockData = [
            ['level' => 'DAY',         'date_id' => '2025-10-01', 'ym' => '2025-10', 'rainfall_mm' => 3.2],
            ['level' => 'MONTH_TOTAL', 'date_id' => null,          'ym' => '2025-10', 'rainfall_mm' => 78.4],
        ];

        // PDO -> prepare -> statement -> fetchAll 체인 목킹
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('bindValue')->willReturn(true);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // Controller 의 sendResponse / sendErrorResponse 를 목킹
        $controllerMock = $this->getMockBuilder(WeatherRainRollupController::class)
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

        $controllerMock->getRainRollupAction($query);
    }

    /**
     * @test
     * 필수 파라미터 누락 시 400
     */
    public function getRainRollupAction_shouldReturn400_onMissingParams()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(WeatherRainRollupController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains('필수 데이터(region_code, from, to)가 누락되었습니다.')
            );

        // 빈 파라미터로 호출
        $controllerMock->getRainRollupAction([]);
    }

    /**
     * @test
     * 날짜 형식이 잘못되면 400
     */
    public function getRainRollupAction_shouldReturn400_onInvalidDateFormat()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(WeatherRainRollupController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains('날짜 형식이 올바르지 않습니다. (YYYY-MM-DD)')
            );

        $controllerMock->getRainRollupAction([
            'region_code' => '01101',
            'from' => '2025-10-40', // invalid day
            'to'   => '2025-10-31',
        ]);
    }

    /**
     * @test
     * Model/DB 예외 발생 시 500
     */
    public function getRainRollupAction_shouldReturn500_onModelException()
    {
        $query = [
            'region_code' => '01101',
            'from' => '2025-10-01',
            'to'   => '2025-10-31',
        ];

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willThrowException(new \PDOException('Simulated DB error'));

        $controllerMock = $this->getMockBuilder(WeatherRainRollupController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains('강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.')
            );

        $controllerMock->getRainRollupAction($query);
    }
}
