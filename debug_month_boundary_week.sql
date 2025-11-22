-- 8월 26일~9월 1일 주의 주평균 계산 분석
-- region_code=283, month=08 vs month=09

-- ============================================
-- 1. 해당 주의 모든 일별 강수량 데이터 조회
-- ============================================
SELECT 
    rn.date_id,
    DATE_FORMAT(rn.date_id, '%W') AS day_of_week,
    DAYOFWEEK(rn.date_id) AS dayofweek_num,
    CASE 
        WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
        ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
    END AS week_start,
    DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
    rn.daily_rainfall,
    r.region_name
FROM Rain rn
JOIN Region r ON r.region_code = rn.region_code
WHERE rn.region_code = '283'
  AND rn.date_id BETWEEN '2024-08-26' AND '2024-09-01'
ORDER BY rn.date_id;

-- ============================================
-- 2. 8월 요청 시 (month=08) - 현재 로직이 가져오는 데이터
-- ============================================
SELECT 
    '8월 요청 시 가져오는 데이터' AS description,
    rn.date_id,
    DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
    CASE 
        WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
        ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
    END AS week_start,
    rn.daily_rainfall,
    CASE 
        WHEN rn.date_id BETWEEN '2024-08-01' AND '2024-08-31' THEN '8월 내'
        ELSE '8월 외'
    END AS month_range
FROM Rain rn
WHERE rn.region_code = '283'
  AND (
      rn.date_id BETWEEN '2024-08-01' AND '2024-08-31'
      OR
      CASE 
          WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
          ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
      END IN (
          SELECT DISTINCT 
              CASE 
                  WHEN DAYOFWEEK(d2.date_id) = 1 THEN DATE_SUB(d2.date_id, INTERVAL 6 DAY)
                  ELSE DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
              END
          FROM DateDim d2
          WHERE d2.date_id BETWEEN '2024-08-01' AND '2024-08-31'
      )
  )
  AND CASE 
      WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
      ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
  END = CASE 
      WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
      ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
  END
ORDER BY rn.date_id;

-- ============================================
-- 3. 9월 요청 시 (month=09) - 현재 로직이 가져오는 데이터
-- ============================================
SELECT 
    '9월 요청 시 가져오는 데이터' AS description,
    rn.date_id,
    DATE_FORMAT(rn.date_id, '%Y-%m') AS ym,
    CASE 
        WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
        ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
    END AS week_start,
    rn.daily_rainfall,
    CASE 
        WHEN rn.date_id BETWEEN '2024-09-01' AND '2024-09-30' THEN '9월 내'
        ELSE '9월 외'
    END AS month_range
FROM Rain rn
WHERE rn.region_code = '283'
  AND (
      rn.date_id BETWEEN '2024-09-01' AND '2024-09-30'
      OR
      CASE 
          WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
          ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
      END IN (
          SELECT DISTINCT 
              CASE 
                  WHEN DAYOFWEEK(d2.date_id) = 1 THEN DATE_SUB(d2.date_id, INTERVAL 6 DAY)
                  ELSE DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
              END
          FROM DateDim d2
          WHERE d2.date_id BETWEEN '2024-09-01' AND '2024-09-30'
      )
  )
  AND CASE 
      WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
      ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
  END = CASE 
      WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
      ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
  END
ORDER BY rn.date_id;

-- ============================================
-- 4. 8월 요청 시 ROLLUP 결과 (현재 로직)
-- ============================================
SELECT
    '8월 요청 시 ROLLUP 결과' AS description,
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
    MAX(s.ym) AS ym,
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
        CASE 
            WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
            ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
        END AS week_start
    FROM Rain rn
    WHERE rn.region_code = '283'
      AND (
          rn.date_id BETWEEN '2024-08-01' AND '2024-08-31'
          OR
          CASE 
              WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
              ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
          END IN (
              SELECT DISTINCT 
                  CASE 
                      WHEN DAYOFWEEK(d2.date_id) = 1 THEN DATE_SUB(d2.date_id, INTERVAL 6 DAY)
                      ELSE DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
                  END
              FROM DateDim d2
              WHERE d2.date_id BETWEEN '2024-08-01' AND '2024-08-31'
          )
      )
) s
WHERE s.week_start = CASE 
    WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
    ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
END
GROUP BY s.week_start, s.date_id WITH ROLLUP
HAVING 
    (GROUPING(s.date_id) = 1 OR MAX(s.ym) = '2024-08')
ORDER BY GROUPING(s.week_start) ASC, GROUPING(s.date_id) ASC, s.date_id ASC;

-- ============================================
-- 5. 9월 요청 시 ROLLUP 결과 (현재 로직)
-- ============================================
SELECT
    '9월 요청 시 ROLLUP 결과' AS description,
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
    MAX(s.ym) AS ym,
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
        CASE 
            WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
            ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
        END AS week_start
    FROM Rain rn
    WHERE rn.region_code = '283'
      AND (
          rn.date_id BETWEEN '2024-09-01' AND '2024-09-30'
          OR
          CASE 
              WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
              ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
          END IN (
              SELECT DISTINCT 
                  CASE 
                      WHEN DAYOFWEEK(d2.date_id) = 1 THEN DATE_SUB(d2.date_id, INTERVAL 6 DAY)
                      ELSE DATE_SUB(d2.date_id, INTERVAL DAYOFWEEK(d2.date_id) - 2 DAY)
                  END
              FROM DateDim d2
              WHERE d2.date_id BETWEEN '2024-09-01' AND '2024-09-30'
          )
      )
) s
WHERE s.week_start = CASE 
    WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
    ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
END
GROUP BY s.week_start, s.date_id WITH ROLLUP
HAVING 
    (GROUPING(s.date_id) = 1 OR MAX(s.ym) = '2024-09')
ORDER BY GROUPING(s.week_start) ASC, GROUPING(s.date_id) ASC, s.date_id ASC;

-- ============================================
-- 6. 올바른 주 평균 계산 (참고용)
-- ============================================
SELECT 
    '올바른 주 평균 (전체 주 데이터 포함)' AS description,
    CASE 
        WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
        ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
    END AS week_start_date,
    COUNT(*) AS day_count,
    SUM(COALESCE(rn.daily_rainfall, 0)) AS total_rainfall,
    ROUND(AVG(COALESCE(rn.daily_rainfall, 0)), 1) AS correct_avg_rainfall
FROM Rain rn
WHERE rn.region_code = '283'
  AND CASE 
      WHEN DAYOFWEEK(rn.date_id) = 1 THEN DATE_SUB(rn.date_id, INTERVAL 6 DAY)
      ELSE DATE_SUB(rn.date_id, INTERVAL DAYOFWEEK(rn.date_id) - 2 DAY)
  END = CASE 
      WHEN DAYOFWEEK('2024-08-26') = 1 THEN DATE_SUB('2024-08-26', INTERVAL 6 DAY)
      ELSE DATE_SUB('2024-08-26', INTERVAL DAYOFWEEK('2024-08-26') - 2 DAY)
  END
  AND rn.date_id BETWEEN '2024-08-26' AND '2024-09-01';

