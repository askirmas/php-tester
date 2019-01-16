<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once(__DIR__.'/assoc.php');
require_once(__DIR__.'/utils.php');
require_once(__DIR__.'/handler.php');
$commonHandler = 'CommonHandler';
$tmstmp = date('Ymd_His-').rand();

//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'))->netpay[0];
//$input = json_decode(file_get_contents(__DIR__.'/index.test.json'))->isra_frame_good[0];
$input = (object) (sizeof($_REQUEST) !== 0
? $_REQUEST
: (array_key_exists('argv', $_SERVER)
? json_decode(preg_replace('/(^"|"$)/i', '', $_SERVER['argv'][1]))
:  json_decode(file_get_contents('php://input'))
));
if (!property_exists($input, 'id')) $input->id = '';

$ConfigDir = mkdir2(__DIR__, 'configs');
$step = json_decode(file_get_contents($ConfigDir."/processes/$input->process.json"));
$handler = $step->instance;
$instance = json_decode(file_get_contents($ConfigDir."/instances/$handler/index.json"));

$logDir = mkdir2(__DIR__, 'processes', $input->process, $input->id, $tmstmp);

$handlerPath = $ConfigDir."/inctances/$handler/handler.php";
if (file_exists($handlerPath)) require_once($handlerPath);
else {
  $handler = 'CycleHandler';
  require_once(__DIR__."/$handler.php");
}

$instanceEnv = json_decode(file_get_contents($ConfigDir."/instances/$step->instance/accounts/$step->account.json"));

$request = \assoc\merge(1, 1, $instance->request, $instanceEnv->request);
$response = \assoc\merge(1, 1, $instance->response, $instanceEnv->response);

$url = ((object) $request->engine)->gateway;

$event = 'Request';
$phase = 'Raw';
$requestData = fireEvent($input);

$requestData = \assoc\merge(1, 0,
  $request->defaults,
  $requestData,
  $request->overrides
);

$event = 'Request';
$phase = 'Formed';
$requestData = fireEvent($requestData);
$requestData = \assoc\mapKeys(
  \assoc\mapValues(
    $requestData,
    (object) $request->values,
    true
  ),
  $request->fields,
  false
);

$request->engine = (object) $request->engine;
switch($request->engine->method) {
  case 'POST':
    $ch = curl_init($request->engine->gateway);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE,"XDEBUG_SESSION=VSCODE");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request->engine->method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [   
      'Content-Type: application/json'                                                              
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    $responseData = json_decode(curl_exec($ch));
    curl_close($ch);
    break;
  case 'GET':
    $gate = $url.'?'.http_build_query($requestData);
    parse_str(
      file_get_contents($gate),
      $responseData
    );
    $responseData = (object) $responseData; 
    $responseData->gate = $gate;
    break;
  default: exit('not impelemented');
}
$event = 'Response';
$phase = 'Raw';
$output = fireEvent($responseData, $requestData);

$output = \assoc\mapValues(
  \assoc\mapKeys(
    $output,    
    \assoc\flip($response->fields),
    true
  ),
  (object) $response->values,
  true
);

$event = 'Response';
$phase = 'Formed';
$output = fireEvent($output, $input);

echo json_encode($output);

function fireEvent(...$data) :object {
  global $event, $phase, $handler, $logDir, $commonHandler, $step;
  $data[0] = \assoc\merge(1, 0, $data[0], call_user_func(["\\$commonHandler", "on$event$phase"], ...array_merge([$step], $data)));
  $data[0] = \assoc\merge(1, 0, $data[0], call_user_func(["\\$handler", "on$event$phase"], ...array_merge([$step], $data)));
  file_put_contents(
    "$logDir/$handler-$event$phase.json",
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
  );
  return $data[0];
}
