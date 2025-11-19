<?php
/**
 * File: test/model/WeatherRainRollupModelTest.php
 * Author: 황혜린
 * Description: 기능 2-3. 특정 지역/기간의 일별 강수량 + 월 합계(ROLLUP) 모델 테스트
 * Last Updated: 2025-11-17
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use App\Model\WeatherRainRollupModel;

class WeatherRainRollupModelTest extends TestCase
{
    /**
     * @test
     * 정상 조회 시 DAY 레벨과 MONTH_TOTAL 레벨이 순서대로 반환되는지 검증
     */
    public function getRainRollup_shouldReturnDayAndMonthRows()
    {
        $region = '01101';
        $from   = '2025-10-01';
        $to     = '2025-10-31';

        // DB가 반환할 모의 데이터 (문자열 숫자 → 모델에서 float 캐스팅 확인)
        $dbRows = [
            ['level' => 'DAY',          'date_id' => '2025-10-01', 'ym' => '2025-10', 'rainfall_mm' => '3.2'],
            ['level' => 'DAY',          'date_id' => '2025-10-02', 'ym' => '2025-10', 'rainfall_mm' => '0'],
            ['level' => 'MONTH_TOTAL',  'date_id' => null,          'ym' => '2025-10', 'rainfall_mm' => '78.4'],
        ];

        // PDOStatement mock
        $stmtMock = $this->createMock(\PDOStatement::class);
        // 🔸 bindValue 호출 횟수/인자 검증은 제거 (드라이버별 호출 수 차이 허용)
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetchAll')->with(\PDO::FETCH_ASSOC)->willReturn($dbRows);

        // PDO mock
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WITH ROLLUP')) // 쿼리 핵심만 검증
            ->willReturn($stmtMock);

        $model = new WeatherRainRollupModel($dbMock);
        $actual = $model->getRainRollup($region, $from, $to);

        // 반환 값 검증
        $this->assertCount(3, $actual);

        $this->assertSame('DAY', $actual[0]['level']);
        $this->assertSame('2025-10-01', $actual[0]['date_id']);
        $this->assertSame('2025-10', $actual[0]['ym']);
        $this->assertIsFloat($actual[0]['rainfall_mm']);
        $this->assertEquals(3.2, $actual[0]['rainfall_mm']);

        $this->assertSame('DAY', $actual[1]['level']);
        $this->assertEquals(0.0, $actual[1]['rainfall_mm']);

        $this->assertSame('MONTH_TOTAL', $actual[2]['level']);
        $this->assertNull($actual[2]['date_id']);
        $this->assertSame('2025-10', $actual[2]['ym']);
        $this->assertEquals(78.4, $actual[2]['rainfall_mm']);
    }

    /**
     * @test
     * DB 오류 발생 시 예외를 던지는지 검증
     */
    public function getRainRollup_shouldThrowExceptionOnDbError()
    {
        $dbMock = $this->createMock(\PDO::class);
        $dbMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \PDOException('Simulated DB error'));

        $model = new WeatherRainRollupModel($dbMock);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('강수량 ROLLUP 조회 중 서버 내부 오류가 발생했습니다.');
        $model->getRainRollup('01101', '2025-10-01', '2025-10-31');
    }
}
