<?php
require_once(__DIR__.'/../../CycleHandler.php');

class Isracard extends CycleHandler {
  static function onResponseFormed(object $r, object $i): object {
    return (object) (
      $r->{'return:code'} != 0 ? []
      : array(
        'amount' => ((int) $r->amountInt) / 100,
        'iframeUrl' => "$r->iframeUrl?".http_build_query(
          array_reduce(['name:first', 'name:last', 'email', 'phone'],
            function($acc, $field) use ($i) {
              if (property_exists($i, $field) && !empty($i->{$field}))
                $acc[$field] = $i->{$field};
              return $acc;
            },
            []
          )
        )
      )
    );
  }
}

