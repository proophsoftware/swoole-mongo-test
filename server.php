<?php
declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";

\Swoole\Runtime::enableCoroutine(true);

$http = new swoole_http_server("0.0.0.0", 9501);

$http->set([
    'worker_num' => 1,
    'enable_coroutine' => true,
]);

$http->on("start", function ($server) {
    echo "Swoole http server started on http://{$server->host}:{$server->port}/\n";
});

$prepareMongo = function (\MongoDB\Client $client) {
  $testDb = $client->selectDatabase('test');
  $testDb->selectCollection('sleepcol')->updateOne(['_id' => 1], [
      '$set' => [
          '_id' => 1,
          'desc' => 'Single doc in collection for consistent sleep behaviour'
      ]
  ], [
      'upsert' => true
  ]);
};

$http->on("request", function (\Swoole\Http\Request $request, \Swoole\Http\Response $response) use ($prepareMongo) {

    if($request->server['request_uri'] === '/favicon.ico') {
        $response->header("Content-Type", "text/plain");
        $response->end('no fav found');
        return;
    }

    $mongo = new \MongoDB\Client('mongodb://mongo:27017');

    $prepareMongo($mongo);

    $result = $mongo->selectCollection('test', 'test')->insertOne([
        'run' => (new DateTimeImmutable())->format('Y-m-d\TH:i:s.u')
    ]);

    $id = $result->getInsertedId();

    $reqNum = $request->get['req_num'] ?? 0;

    $body = "Handling request #$reqNum ...\n";

    $body.= "Inserted new run with id: $id\n";

    if($reqNum % 2 === 0) {
        $body.= "Send mongo to sleep for 10000ms ...";

        $res = $mongo->selectCollection('test', 'sleepcol')->find([
            '$where' => 'sleep(10000) || true'
        ]);

        $body.= "Mongo query finished.\n";
    }

    $response->header("Content-Type", "text/plain");
    $response->end($body);

    unset($mongo);
});

$http->start();
