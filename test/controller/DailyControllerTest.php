<?php
/**
 * File: test/controller/DailyControllerTest.php
 * Description: 라우터 기반 DailyController 테스트
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/controller/DailyController.php';
require_once __DIR__ . '/../../src/model/DailyModel.php';

use PHPUnit\Framework\TestCase;
use App\Model\DailyModel;
use App\Controller\DailyController;

class DailyControllerTest extends TestCase
{
    private int $bufferLevel;

    protected function setUp(): void
    {
        $this->bufferLevel = ob_get_level();
        ob_start();
    }

    protected function tearDown(): void
    {
        while (ob_get_level() > $this->bufferLevel) {
            ob_end_clean();
        }
    }

    private function sampleRow(): array
    {
        return [
            'region_code' => '090',
            'region_name' => '서울',
            'date_id' => '2025-10-12',
            'avg_temp' => 19.2,
            'max_temp' => 23.0,
            'min_temp' => 14.8,
            'daily_temp_range' => 8.2,
            'daily_rainfall' => 0.0,
            'humidity' => 58,
            'wind_speed' => 2.7,
            'cloud_cover' => 3,
            'status_code' => 90,
            'alert_time' => '12:00:00',
            'alert_type' => '호우주의보',
            'pm10' => 21,
        ];
    }

    private function decodedOutput(): array
    {
        $content = ob_get_contents();
        ob_clean();
        return json_decode($content, true);
    }

    public function testGetDailyWeatherReturnsSuccessPayload(): void
    {
        $modelMock = $this->createMock(DailyModel::class);
        $modelMock->method('getDailyWeather')
            ->with('090', '2025-10-12')
            ->willReturn($this->sampleRow());

        $controller = new DailyController($modelMock);
        $controller->getDailyWeather(['region_code' => '090', 'date' => '2025-10-12']);

        $response = $this->decodedOutput();
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('090', $response['data']['region_code']);
        $this->assertArrayHasKey('temperature', $response['data']);
    }

    public function testGetDailyWeatherRequiresParameters(): void
    {
        $controller = new DailyController($this->createMock(DailyModel::class));
        $controller->getDailyWeather([]);

        $response = $this->decodedOutput();
        $this->assertEquals(400, $response['status']);
    }

    public function testGetDailyWeatherValidatesFormat(): void
    {
        $controller = new DailyController($this->createMock(DailyModel::class));
        $controller->getDailyWeather(['region_code' => 'abc', 'date' => '2025-10-12']);

        $response = $this->decodedOutput();
        $this->assertEquals(400, $response['status']);
    }

    public function testGetDailyWeatherReturns404WhenNoData(): void
    {
        $modelMock = $this->createMock(DailyModel::class);
        $modelMock->method('getDailyWeather')->willReturn(null);

        $controller = new DailyController($modelMock);
        $controller->getDailyWeather(['region_code' => '090', 'date' => '2025-10-12']);

        $response = $this->decodedOutput();
        $this->assertEquals(404, $response['status']);
    }

    public function testGetDailyWeatherHandlesException(): void
    {
        $modelMock = $this->createMock(DailyModel::class);
        $modelMock->method('getDailyWeather')->willThrowException(new \Exception('DB Error'));

        $controller = new DailyController($modelMock);
        $controller->getDailyWeather(['region_code' => '090', 'date' => '2025-10-12']);

        $response = $this->decodedOutput();
        $this->assertEquals(500, $response['status']);
    }

    public function testGetDayInfoTransformsPayload(): void
    {
        $modelMock = $this->createMock(DailyModel::class);
        $modelMock->method('getDailyWeather')->willReturn($this->sampleRow());

        $controller = new DailyController($modelMock);
        $controller->getDailyWeather([
            '_route' => '/api/day-info/{region_code}/{date}',
            'region_code' => '090',
            'date' => '2025-10-12',
        ]);

        $response = $this->decodedOutput();
        $this->assertEquals('서울', $response['regionName']);
        $this->assertStringContainsString('°', $response['temperature'][0]['value']);
    }
}
