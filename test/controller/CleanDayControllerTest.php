<?php
// File: test/controller/CleanDayControllerTest.php
// 기능 3-1: PM10 클린 기간 추천 Controller 테스트

require_once __DIR__ . '/../../vendor/autoload.php';
// Controller와 Model 파일을 수동으로 로드 (Autoload 실패 방지용)
require_once __DIR__ . '/../../src/controller/CleanDayController.php';
require_once __DIR__ . '/../../src/model/CleanDayModel.php';

use PHPUnit\Framework\TestCase;
use App\Model\CleanDayModel;
use App\Controller\CleanDayController;

class CleanDayControllerTest extends TestCase
{
    // 테스트 전에 출력 버퍼를 시작하고, 후에는 정리합니다.
    protected function setUp(): void
    {
        if (!ob_get_level()) {
            ob_start();
        }
        // 각 테스트 시작 시 $_GET 변수를 초기화합니다.
        $_GET = [];
    }

    protected function tearDown(): void
    {
        ob_end_clean();
    }

    /**
     * Helper: Model Mock을 Controller에 주입하고 테스트를 실행할 Mock Controller 객체를 생성합니다.
     * Model의 반환 타입 오류를 막기 위해 $modelReturn은 항상 배열 타입으로 처리합니다.
     */
    private function getControllerMockForTest(array $modelReturn = [])
    {
        // 1. Model Mocking: Model의 비즈니스 메소드 Mock
        $modelMock = $this->createMock(CleanDayModel::class);
        $modelMock->method('getCleanStreakRanking')->willReturn($modelReturn);

        // 2. Controller Mocking: getModel() 헬퍼 함수와 응답 헬퍼 함수만 Mocking
        $controllerMock = $this->getMockBuilder(\App\Controller\CleanDayController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendJsonResponse', 'sendErrorResponse', 'getModel'])
            ->getMock();

        // 3. getModel() 호출 시 Model Mock을 반환하도록 설정 (의존성 주입 대체)
        $controllerMock->method('getModel')->willReturn($modelMock);

        return $controllerMock;
    }

    /**
     * @test
     * [200 OK] 유효한 region_code 요청에 대해 Top 5 클린 기간을 반환하는지 테스트
     */
    public function getCleanStreakRankingAction_shouldReturn200Ok_onValidRequest()
    {
        // $_GET 변수 설정
        $_GET['region_code'] = 'SEOUL';
        $regionCode = 'SEOUL';

        $mockData = [
            ['start_date' => '2024-03-10', 'end_date' => '2024-03-20', 'streak_days' => 11, 'pm10_avg' => 25.5, 'rank' => 1]
        ];

        // Controller Mock 생성 및 데이터 Mock 주입
        $controllerMock = $this->getControllerMockForTest($mockData);

        // sendJsonResponse가 예상대로 (200 OK) 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendJsonResponse')
            ->with(
                $this->callback(function($body) use ($mockData) {
                    return $body['status'] === 200 && $body['data'] === $mockData;
                }),
                $this->equalTo(200)
            );

        // 메소드 실행
        $controllerMock->getCleanStreakRankingAction();
    }

    /**
     * @test
     * [400 Bad Request] 필수 region_code 파라미터가 누락된 경우를 테스트
     */
    public function getCleanStreakRankingAction_shouldReturn400_onMissingRegionCode()
    {
        // $_GET 변수를 null로 설정하여 누락 상태를 만듭니다. (setUp에서 이미 초기화됨)

        // DB 호출은 없어야 함
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->never())->method('prepare');

        $controllerMock = $this->getMockBuilder(\App\Controller\CleanDayController::class)
            ->setConstructorArgs([$dbMock])
            ->onlyMethods(['sendJsonResponse', 'sendErrorResponse', 'getModel'])
            ->getMock();

        // sendErrorResponse가 400 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->stringContains("필수 데이터 (region_code)가 누락되었습니다.")
            );

        // 메소드 실행 (파라미터 누락)
        $controllerMock->getCleanStreakRankingAction();
    }

    /**
     * @test
     * [500 Internal Server Error] Model에서 Exception 발생 시 오류를 처리하는지 테스트
     */
    public function getCleanStreakRankingAction_shouldReturn500_onModelException()
    {
        $_GET['region_code'] = 'BUSAN';

        // 1. **Model Mocking (인라인):** Exception을 던지도록 설정
        $modelMock = $this->createMock(CleanDayModel::class);
        $modelMock->expects($this->once()) // 메소드가 한 번 호출됨을 기대
        ->method('getCleanStreakRanking')
            ->willThrowException(new \Exception("PM10 클린 기간 분석 중 서버 내부 오류가 발생했습니다."));

        // 2. Controller Mocking (헬퍼 함수와 getModel을 Mocking)
        $controllerMock = $this->getMockBuilder(\App\Controller\CleanDayController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendJsonResponse', 'sendErrorResponse', 'getModel'])
            ->getMock();

        // 3. getModel() 호출 시 Model Mock을 반환하도록 설정
        $controllerMock->method('getModel')->willReturn($modelMock); // <-- 이 부분이 Model Mock을 주입합니다.

        // 4. Controller의 sendErrorResponse가 500 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->stringContains("서버 내부 오류입니다.")
            );

        // 5. 메소드 실행
        $controllerMock->getCleanStreakRankingAction(['region_code' => 'BUSAN']); // <-- 쿼리 파라미터 대신 $_GET 사용
    }

    /**
     * @test
     * [404 Not Found] Model이 빈 배열을 반환할 때 데이터를 찾을 수 없음을 반환하는지 테스트
     */
    public function getCleanStreakRankingAction_shouldReturn404_onEmptyResult()
    {
        // $_GET 변수 설정
        $_GET['region_code'] = 'BUSAN';

        // Controller Mock 생성 및 빈 데이터 Mock 주입
        $controllerMock = $this->getControllerMockForTest([]); // ModelReturn: 빈 배열

        // Controller의 sendErrorResponse가 404 상태로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(404),
                $this->stringContains("해당 지역의 2024년 주간 분석 데이터가 없습니다.")
            );

        // 메소드 실행
        $controllerMock->getCleanStreakRankingAction();
    }
}