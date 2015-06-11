
<?php
/**
 * Parsing callback for yaml tag.
 * @param mixed $value Data from yaml file
 * @param string $tag Tag that triggered callback
 * @param int $flags Scalar entity style (see YAML_*_SCALAR_STYLE)
 * @return mixed Value that YAML parser should emit for the given value
 */
function tag_callback ($value, $tag, $flags) {
  var_dump(func_get_args()); // debugging
  return "Hello {$value}";
}

$yaml = <<<YAML
greeting: !example/hello World
YAML;

$result = yaml_parse($yaml, 0, $ndocs, array(
    '!example/hello' => 'tag_callback',
  ));

var_dump($result);
?>
