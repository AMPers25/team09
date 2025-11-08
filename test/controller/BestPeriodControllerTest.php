<?php
/**
 * File: src/model/BestPeriodControllerTest.php
 * Author: 김연수
 * Description: 기능 3-2. 여행 기간 추천 컨트롤러 테스트
 * Last Updated: 2025-11-08
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/BestPeriodController.php';
require_once __DIR__ . '/../../src/model/BestPeriodModel.php';

use PHPUnit\Framework\TestCase;
use App\Model\BestPeriodModel;
use App\Controller\BestPeriodController;

class BestPeriodControllerTest extends TestCase
{
    // 테스트 전에 출력 버퍼를 시작합니다.
    protected function setUp(): void
    {
        if (!ob_get_level()) {
            ob_start();
        }
    }

    // 테스트 후에 출력 버퍼를 정리합니다.
    protected function tearDown(): void
    {
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

        // 1. PDO/Statement Mocking: 성공 데이터 반환 설정
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // 2. Controller Mocking (sendResponse 헬퍼 함수를 Mocking)
        // Controller가 Model을 생성하고 DB Mock을 전달하는 흐름을 따릅니다.
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$dbMock]) // Mock DB 커넥션 주입
            ->onlyMethods(['sendResponse', 'sendErrorResponse']) // 헬퍼 함수 Mocking
            ->getMock();

        // 3. Controller의 sendResponse가 예상대로 (200 OK) 호출되는지 검증
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
        // 1. DB Mock 설정: Model 호출 방지 검증을 위해 DB 호출이 없어야 함
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        // 2. Controller Mocking: sendErrorResponse 호출만 검증
        $controllerMock = $this->getMockBuilder(\App\Controller\BestPeriodController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // 3. Controller의 sendErrorResponse가 400 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("필수 데이터 (region_code)가 누락되었습니다.")
            );

        // 4. 메소드 실행 (파라미터 누락)
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
        // prepare() 호출 시 DB 오류를 강제 발생시켜 Model 내부에서 Exception이 던져지게 함
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
        $mockData = []; // 빈 데이터 (Model의 return 값)

        // 1. DB Mock 설정: 빈 배열 반환을 유도
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($mockData); // 빈 데이터 반환하도록 설정

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