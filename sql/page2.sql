
-- 2-1 특정 날짜, 특정 지역의 모든 날씨 정보 조희
SELECT 
    t.region_code,
    t.date_id,
    aq.pm10,
    t.avg_temp,
    t.max_temp,
    t.min_temp,
    t.daily_temp_range,
    r.daily_rainfall,
    r.humidity,
    r.wind_speed,
    r.cloud_cover,
    lwi.uv_index,
    lwi.sensory_temp,
    lwi.discomfort_index
FROM 
    Temperature t
JOIN 
    AirQuality aq ON t.region_code = aq.region_code AND t.date_id = aq.date_id
JOIN 
    Rain r ON t.region_code = r.region_code AND t.date_id = r.date_id
JOIN 
    WeatherAlert wa ON t.region_code = wa.region_code AND t.date_id = wa.date_id
JOIN 
    LiveWeatherIndex lwi ON t.region_code = lwi.region_code AND t.date_id = lwi.date_id 
    -- LiveWeatherIndex(lwi) 테이블 누락되어 추가
WHERE 
    t.region_code = ? AND t.date_id = ?;
ORDER BY 
	d.date_id;


-- 2-2 특정 월, 특정 지역의 기온
SELECT 
    r.region_name,
	d.date_id,
    d.month AS month,
    AVG(t.avg_temp) AS avg_monthly_temp,
    MAX(t.max_temp) AS max_monthly_temp,
    MIN(t.min_temp) AS min_monthly_temp,
    AVG(t.daily_temp_range) AS avg_daily_temp_range
FROM 
    Temperature t
JOIN 
    DateDim d ON t.date_id = d.date_id
JOIN
	Region r ON t.region_code = r.region_code
WHERE 
    t.region_code = ? AND d.month = ?
ORDER BY 
	d.date_id;



-- 2-3 특정 월, 특정 지역의 강수 + 주별 강수량 rollup 
SELECT
    re.region_name,
	d.date_id
    d.month AS month,
    WEEK(r.date_id, 1) AS week_number,
    SUM(r.daily_rainfall) AS total_weekly_rainfall
FROM
    Rain ra
JOIN
    DateDim d ON ra.date_id = d.date_id
JOIN
	Region re ON ra.region_code = re.region_code
WHERE
    re.region_code = ? AND d.month = ?
GROUP BY
    week_number
ORDER BY 
	d.date_id;
WITH ROLLUP;


-- 2-4 특정 월, 특정 지역의 경보
SELECT 
    wa.region_code,
	d.date_id,
    d.month AS month,
    wa.alert_type,
    COUNT(*) AS alert_count
FROM 
    WeatherAlert wa
JOIN 
    DateDim d ON wa.date_id = d.date_id
WHERE 
    wa.region_code = ? AND d.month = ?
ORDER BY 
	d.date_id;


