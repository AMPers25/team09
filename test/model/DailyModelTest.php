<?php
/**
 * File: test/model/DailyModelTest.php
 * Author: 강한나
 * Description: 일일 날씨 통합 조회 Model 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\DailyModel;

class DailyModelTest extends TestCase
{
    /**
     * @test
     * Model이 정상적으로 데이터를 반환하는지 테스트
     */
    public function getDailyWeather_shouldReturnExpectedData()
    {
        $regionCode = '090';
        $date = '2025-10-12';
        $expectedResult = [
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

        // PDOStatement Mocking
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->expects($this->exactly(2))
            ->method('bindParam')
            ->withConsecutive(
                [':date_id', $date, \PDO::PARAM_STR],
                [':region_code', $regionCode, \PDO::PARAM_STR]
            );
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn($expectedResult);

        // PDO Mocking
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('FROM Region r'))
            ->willReturn($stmtMock);

        $model = new DailyModel($dbMock);

        // 메소드 실행 및 결과 검증
        $actualResult = $model->getDailyWeather($regionCode, $date);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     * 데이터가 없을 때 null을 반환하는지 테스트
     */
    public function getDailyWeather_shouldReturnNull_onNoData()
    {
        $regionCode = '090';
        $date = '2025-10-12';

        // PDOStatement Mocking: fetch가 false 반환
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->expects($this->exactly(2))
            ->method('bindParam');
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->with(\PDO::FETCH_ASSOC)
            ->willReturn(false);

        // PDO Mocking
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $model = new DailyModel($dbMock);

        // 메소드 실행 및 결과 검증
        $actualResult = $model->getDailyWeather($regionCode, $date);
        $this->assertNull($actualResult);
    }

    /**
     * @test
     * DB 오류 발생 시 Exception을 던지는지 테스트
     */
    public function getDailyWeather_shouldThrowExceptionOnDbError()
    {
        $regionCode = '090';
        $date = '2025-10-12';

        // PDO Mocking: prepare 호출 시 PDOException 발생
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException('Simulated DB Connection Error'));

        $model = new DailyModel($dbMock);

        // 예외 검증
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('일일 날씨 데이터 조회 중 서버 내부 오류가 발생했습니다.');

        $model->getDailyWeather($regionCode, $date);
    }

    /**
     * @test
     * execute() 실행 중 PDOException 발생 시 Exception을 던지는지 테스트
     */
    public function getDailyWeather_shouldThrowExceptionOnExecuteError()
    {
        $regionCode = '090';
        $date = '2025-10-12';

        // PDOStatement Mocking: execute 호출 시 PDOException 발생
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->expects($this->exactly(2))
            ->method('bindParam');
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new \PDOException('Simulated SQL Execution Error'));

        // PDO Mocking
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $model = new DailyModel($dbMock);

        // 예외 검증
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('일일 날씨 데이터 조회 중 서버 내부 오류가 발생했습니다.');

        $model->getDailyWeather($regionCode, $date);
    }
}

