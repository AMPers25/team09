<?php
/**
 * File: src/core/Router.php
 * Author: 김연수 (sooooscode)
 * Date: 2025-11-19
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

                // 1. Controller 인스턴스 생성 및 DB 연결 주입 (Dependency Injection)
                $controllerName = $route['controller'];

                // 해당 Model 클래스를 자동으로 파악하여 주입한다고 가정 (복잡도 때문에 생략)
                // 현재는 Controller의 생성자에 $dbConnection을 주입한다고 가정합니다.
                $controllerInstance = new $controllerName($this->dbConnection);

                // 2. 파라미터 재구성 (URI에서 추출된 변수를 Controller 메소드로 전달)
                // 여기서는 간단히 URI 추출 변수만 Controller에 전달한다고 가정합니다.
                // 실제로는 Request 객체에서 GET/POST/URI 파라미터를 통합하여 전달합니다.

                // 3. Controller 메소드 실행
                // 💡 Reflection을 사용하여 메소드를 호출하고 URL 변수를 전달합니다.
                try {
                    // $matches 배열은 Controller가 기대하는 파라미터 배열이 됩니다.
                    call_user_func_array([$controllerInstance, $route['action']], $matches);
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