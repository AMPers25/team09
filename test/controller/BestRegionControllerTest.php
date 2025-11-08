<?php
// File: test/controller/BestRegionControllerTest.php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\BestRegionModel;
use App\Controller\BestRegionController; // Controller 클래스 참조

class BestRegionControllerTest extends TestCase
{
    // 테스트 전에 출력 버퍼를 시작합니다. (헤더 오류 방지)
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
     * [200 OK] 유효한 기간 요청에 대해 Top 5 랭킹 응답을 반환하는지 테스트
     */
    public function getRegionRankingAction_shouldReturn200Ok_onValidRequest()
    {
        $startDate = '2024-10-01';
        $endDate = '2024-10-31';
        $mockData = [
            ['region_code' => '48011', 'region_name' => '제주시', 'province' => null, 'avg_ti_score' => 93.55, 'rank' => 1]
        ];

        // 1. Model Mocking: 성공 데이터 반환 설정
        $modelMock = $this->createMock(BestRegionModel::class);
        $modelMock->expects($this->once())
            ->method('getBestRegionRanking')
            ->with($startDate, $endDate) // 날짜 파라미터가 정확히 전달되는지 검증
            ->willReturn($mockData);

        // 2. Controller Mocking (응답 헬퍼 함수 Mocking)
        $controllerMock = $this->getMockBuilder(\App\Controller\BestRegionController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)]) // Mock DB 커넥션 주입
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidDateFormat']) // 헬퍼 함수 및 날짜 유효성 검사 Mocking
            ->getMock();

        // 3. Controller 내부의 날짜 검증 함수가 true를 반환하도록 Mocking
        $controllerMock->method('isValidDateFormat')->willReturn(true);

        // 4. Controller의 sendResponse가 예상대로 (200 OK) 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->stringContains("Top 5 여행 적합 지역 랭킹 조회가 완료되었습니다."),
                $this->equalTo($mockData)
            );

        // 5. 메소드 실행
        $controllerMock->getRegionRankingAction(['start_date' => $startDate, 'end_date' => $endDate]);
    }

    /**
     * @test
     * [400 Bad Request] 필수 Query Parameter가 누락된 경우를 테스트
     */
    public function getRegionRankingAction_shouldReturn400_onMissingParameters()
    {
        // DB 호출은 없어야 함
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(\App\Controller\BestRegionController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // sendErrorResponse가 400 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("필수 데이터 (start_date, end_date)가 누락되었습니다.")
            );

        // 메소드 실행 (end_date 누락)
        $controllerMock->getRegionRankingAction(['start_date' => '2024-10-01']);
    }

    /**
     * @test
     * [500 Internal Server Error] Model에서 Exception 발생 시 오류를 처리하는지 테스트
     */
    public function getRegionRankingAction_shouldReturn500_onModelException()
    {
        // 1. DB Mock 설정: Model이 PDOException을 던지도록 유도
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willThrowException(new \PDOException("DB connection fail"));

        // 2. Controller Mocking
        $controllerMock = $this->getMockBuilder(\App\Controller\BestRegionController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidDateFormat'])
            ->getMock();

        $controllerMock->method('isValidDateFormat')->willReturn(true); // 날짜 형식 통과

        // 3. Controller의 sendErrorResponse가 500 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains("서버 내부 오류입니다.")
            );

        // 4. 메소드 실행
        $controllerMock->getRegionRankingAction(['start_date' => '2024-10-01', 'end_date' => '2024-10-31']);
    }

    /**
     * @test
     * [404 Not Found] Model이 빈 배열을 반환할 때 데이터를 찾을 수 없음을 반환하는지 테스트
     */
    public function getRegionRankingAction_shouldReturn404_onEmptyResult()
    {
        $mockData = []; // 빈 데이터

        // 1. DB Mock 설정: 빈 배열 반환을 유도
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // 2. Controller Mocking
        $controllerMock = $this->getMockBuilder(\App\Controller\BestRegionController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidDateFormat'])
            ->getMock();

        $controllerMock->method('isValidDateFormat')->willReturn(true);

        // 3. Controller의 sendErrorResponse가 404 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(404),
                $this->stringContains("해당 기간의 지역 분석 데이터가 없습니다.")
            );

        // 4. 메소드 실행
        $controllerMock->getRegionRankingAction(['start_date' => '2024-10-01', 'end_date' => '2024-10-31']);
    }
}