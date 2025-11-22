-- 9월 요청 시 (month=09) 첫째주 평균이 8.2mm로 나오는 이유 확인
-- region_code=283, month=09

-- ============================================
-- 1. 9월 요청 시 API가 반환하는 WEEK_AVG 값 확인
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
    END AS rainfall_mm,
    -- 디버그: 해당 주의 모든 날짜와 강수량 확인
    CASE 
        WHEN GROUPING(s.week_start) = 0 AND GROUPING(s.date_id) = 1 THEN 
            (SELECT GROUP_CONCAT(CONCAT(DATE_FORMAT(date_id, '%Y-%m-%d'), ':', daily_rainfall) ORDER BY date_id)
             FROM (
                 SELECT rn2.date_id, COALESCE(rn2.daily_rainfall, 0) AS daily_rainfall
                 FROM Rain rn2
                 WHERE rn2.region_code = '283'
                   AND CASE 
                       WHEN DAYOFWEEK(rn2.date_id) = 1 THEN DATE_SUB(rn2.date_id, INTERVAL 6 DAY)
                       ELSE DATE_SUB(rn2.date_id, INTERVAL DAYOFWEEK(rn2.date_id) - 2 DAY)
                   END = s.week_start
                   AND rn2.date_id BETWEEN '2024-08-26' AND '2024-09-01'
             ) sub
            )
        ELSE NULL
    END AS week_days_detail
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
    GROUPING(s.date_id) = 1 OR DATE_FORMAT(s.date_id, '%Y-%m') = '2024-09'
ORDER BY GROUPING(s.week_start) ASC, GROUPING(s.date_id) ASC, s.date_id ASC;

-- ============================================
-- 2. 8.2mm가 나오는 경우 확인 (8월 26일~31일만 포함한 평균?)
-- ============================================
SELECT 
    '8월 26일~31일만 포함한 평균' AS description,
    COUNT(*) AS day_count,
    SUM(COALESCE(rn.daily_rainfall, 0)) AS total_rainfall,
    ROUND(AVG(COALESCE(rn.daily_rainfall, 0)), 1) AS avg_rainfall
FROM Rain rn
WHERE rn.region_code = '283'
  AND rn.date_id BETWEEN '2024-08-26' AND '2024-08-31';

-- ============================================
-- 3. 전체 주의 평균 (올바른 값)
-- ============================================
SELECT 
    '전체 주의 평균 (8월 26일~9월 1일)' AS description,
    COUNT(*) AS day_count,
    SUM(COALESCE(rn.daily_rainfall, 0)) AS total_rainfall,
    ROUND(AVG(COALESCE(rn.daily_rainfall, 0)), 1) AS avg_rainfall
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

