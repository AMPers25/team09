<?php
// File: test/controller/BestPeriodControllerTest.php

// Composer Autoload를 로드하여 모든 클래스를 사용 가능하게 함
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\BestPeriodModel;
use App\Controller\BestPeriodController;

class BestPeriodControllerTest extends TestCase
{
    // Controller에서 sendResponse/sendErrorResponse가 호출되기 전에
    // 출력 버퍼를 시작하여 실제 HTTP 헤더 전송을 막습니다.
    protected function setUp(): void
    {
        if (!ob_get_level()) { // 버퍼가 시작되지 않은 경우에만 시작
            ob_start();
        }
    }

    protected function tearDown(): void
    {
        // 테스트 후 버퍼를 정리합니다.
        ob_end_clean();
    }

    /**
     * @test
     * 유효한 region_code 요청에 대해 200 OK 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn200Ok_onValidRequest()
    {
        $regionCode = 'BUSAN';
        $mockData = [
            ['start_date' => '2024-10-07', 'end_date' => '2024-10-13', 'avg_ti_score' => 93.55, 'rank' => 1]
        ];

        // 1. Model Mocking: 성공 데이터 반환하도록 설정
        $modelMock = $this->createMock(BestPeriodModel::class);
        $modelMock->expects($this->once())
            ->method('getBestWeekRanking')
            ->with($regionCode)
            ->willReturn($mockData);

        // 2. Controller Mocking: protected 메소드인 sendResponse/sendErrorResponse를 Mock하여
        //    실제 HTTP 헤더 전송을 막고, 호출 여부와 파라미터를 검증합니다.
        $controller = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([null]) // DB 커넥션은 무시
            ->onlyMethods(['__construct', 'getWeekRankingAction']) // Mocking하지 않을 메소드만 남기고 나머지는 Mock
            ->getMock();

        // Model Mock을 Controller의 의존성으로 대체 (Mocking을 위해 코드를 단순화)
        // Controller 내부에서 new BestPeriodModel을 사용하는 로직을 우회할 수 있도록 MockBuilder를 재정의합니다.

        // **Controller 로직이 Mock을 사용하도록 직접 Mocking합니다.**
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)]) // Mock PDO 커넥션 주입
            ->onlyMethods(['sendResponse', 'sendErrorResponse']) // <-- 헬퍼 함수 Mocking
            ->getMock();

        // 3. Controller의 sendResponse가 예상대로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->stringContains("Top 5 여행 적합 주간 추천이 완료되었습니다."),
                $this->equalTo($mockData)
            );

        // 4. 메소드 실행
        $controllerMock->getWeekRankingAction(['region_code' => $regionCode]);
    }

    /**
     * @test
     * region_code가 누락된 경우 400 Bad Request 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn400_onMissingRegionCode()
    {
        // 1. DB Mock (DB 호출이 발생하지 않음을 가정)
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        // 2. Controller Mocking: sendErrorResponse 호출만 검증
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // Controller의 sendErrorResponse가 400 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("필수 데이터 (region_code)가 누락되었습니다.")
            );

        // 3. 메소드 실행 (파라미터 누락)
        $controllerMock->getWeekRankingAction([]);
    }

    /**
     * @test
     * Model에서 Exception 발생 시 500 Internal Server Error 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn500_onModelException()
    {
        // 1. DB Mock 설정: Model이 PDOException을 던지도록 유도
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willThrowException(new \PDOException("DB connection fail"));

        // 2. Controller Mocking
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 3. Controller의 sendErrorResponse가 500 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains("서버 내부 오류입니다.")
            );

        // 4. 메소드 실행
        $controllerMock->getWeekRankingAction(['region_code' => 'BUSAN']);
    }

    /**
     * @test
     * Model이 빈 배열을 반환할 때 404 Not Found 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn404_onEmptyResult()
    {
        // 1. DB Mock 설정: 빈 배열 반환을 유도
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn([]); // 빈 데이터 반환하도록 설정

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // 2. Controller Mocking
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 3. Controller의 sendErrorResponse가 404 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(404),
                $this->stringContains("해당 지역의 2024년 주간 분석 데이터가 없습니다.")
            );

        // 4. 메소드 실행
        $controllerMock->getWeekRankingAction(['region_code' => 'BUSAN']);
    }
}