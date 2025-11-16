<?php
/**
 * File: test/controller/TemperatureCalendarControllerTest.php
 * Author: 김연수 (sooooscode)
 * Description: 기능 2-2. 기온 캘린더 조회 Controller Test
 * Last Updated: 2025-11-16
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/TemperatureCalendarController.php';
require_once __DIR__ . '/../../src/model/TemperatureCalendarModel.php';

use PHPUnit\Framework\TestCase;
use App\Controller\TemperatureCalendarController;
use App\Model\TemperatureCalendarModel;

class TemperatureCalendarControllerTest extends TestCase
{
    private TemperatureCalendarModel $modelMock;
    private TemperatureCalendarController $controller;

    protected function setUp(): void
    {
        // Model Mocking: 실제 DB 접근을 막고 가짜 데이터를 반환하도록 설정
        $this->modelMock = $this->createMock(TemperatureCalendarModel::class);

        // Controller 인스턴스 생성 및 Mock Model 주입
        $this->controller = new TemperatureCalendarController($this->modelMock);
    }

    /**
     * @test
     * 유효한 파라미터로 데이터를 성공적으로 조회하고 200 응답을 반환하는지 테스트
     */
    public function getDailyCalendar_shouldReturn200AndDataOnSuccess()
    {
        $params = [
            'regionCode' => '11000',
            'year' => '2025',
            'month' => '10'
        ];
        $expectedData = [
            ['date_id' => '2025-10-01', 'avg_temp' => 18.4],
            ['date_id' => '2025-10-02', 'avg_temp' => 19.0],
        ];

        // 1. Model Mock 설정: getDailyAverageTemperature 호출을 예상하고 데이터를 반환하도록 설정
        $this->modelMock->expects($this->once())
            ->method('getDailyAverageTemperature')
            ->with($params['regionCode'], (int)$params['year'], (int)$params['month'])
            ->willReturn($expectedData);

        // 2. 출력 버퍼링 시작 및 컨트롤러 메소드 실행
        ob_start();
        $this->controller->getDailyCalendar($params);
        $output = ob_get_clean();

        // 3. 응답 검증
        $responseData = json_decode($output, true);

        $this->assertIsArray($responseData);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('일일 평균 기온 데이터를 성공적으로 조회했습니다.', $responseData['message']);
        $this->assertEquals($expectedData, $responseData['data']);
    }

    /**
     * @test
     * 필수 파라미터 (regionCode) 누락 시 400 응답을 반환하는지 테스트
     */
    public function getDailyCalendar_shouldReturn400OnMissingRegionCode()
    {
        $params = [
            'year' => '2025',
            'month' => '10'
        ];

        // Model은 호출되지 않아야 함
        $this->modelMock->expects($this->never())
            ->method('getDailyAverageTemperature');

        // 출력 버퍼링 시작
        ob_start();
        $this->controller->getDailyCalendar($params);
        $output = ob_get_clean();

        // 응답 검증
        $responseData = json_decode($output, true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertStringContainsString('잘못된 요청입니다. regionCode, year, month 파라미터를 확인해주세요.', $responseData['message']);
    }

    /**
     * @test
     * 유효하지 않은 월(month) 범위 (1~12) 입력 시 400 응답을 반환하는지 테스트
     */
    public function getDailyCalendar_shouldReturn400OnInvalidMonthRange()
    {
        $params = [
            'regionCode' => '11000',
            'year' => '2025',
            'month' => '13' // 유효하지 않은 월
        ];

        // Model은 호출되지 않아야 함
        $this->modelMock->expects($this->never())
            ->method('getDailyAverageTemperature');

        // 출력 버퍼링 시작
        ob_start();
        $this->controller->getDailyCalendar($params);
        $output = ob_get_clean();

        // 응답 검증
        $responseData = json_decode($output, true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertStringContainsString('월(month)은 1부터 12 사이의 값', $responseData['message']);
    }

    /**
     * @test
     * 유효하지 않은 연도(year) 값 (문자열 등) 입력 시 400 응답을 반환하는지 테스트
     */
    public function getDailyCalendar_shouldReturn400OnInvalidYearType()
    {
        $params = [
            'regionCode' => '11000',
            'year' => 'invalid_year', // filter_var($year, FILTER_VALIDATE_INT) -> false
            'month' => '10'
        ];

        // Model은 호출되지 않아야 함
        $this->modelMock->expects($this->never())
            ->method('getDailyAverageTemperature');

        // 출력 버퍼링 시작
        ob_start();
        $this->controller->getDailyCalendar($params);
        $output = ob_get_clean();

        // 응답 검증
        $responseData = json_decode($output, true);
        $this->assertEquals('error', $responseData['status']);
        // filter_var('invalid_year', FILTER_VALIDATE_INT)는 false를 반환하여 첫 번째 유효성 검사에서 걸림
        $this->assertStringContainsString('잘못된 요청입니다.', $responseData['message']);
    }

    /**
     * @test
     * Model에서 Exception 발생 시 500 응답을 반환하는지 테스트
     */
    public function getDailyCalendar_shouldReturn500OnModelException()
    {
        $params = [
            'regionCode' => '11000',
            'year' => '2025',
            'month' => '10'
        ];
        // Model에서 정의된 오류 메시지 사용
        $errorMessage = "기온 데이터 조회 중 서버 내부 오류가 발생했습니다.";

        // 1. Model Mock 설정: Model 메소드 호출 시 Exception을 throw 하도록 설정
        $this->modelMock->expects($this->once())
            ->method('getDailyAverageTemperature')
            ->willThrowException(new \Exception($errorMessage));

        // 2. 출력 버퍼링 시작 및 컨트롤러 메소드 실행
        ob_start();
        $this->controller->getDailyCalendar($params);
        $output = ob_get_clean();

        // 3. 응답 검증
        $responseData = json_decode($output, true);
        $this->assertIsArray($responseData);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals($errorMessage, $responseData['message']);
    }
}