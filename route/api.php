<?php
/**
 * File: routes/api.php
 * Author: 김연수 (sooooscode)
 * Date: 2025-11-19
 * Role: 최종 API 명세에 따라 모든 엔드포인트를 Router 클래스에 등록합니다.
 * Note: $router 변수는 index.php에서 이 파일을 require하기 전에 생성되어야 합니다.
 */

use App\Controller\PopularRegionController;
use App\Controller\WeatherDailyController;
use App\Controller\TemperatureCalendarController;
use App\Controller\WeatherRainRollupController;
use App\Controller\WeatherAlertCalendarController;
use App\Controller\CleanDayController;
use App\Controller\BestPeriodController;
use App\Controller\BestRegionController;
use App\Controller\BookMarkController;

// 익명 함수(Closure)를 반환하고, $router 인자를 받는 함수
return function (\App\Core\Router $router) {
    //================================================================
    // 1. 랭킹 기능 (1-x)
    //================================================================

    // 1-2) 지역 즐겨찾기 랭킹 (인기 지역 랭킹 조회)
    // URL: /api/ranking
    // 예시: /api/ranking?orderBy=favorite_count
    $router->get('/api/ranking',
        PopularRegionController::class, 'getPopularRegionRanking');


    //================================================================
    // 2. 캘린더 및 일일 날씨 조회 (2-x)
    //================================================================

    // 2-1) 특정 날짜·특정 지역의 모든 날씨 정보 조회
    // URL: /api/calendar/daily/{regionCode}/{date}
    $router->get('/api/calendar/daily/{regionCode}/{date}',
        WeatherDailyController::class, 'getDailyWeather');

    // 2-2) 기온 캘린더 조회 (월/지역별 일일 평균 기온)
    // URL: /api/calendar/temperature/{regionCode}/{month}
    $router->get('/api/calendar/temperature/{regionCode}/{month}',
        TemperatureCalendarController::class, 'getDailyCalendar');

    // 2-3) 강수량 캘린더 조회
    // URL: /api/calendar/rain/{regionCode}/{year}/{month}
    $router->get('/api/calendar/rain/{regionCode}/{year}/{month}',
        WeatherRainRollupController::class, 'getRainCalendar');

    // 2-4) 경보 캘린더 조회
    // URL: /api/calendar/alert/{regionCode}/{year}/{month}
    $router->get('/api/calendar/alert/{regionCode}/{year}/{month}',
        WeatherAlertCalendarController::class, 'getAlertCalendar');


    //================================================================
    // 3. 추천/분석 기능 (3-x)
    //================================================================

    // 3-1) 최대 연속 클린데이 추천
    // URL: /api/recommend/air-quality/{region_code}
    $router->get('/api/recommend/air-quality/{region_code}',
        CleanDayController::class, 'getCleanStreakRankingAction');

    // 3-2) 여행 적합 기간 추천 (주간 Top 5)
    // URL: /api/recommend/best-period/{region_code}
    $router->get('/api/recommend/best-period/{region_code}',
        BestPeriodController::class, 'getWeekRankingAction');

    // 3-3) 지역별 여행 적합 지역 추천 (Top 5)
    // URL: /api/recommend/best-region
    $router->get('/api/recommend/best-region/{start_date}/{end_date}',
        BestRegionController::class, 'getRegionRankingAction');


    //================================================================
    // 4. 즐겨찾기 (4-x) - 동일 URL, HTTP 메서드 분리
    //================================================================

    // 4-1) 즐겨찾기 생성 (POST)
    // URL: /api/bookmarks
    $router->add('POST', '/api/bookmarks',
        BookMarkController::class, 'createBookmark');

    // 4-3) 즐겨찾기 목록 조회 (GET)
    // URL: /api/bookmarks
    $router->get('/api/bookmarks',
        BookMarkController::class, 'getBookmarkList');

    // 4-2) 즐겨찾기 삭제 (DELETE)
    // URL: /api/bookmarks/{bookmarkId} - 즐겨찾기 ID를 URL 경로로 받도록 수정
    $router->add('DELETE', '/api/bookmarks/{bookmarkId}',
        BookMarkController::class, 'deleteBookmark');

    // 즐겨찾기 수정 (PUT/PATCH)
    // URL: /api/bookmarks/{bookmarkId}
    $router->add('PUT', '/api/bookmarks/{bookmarkId}',
        BookMarkController::class, 'updateBookmark');
};
