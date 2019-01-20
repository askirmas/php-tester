<?php
require_once(__DIR__.'/../../CycleHandler.php');

class Isracard extends CycleHandler {
  static function onResponseFormed(object $env, object $r, object $i): object {
    return (object) (
      $r->{'return:code'} != 0 ? []
      : array(
        'iframeUrl' => "$r->iframeUrl?".http_build_query(
          array_reduce(
            [
              ['name:first' => 'first_name'],
              ['name:last' => 'last_name'],
              ['email' => 'email'],
              ['phone' => 'phone']
            ],
            function($acc, $fields) use ($i) {
              $commonKey = array_keys($fields)[0];
              $instanceKey = $fields[$commonKey];
              if (property_exists($i, $commonKey) && !empty($i->{$commonKey}))
                $acc[$instanceKey] = $i->{$commonKey};
              return $acc;
            },
            []
          )
        )
      )
    );
  }
}
