<?php
/**
 * File: test/controller/WeatherDailyControllerTest.php
 * Author: 강한나
 * Description: 일일 날씨 통합 조회 Controller 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/WeatherDailyController.php';
require_once __DIR__ . '/../../src/model/WeatherDailyModel.php';

use PHPUnit\Framework\TestCase;
use App\Model\WeatherDailyModel;
use App\Controller\WeatherDailyController;

class WeatherDailyControllerTest extends TestCase
{
    /** @var int */
    private $outputBufferLevel;

    protected function setUp(): void
    {
        $this->outputBufferLevel = ob_get_level();
        ob_start();
        $_SERVER = [];
        $_GET = [];
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->outputBufferLevel) {
            ob_end_clean();
        }
    }

    /**
     * @test
     * [200 OK] 유효한 요청에 대해 일일 날씨 데이터를 반환하는지 테스트
     */
    public function getDailyWeatherAction_shouldReturn200Ok_onValidRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token_123';

        $regionCode = '090';
        $date = '2025-10-12';
        $mockData = [
            'region_code' => '090',
            'date_id' => '2025-10-12',
            'avg_temp' => 19.2,
            'max_temp' => 23.0,
            'min_temp' => 14.8,
            'daily_temp_range' => 8.2,
            'daily_rainfall' => 0.0,
            'humidity' => 58,
            'wind_speed' => 2.7,
            'cloud_cover' => 3,
            'status_code' => 1,
            'alert_time' => '12:00:00',
            'alert_type' => '호우주의보',
            'pm10' => 21,
        ];

        // Model Mocking
        $modelMock = $this->createMock(WeatherDailyModel::class);
        $modelMock->expects($this->once())
            ->method('getDailyWeather')
            ->with($regionCode, $date)
            ->willReturn($mockData);

        // Controller Mocking
        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidRegionCode', 'isValidDate', 'isValidJwtFromHeader', 'getModel'])
            ->getMock();

        $controllerMock->method('getModel')->willReturn($modelMock);
        $controllerMock->method('isValidRegionCode')->willReturn(true);
        $controllerMock->method('isValidDate')->willReturn(true);
        $controllerMock->method('isValidJwtFromHeader')->willReturn(true);

        // sendResponse가 200 OK로 호출되는지 검증
        $controllerMock->expects($this->once())
            ->method('sendResponse')
            ->with(
                $this->equalTo(200),
                $this->equalTo('OK'),
                $this->callback(function ($data) {
                    return isset($data['region_code']) && 
                           isset($data['temperature']) && 
                           isset($data['rain']) &&
                           isset($data['weather_alert']) &&
                           isset($data['air_quality']);
                })
            );

        $controllerMock->getDailyWeatherAction(['region_code' => $regionCode, 'date' => $date]);
    }

    /**
     * @test
     * [400 Bad Request] HTTP Method가 GET이 아닌 경우
     */
    public function getDailyWeatherAction_shouldReturn400_onInvalidMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse'])
            ->getMock();

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->equalTo('잘못된 요청 형식입니다.')
            );

        $controllerMock->getDailyWeatherAction(['region_code' => '090', 'date' => '2025-10-12']);
    }

    /**
     * @test
     * [401 Unauthorized] JWT 토큰이 유효하지 않은 경우
     */
    public function getDailyWeatherAction_shouldReturn401_onInvalidJwt()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidJwtFromHeader'])
            ->getMock();

        $controllerMock->method('isValidJwtFromHeader')->willReturn(false);

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(401),
                $this->equalTo('유효한 토큰이 필요합니다.')
            );

        $controllerMock->getDailyWeatherAction(['region_code' => '090', 'date' => '2025-10-12']);
    }

    /**
     * @test
     * [400 Bad Request] 필수 파라미터가 누락된 경우
     */
    public function getDailyWeatherAction_shouldReturn400_onMissingParameters()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token';

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidJwtFromHeader'])
            ->getMock();

        $controllerMock->method('isValidJwtFromHeader')->willReturn(true);

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->equalTo('필수 데이터가 누락되었습니다.')
            );

        // date 누락
        $controllerMock->getDailyWeatherAction(['region_code' => '090']);
    }

    /**
     * @test
     * [400 Bad Request] 잘못된 데이터 형식
     */
    public function getDailyWeatherAction_shouldReturn400_onInvalidFormat()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token';

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidRegionCode', 'isValidDate', 'isValidJwtFromHeader'])
            ->getMock();

        $controllerMock->method('isValidJwtFromHeader')->willReturn(true);
        $controllerMock->method('isValidRegionCode')->willReturn(false); // 잘못된 형식

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(400),
                $this->equalTo('잘못된 데이터 형식입니다.')
            );

        $controllerMock->getDailyWeatherAction(['region_code' => 'invalid', 'date' => '2025-10-12']);
    }

    /**
     * @test
     * [404 Not Found] 데이터가 없는 경우
     */
    public function getDailyWeatherAction_shouldReturn404_onNoData()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token';

        $regionCode = '090';
        $date = '2025-10-12';

        // Model이 null 반환
        $modelMock = $this->createMock(WeatherDailyModel::class);
        $modelMock->method('getDailyWeather')->willReturn(null);

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidRegionCode', 'isValidDate', 'isValidJwtFromHeader', 'getModel'])
            ->getMock();

        $controllerMock->method('getModel')->willReturn($modelMock);
        $controllerMock->method('isValidRegionCode')->willReturn(true);
        $controllerMock->method('isValidDate')->willReturn(true);
        $controllerMock->method('isValidJwtFromHeader')->willReturn(true);

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(404),
                $this->equalTo('해당 조건의 일일 날씨 데이터가 없습니다.')
            );

        $controllerMock->getDailyWeatherAction(['region_code' => $regionCode, 'date' => $date]);
    }

    /**
     * @test
     * [500 Internal Server Error] Model에서 Exception 발생 시
     */
    public function getDailyWeatherAction_shouldReturn500_onModelException()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_token';

        $regionCode = '090';
        $date = '2025-10-12';

        // Model이 Exception 던짐
        $modelMock = $this->createMock(WeatherDailyModel::class);
        $modelMock->method('getDailyWeather')
            ->willThrowException(new \Exception('일일 날씨 데이터 조회 중 서버 내부 오류가 발생했습니다.'));

        $controllerMock = $this->getMockBuilder(WeatherDailyController::class)
            ->setConstructorArgs([$this->createMock(\PDO::class)])
            ->onlyMethods(['sendResponse', 'sendErrorResponse', 'isValidRegionCode', 'isValidDate', 'isValidJwtFromHeader', 'getModel'])
            ->getMock();

        $controllerMock->method('getModel')->willReturn($modelMock);
        $controllerMock->method('isValidRegionCode')->willReturn(true);
        $controllerMock->method('isValidDate')->willReturn(true);
        $controllerMock->method('isValidJwtFromHeader')->willReturn(true);

        $controllerMock->expects($this->once())
            ->method('sendErrorResponse')
            ->with(
                $this->equalTo(500),
                $this->equalTo('서버 내부 오류입니다.')
            );

        $controllerMock->getDailyWeatherAction(['region_code' => $regionCode, 'date' => $date]);
    }
}
