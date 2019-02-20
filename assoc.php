<?php
declare(strict_types=1);
namespace assoc;

function mapKeys(
  object $assoc,
  object $keyMap,
  bool $keepUnmet = false,
  string $delimiter = ":"
) :object {
  $result = new \stdClass; // class{} or \stdClass 
  forEach(assoc2table(json_decode(json_encode($assoc), true)) as $row) {
    $lastIndex = 0;
    do {
      $lastIndex++;
      //NB! Last iteration on {a:{b:c}} is [a,b,c] - feature
      $key = join($delimiter, array_slice($row, 0, $lastIndex));
      
    } while (
      !property_exists($keyMap, $key)
      && $lastIndex < sizeof($row)
    );
    if (property_exists($keyMap, $key))
      $key = $keyMap->{$key};
    else {
      if (!$keepUnmet)
        continue;
      $lastIndex = sizeof($row) - 1;
      $key = join($delimiter, array_slice($row, 0, $lastIndex));
    }
    $value = join($delimiter, array_slice($row, $lastIndex));

    $matches = [];
    //Idea like \assoc.php:formatString but very different implementation
    if (preg_match('|^{(.*)}$|', $key, $matches))
      if (property_exists($assoc, $matches[1]))
        $key = $assoc->{$matches[1]};

    $result->{$key} = $value;
  }
  return $result;
}
 
function mapValues(
  object $assoc,
  object $valuesMap,
  bool $keepUnmet = false
) :object {
  $result = new \stdClass;
  forEach((array) $assoc as $key0 => $value0) {
    $key = (string) $key0;
    $value = (string) $value0;
    if (
      property_exists($valuesMap, $key)
      && (!in_array(gettype($valuesMap->{$key}), ['array', 'object']))
    )
      $result->{$key} = $valuesMap->{$key};
    elseif (
      property_exists($valuesMap, $key)
      && property_exists($valuesMap->{$key}, $value)
    )
      $result->{$key} = $valuesMap->{$key}->{$value};
    elseif ($keepUnmet)
      $result->{$key} = $value;
  }
  return $result;
}

function merge(...$objects) {
  $base = (array) array_shift($objects);
  forEach($objects as $obj)
    forEach((array) $obj as $key => $value) {
      $base[$key] = (
        !array_key_exists($key, $base)
        || !isESObject($value)
        || !isESObject($base[$key])
      )
      ? $value
      : merge($base[$key], $value);
    }
  return $base;
}

function flip($obj) :object {
  return (object) array_flip((array) $obj);
}

function isESObject($var) {
  return in_array(gettype($var), ['array', 'object']);
}

function assoc2table(array $assoc) {
  $rows = [];
  foreach($assoc as $key => $value) {
    if (!is_array($value))
      array_push($rows, [$key, $value]);
    else
      foreach(assoc2table($value) as $subRow)
        array_push($rows, array_merge([$key], $subRow));
  }
  return $rows;
}

function row2assoc(array $row) {
  $len = sizeof($row);
  $result = [$row[$len - 2] => $row[$len - 1]];
  foreach(array_slice(array_reverse($row), 2) as $key)
    $result = [$key => $result];
  return $result;
}