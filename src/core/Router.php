<?php
/**
 * File: src/core/Router.php
 * Author: 김연수 (sooooscode), 황혜린
 * Date: 2025-11-21
 * Role: HTTP 요청 URI와 메소드를 기반으로 적절한 Controller 메소드를 찾아 연결(Dispatch)합니다.
 */

namespace App\Core;

class Router
{
    protected array $routes = [];
    protected $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * 라우트 등록 함수 (예: GET 요청)
     * @param string $uriPattern /api/calendar/{region}과 같은 패턴
     * @param string $controllerName App\Controller\Name
     * @param string $methodName Controller 내의 함수 이름
     */
    public function add(string $method, string $uriPattern, string $controllerName, string $methodName)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uriPattern,
            'controller' => $controllerName,
            'action' => $methodName,
        ];
    }

    public function get(string $uri, string $controller, string $action)
    {
        $this->add('GET', $uri, $controller, $action);
    }
    // POST, PUT, DELETE 메소드도 유사하게 추가 가능

    /**
     * 요청을 분석하고 적절한 Controller를 실행합니다.
     * @param string $uri 요청된 URI (/api/calendar/daily/11000/2025/10)
     * @param string $httpMethod 요청 메소드 (GET)
     */
    public function dispatch(string $uri, string $httpMethod)
    {
        $httpMethod = strtoupper($httpMethod);

        // 쿼리 스트링 제거 (실제 URI만 남김)
        $uri = strtok($uri, '?');

        // URI 정리 로직 추가
        // XAMPP 환경에서 요청 URI에 포함된 '/프로젝트_폴더/index.php' 경로를 제거합니다.
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('/index.php', '', $scriptName);

        // 1. URI에서 실행 스크립트 경로 제거 (예: /team09/index.php 제거)
        $uri = str_replace($scriptName, '', $uri);

        // 2. 프로젝트 폴더 경로가 남아있을 경우 제거 (예: /team09 제거)
        $uri = str_replace($basePath, '', $uri);

        // 3. URI의 시작 부분이 슬래시(/)로 시작하도록 보장
        if (substr($uri, 0, 1) !== '/') {
            $uri = '/' . $uri;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $httpMethod) {
                continue;
            }

            // 정규 표현식으로 패턴 변환 (예: {param}을 ([^/]+)로)
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['uri']);

            // 패턴 일치 여부 확인
            if (preg_match("#^" . $pattern . "$#", $uri, $matches)) {
                // 첫 번째 매치는 전체 URI이므로 제거
                array_shift($matches);

                // 1. Controller 이름에서 Model 이름을 추론하고 Model 인스턴스 생성
                $controllerName = $route['controller'];
                $modelName = str_replace('Controller', 'Model', $controllerName);
                if (!class_exists($modelName)) {
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => "Model class not found: $modelName"]);
                    return;
                }
                $modelInstance = new $modelName($this->dbConnection);

                // 3. Controller 인스턴스 생성 (Model 인스턴스 주입)
                $controllerInstance = new $controllerName($modelInstance); // Model 인스턴스 주입

                // 1. 라우트 패턴에서 파라미터 이름 추출 (예: {regionCode}, {month})
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route['uri'], $paramNames);
                $paramKeys = $paramNames[1]; // 추출된 이름 배열 (예: ['regionCode', 'month'])

                // 2. 이름과 값을 결합하여 연관 배열($params) 생성
                // $matches 배열의 요소 수만큼만 키를 사용합니다.
                $params = array_combine(array_slice($paramKeys, 0, count($matches)), $matches);

                // 쿼리스트링/POST JSON 병합
                if ($httpMethod === 'GET' || $httpMethod === 'DELETE') {
                    // GET/DELETE는 쿼리스트링이 우선 적용되도록 (동일 키 충돌 시 쿼리로 덮어씀)
                    $params = array_merge($_GET ?? [], $params);
                } elseif ($httpMethod === 'POST' || $httpMethod === 'PUT' || $httpMethod === 'PATCH') {
                    // JSON 우선, 아니면 form-urlencoded($_POST)
                    $raw  = file_get_contents('php://input');
                    $json = json_decode($raw, true);
                    $body = is_array($json) ? $json : ($_POST ?? []);
                    // POST/PUT/PATCH는 body가 path를 덮어씀(동일 키 충돌 시 body로 덮어씀)
                    $params = array_merge($params, $body);
                }

                // Reflection을 사용하여 메소드를 호출하고 URL 변수를 전달합니다.
                try {
                    // $matches 배열 대신, 캡슐화된 $params 배열 하나를 인자 목록으로 묶어서 전달합니다.
                    call_user_func_array([$controllerInstance, $route['action']], [$params]);
                    return;

                } catch (\Exception $e) {
                    // Controller 내부 오류 처리
                    http_response_code(500);
                    echo json_encode(['status' => 'error', 'message' => 'Controller Internal Error: ' . $e->getMessage()]);
                    return;
                }
            }
        }

        // 일치하는 라우트가 없을 경우 404 응답
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Not Found']);
    }
}