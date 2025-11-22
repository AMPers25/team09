-- 10월 28일부터 11월 3일까지의 주평균 강수량 계산 분석
-- region_code=283, month=10

-- 1. 해당 주의 모든 일별 강수량 데이터 조회
SELECT 
    rn.date_id,
    DATE_FORMAT(rn.date_id, '%W') AS day_of_week,
    DAYOFWEEK(rn.date_id) AS dayofweek_num, -- 1=일요일, 2=월요일, ..., 7=토요일
    DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) AS week_start,
    rn.daily_rainfall,
    r.region_name
FROM Rain rn
JOIN Region r ON r.region_code = rn.region_code
WHERE rn.region_code = '283'
  AND rn.date_id BETWEEN '2024-10-28' AND '2024-11-03'
ORDER BY rn.date_id;

-- 2. 해당 주의 주평균 강수량 계산 (수동 계산용)
SELECT 
    DATE_SUB('2024-10-28', INTERVAL DAYOFWEEK('2024-10-28') - 2 DAY) AS week_start_date,
    COUNT(*) AS day_count,
    SUM(COALESCE(rn.daily_rainfall, 0)) AS total_rainfall,
    AVG(COALESCE(rn.daily_rainfall, 0)) AS avg_rainfall,
    ROUND(AVG(COALESCE(rn.daily_rainfall, 0)), 1) AS avg_rainfall_rounded
FROM Rain rn
WHERE rn.region_code = '283'
  AND DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) = DATE_SUB('2024-10-28', INTERVAL DAYOFWEEK('2024-10-28') - 2 DAY)
  AND rn.date_id BETWEEN '2024-10-28' AND '2024-11-03';

-- 3. 현재 API 로직이 가져오는 데이터 확인 (10월 전체 + 주 범위 확장)
-- Controller에서 month=10이면 from='2024-10-01', to='2024-10-31'
SELECT 
    rn.date_id,
    DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
    DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) AS week_start,
    rn.daily_rainfall,
    CASE 
        WHEN rn.date_id BETWEEN '2024-10-01' AND '2024-10-31' THEN '10월 내'
        ELSE '10월 외'
    END AS month_range
FROM Rain rn
WHERE rn.region_code = '283'
  AND (
      -- 해당 월의 날짜
      rn.date_id BETWEEN '2024-10-01' AND '2024-10-31'
      OR
      -- 해당 월의 날짜가 속한 주의 전체 날짜 (전달/다음달 포함)
      DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) IN (
          SELECT DISTINCT DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
          FROM DateDim d2
          WHERE d2.date_id BETWEEN '2024-10-01' AND '2024-10-31'
      )
  )
  AND DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) = DATE_SUB('2024-10-28', INTERVAL DAYOFWEEK('2024-10-28') - 2 DAY)
ORDER BY rn.date_id;

-- 4. ROLLUP 결과 확인 (실제 API가 반환하는 형식)
SELECT
    CASE 
        WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 0 THEN 'DAY'
        WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN 'WEEK_AVG'
        WHEN GROUPING(s.week_start) = 1 AND GROUPING(s.date_id) = 1 THEN 'MONTH_TOTAL'
        ELSE 'UNKNOWN'
    END AS level,
    CASE 
        WHEN GROUPING(s.date_id) = 0 THEN DATE_FORMAT(s.date_id, '%Y-%m-%d')
        ELSE NULL
    END AS date_id,
    s.ym AS ym,
    CASE 
        WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN DATE_FORMAT(s.week_start, '%Y-%m-%d')
        ELSE NULL
    END AS week_start,
    CASE 
        WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN ROUND(AVG(s.daily_rainfall), 1)
        WHEN GROUPING(s.week_start) = 1 AND GROUPING(s.date_id) = 1 THEN ROUND(AVG(s.daily_rainfall), 1)
        ELSE ROUND(SUM(s.daily_rainfall), 1)
    END AS rainfall_mm
FROM (
    SELECT
        rn.region_code,
        rn.date_id,
        COALESCE(rn.daily_rainfall, 0) AS daily_rainfall,
        DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
        DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) AS week_start
    FROM Rain rn
    WHERE rn.region_code = '283'
      AND (
          rn.date_id BETWEEN '2024-10-01' AND '2024-10-31'
          OR
          DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY) IN (
              SELECT DISTINCT DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
              FROM DateDim d2
              WHERE d2.date_id BETWEEN '2024-10-01' AND '2024-10-31'
          )
      )
) s
WHERE s.week_start = DATE_SUB('2024-10-28', INTERVAL DAYOFWEEK('2024-10-28') - 2 DAY)
GROUP BY s.ym, s.week_start, s.date_id WITH ROLLUP
HAVING GROUPING(s.ym) = 0
ORDER BY GROUPING(s.week_start) ASC, GROUPING(s.date_id) ASC, s.date_id ASC;

