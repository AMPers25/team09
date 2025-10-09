<?php
/**
 * File: src/auth_session.php
 * Author: Yeonsu Kim (Backend Developer)
 * Date: 2025-10-09
 * Role: PHP 세션 시작 및 사용자가 입력한 지역 및 기간 정보 관리
 * (프로젝트 요구사항 2-(6))
 */

// 세션이 아직 시작되지 않았다면 시작
// 이 함수는 모든 페이지에서 require_once로 호출되어야 함
function start_session_if_not_started() {
    // 세션이 활성화되지 않았고, 시작된 적도 없을 때만 session_start() 호출
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * 홈페이지(index.php)에서 입력받은 여행 지역 및 기간 정보를 세션에 저장
 *
 * @param string $region_name 사용자가 입력한 지역명
 * @param string $start_date 여행 시작 날짜 (YYYY-MM-DD 형식)
 * @param string $end_date 여행 종료 날짜 (YYYY-MM-DD 형식)
 * @return bool 성공 여부
 */
function save_trip_selection(string $region_name, string $start_date, string $end_date): bool {
    start_session_if_not_started();

    // 입력 데이터의 간단한 유효성 검사
    if (empty($region_name) || empty($start_date) || empty($end_date)) {
        return false;
    }

    // 세션 변수에 저장
    $_SESSION['trip_selected'] = [
        'region_name' => $region_name,
        'start_date'  => $start_date,
        'end_date'    => $end_date
    ];

    return true;
}

/**
 * 세션에 저장된 현재 여행 선택 정보를 가져옴
 *
 * @return array|null 저장된 정보 배열 또는 정보가 없을 경우 null
 */
function get_trip_selection(): ?array {
    start_session_if_not_started();

    if (isset($_SESSION['trip_selected'])) {
        return $_SESSION['trip_selected'];
    }
    return null;
}

/**
 * 여행 정보를 세션에서 삭제(초기화)합니다. (1-1 페이지의 '일정 초기화' 기능과 연결)
 *
 * @return void
 */
function clear_trip_selection(): void {
    start_session_if_not_started();
    unset($_SESSION['trip_selected']);
}

// 이 파일이 로드되는 시점에 세션을 시작하도록 함수 호출
start_session_if_not_started();

?>
