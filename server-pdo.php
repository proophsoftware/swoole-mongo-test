<?php
declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

$useMySql = true;

$http = new swoole_http_server("0.0.0.0", 9501);

$http->set([
    'worker_num' => 1,
    'enable_coroutine' => true,
]);

\Swoole\Runtime::enableCoroutine();

$http->on("start", function ($server) {
    echo "Swoole http server started on http://{$server->host}:{$server->port}/\n";
});

$http->on("request", function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) use ($useMySql) {

    if($request->server['request_uri'] === '/favicon.ico') {
        $response->header("Content-Type", "text/plain");
        $response->end('no fav found');
        return;
    }

    $reqNum = $request->get['req_num'] ?? 0;

    error_log("Handling request #$reqNum");

    $body = "Handling request #$reqNum ...\n";

    if($reqNum % 2 === 0) {
        $body.= "Send PDO to sleep for 10 s ...";

        if($useMySql) {
            $pdo = new PDO('mysql:host=mysql;dbname=test', 'dev', 'dev');
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $pdo->prepare('select sleep(10)');
        } else {
            $pdo = new PDO('pgsql:host=postgres port=5432 dbname=test', 'postgres');
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $stmt = $pdo->prepare('select pg_sleep(10)');
        }

        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $body.= "PDO query finished.\n";
    }

    error_log("Req #$reqNum finished");

    $response->header("Content-Type", "text/plain");
    $response->end($body);
});

$http->start();
