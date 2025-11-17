<?php
/**
 * File: test/model/WeatherAlertCalendarModelTest.php
 * Author: 황혜린
 * Description: 기능 2-4. 특정 월의 지역별 기상 특보 목록(Model) 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\WeatherAlertCalendarModel;

class WeatherAlertCalendarModelTest extends TestCase
{
    /**
     * @test
     * 정상 조회 시 alert_id/date_id/alert_time/alert_type가 기대 형태로 반환되는지 검증
     */
    public function getMonthlyAlerts_shouldReturnRows()
    {
        $region = '01101';
        $year   = 2025;
        $month  = 10;

        $dbRows = [
            ['alert_id' => '1001', 'date_id' => '2025-10-03', 'alert_time' => '14:00:00', 'alert_type' => '폭우'],
            ['alert_id' => '1002', 'date_id' => '2025-10-16', 'alert_time' => '11:00:00', 'alert_type' => '폭설'],
        ];

        // PDOStatement mock
        $stmtMock = $this->createMock(\PDOStatement::class);
        // bindValue 검증은 드라이버별 차이를 허용하기 위해 생략
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($dbRows);

        // PDO mock
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
               ->method('prepare')
               ->with($this->stringContains('LAST_DAY'))
               ->willReturn($stmtMock);

        $model = new WeatherAlertCalendarModel($dbMock);
        $actual = $model->getMonthlyAlerts($region, $year, $month);

        $this->assertCount(2, $actual);

        $this->assertIsInt($actual[0]['alert_id']);
        $this->assertSame(1001, $actual[0]['alert_id']);
        $this->assertSame('2025-10-03', $actual[0]['date_id']);
        $this->assertSame('14:00:00', $actual[0]['alert_time']);
        $this->assertSame('폭우', $actual[0]['alert_type']);

        $this->assertSame(1002, $actual[1]['alert_id']);
        $this->assertSame('2025-10-16', $actual[1]['date_id']);
        $this->assertSame('폭설', $actual[1]['alert_type']);
    }

    /**
     * @test
     * DB 오류 발생 시 예외를 던지는지 검증
     */
    public function getMonthlyAlerts_shouldThrowExceptionOnDbError()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
               ->method('prepare')
               ->willThrowException(new \PDOException('Simulated DB error'));

        $model = new WeatherAlertCalendarModel($dbMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('기상 특보 조회 중 서버 내부 오류가 발생했습니다.');
        $model->getMonthlyAlerts('01101', 2025, 10);
    }
}
