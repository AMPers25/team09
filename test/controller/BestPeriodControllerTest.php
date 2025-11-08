<?php
// File: test/controller/BestPeriodControllerTest.php
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

// Controller 테스트를 위해 Model과 Controller 클래스 모두 사용
use App\Model\BestPeriodModel;
// Controller 파일은 require_once로 직접 포함하거나, Router에서 포함된다고 가정
// 현재 환경에서는 Autoload로 로드되지 않을 수 있으므로, 테스트 클래스 정의 전에 Controller 코드를 포함해야 할 수 있습니다.

class BestPeriodControllerTest extends TestCase
{
    // 테스트 전에 출력 버퍼를 시작합니다.
    protected function setUp(): void
    {
        // 출력 버퍼를 시작하여 Controller의 echo 출력을 캡처합니다.
        ob_start();
    }

    // 테스트 후에 출력 버퍼를 정리합니다.
    protected function tearDown(): void
    {
        // 출력 버퍼를 정리합니다.
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

        // 2. Controller 인스턴스 생성 (DB는 필요 없으므로 null 전달)
        // Controller 내에서 Model을 인스턴스화하는 경우, 이를 위해 Controller를 Mocking하거나 DI 컨테이너를 사용해야 하지만,
        // 여기서는 Model Mock을 주입하는 방식으로 Controller를 테스트한다고 가정합니다.
        // 실제 Controller 생성자에 Model을 주입할 수 있도록 코드를 수정하거나,
        // Controller가 Model을 내부에서 생성하는 경우를 위해 DB Mock을 전달하고 Model 생성 시 Mock이 사용되도록 설정해야 합니다.

        // *가정: Controller가 DB 커넥션을 받아 Model을 생성합니다.
        // Model 생성 시 Mock이 반환되도록 하기 위해 DB Mock을 전달합니다. (Advanced Mocking 필요)

        // 단순화를 위해, Model Mock을 직접 주입할 수 있는 구조라고 가정하고 Controller를 Mocking합니다.
        $controller = $this->getMockBuilder(BestPeriodController::class)
            ->setConstructorArgs([null]) // DB 커넥션은 무시 (Mock Model 사용할 것이므로)
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        // Model Mock을 Controller 내부에 주입 (실제 코드가 DI 패턴을 사용한다고 가정)
        // Controller 내부에서 Model을 인스턴스화 하는 경우, 이를 Mock으로 대체하는 방식이 필요합니다.
        // 여기서는 Model Mock을 반환하는 헬퍼 메소드를 Controller에 추가하여 Mocking하는 방법을 사용합니다.

        // Model이 아닌, Controller의 최종 응답 메소드인 sendResponse가 예상대로 호출되는지 검증합니다.
        $controller->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->stringContains("Top 5 여행 적합 주간 추천이 완료되었습니다."),
                $this->equalTo($mockData)
            );

        // Model Mock을 Controller에서 사용하도록 Controller 내부 구조를 수정합니다.
        // 현재 Controller 코드를 수정하지 않는 가정하에, Mock Model을 사용하기 위해 복잡한 Mocking이 필요합니다.

        // **간단화된 테스트:** Controller의 실제 로직 실행 대신, 파라미터 검증과 Model 호출만 확인

        // 임시 Model Mock을 사용하여 테스트 코드를 완성합니다.
        $dbMock = $this->createMock(\PDO::class);
        $controller = new BestPeriodController($dbMock);

        // Controller가 내부적으로 Model을 생성할 때 Mock Model을 사용하도록 `getMockedModel`과 같은 private 메소드를
        // Controller에 추가하고 이를 Mocking하는 방법이 가장 깔끔합니다.

        // 현재는 Controller의 `getWeekRankingAction`을 직접 실행하고 출력을 캡처하는 방식으로 진행하겠습니다.

        // Model의 Mock을 Controller에 주입하기 위해 Model 생성 부분을 재정의 (실제 환경에서 어려움)
        // -> **Model이 DB 커넥션을 받아 작동하므로, DB 커넥션을 Mock하고 Model은 실제 클래스를 사용합니다.**

        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        // Controller가 Model을 생성하고 DB Mock을 전달하는 흐름을 따릅니다.
        $controller = new BestPeriodController($dbMock);

        // 3. 메소드 실행
        $controller->getWeekRankingAction(['region_code' => $regionCode]);

        // 4. 응답 검증 (출력 버퍼 캡처)
        $output = ob_get_contents();
        $response = json_decode($output, true);

        $this->assertEquals(200, $response['status']);
        $this->assertEquals("Top 5 여행 적합 주간 추천이 완료되었습니다.", $response['message']);
        $this->assertEquals($mockData, $response['data']);
    }

    /**
     * @test
     * region_code가 누락된 경우 400 Bad Request 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn400_onMissingRegionCode()
    {
        $dbMock = $this->createMock(\PDO::class);
        $controller = new BestPeriodController($dbMock);

        // DB Mock의 prepare가 호출되지 않아야 합니다. (Model 호출 전 검증)
        $dbMock->expects($this->never())->method('prepare');

        // 메소드 실행 (파라미터 누락)
        $controller->getWeekRankingAction([]);

        // 응답 검증
        $output = ob_get_contents();
        $response = json_decode($output, true);

        $this->assertEquals(400, $response['status']);
        $this->assertEquals("필수 데이터 (region_code)가 누락되었습니다.", $response['message']);
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

        $controller = new BestPeriodController($dbMock);

        // 2. 메소드 실행
        $controller->getWeekRankingAction(['region_code' => 'BUSAN']);

        // 3. 응답 검증
        $output = ob_get_contents();
        $response = json_decode($output, true);

        $this->assertEquals(500, $response['status']);
        $this->assertEquals("서버 내부 오류입니다.", $response['message']);
    }

    /**
     * @test
     * Model이 빈 배열을 반환할 때 404 Not Found 응답을 반환하는지 테스트
     */
    public function getWeekRankingAction_shouldReturn404_onEmptyResult()
    {
        $mockData = []; // 빈 데이터

        // 1. DB Mock 설정: 빈 배열 반환
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')->willReturn($mockData);

        $dbMock = $this->createMock(\PDO::class);
        $dbMock->method('prepare')->willReturn($stmtMock);

        $controller = new BestPeriodController($dbMock);

        // 2. 메소드 실행
        $controller->getWeekRankingAction(['region_code' => 'BUSAN']);

        // 3. 응답 검증
        $output = ob_get_contents();
        $response = json_decode($output, true);

        $this->assertEquals(404, $response['status']);
        $this->assertEquals("해당 지역의 2024년 주간 분석 데이터가 없습니다.", $response['message']);
    }
}