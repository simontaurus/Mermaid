<?php

namespace Mermaid;

class MermaidConfigExtractor
{
  private $configMap = [
      'theme' => null,
      'fontFamily' => null,
      'logLevel' => null,
      'securityLevel' => null,
      'startOnLoad' => FILTER_VALIDATE_BOOLEAN,
      'arrowMarkerAbsolute' => FILTER_VALIDATE_BOOLEAN,
      'flowchart.curve' => null,
      'flowchart.useMaxWidth' => FILTER_VALIDATE_BOOLEAN,
      'flowchart.htmlLabels' => FILTER_VALIDATE_BOOLEAN,
      'flowchart.rankSpacing' => null,
      'flowchart.nodeSpacing' => null,
      'sequence.diagramMarginX' => null,
      'sequence.diagramMarginY' => null,
      'sequence.actorMargin' => null,
      'sequence.width' => null,
      'sequence.height' => null,
      'sequence.boxMargin' => null,
      'sequence.boxTestMargin' => null,
      'sequence.noteMargin' => null,
      'sequence.messageMargin' => null,
      'sequence.messageAlign' => null,
      'sequence.mirrorActors' => FILTER_VALIDATE_BOOLEAN,
      'sequence.bottomMarginAdj' => null,
      'sequence.useMaxWidth' => null,
      'sequence.rightAngles' => null,
      'sequence.showSequenceNumbers' => null,
      'gantt.titleTopMargin' => null,
      'gantt.barHeight' => null,
      'gantt.barGap' => null,
      'gantt.topPadding' => null,
      'gantt.leftPadding' => null,
      'gantt.gridLineStartPadding' => null,
      'gantt.fontSize' => null,
      'gantt.fontFamily' => null,
      'gantt.numberSectionStyles' => null,
      'gantt.axisFormat' => null
  ];

  public function extract(array $params) {
      $configMapKeys = array_keys($this->configMap);

      // Use reduce to split the param array into two arrays: [$mermaidConfig, $mediawikiParam]
      return array_reduce($params, function ($prev, $current) use ($configMapKeys) {
        // Destructures the two arrays
        list($mermaidConfig, $mwParams) = $prev;

        // if there is no "=", it belongs in mediawiki params
        if (strpos( $current, '=' ) === false) {
            $mwParams[] = $current;
            return [$mermaidConfig, $mwParams];
        }

        list( $key, $value ) = array_map( 'trim', explode( '=', $current, 2 ) );
        // test to see if the leftside of the "=" is in the configMap keys
        $normalizedKey = $this->keyNamingNormalizer($key);
        $inConfigMap = in_array($normalizedKey, $configMapKeys, true);

        // if not in config map, the value belongs in the mediawiki params
        if (!$inConfigMap) {
            $mwParams[] = $current;
            return [$mermaidConfig, $mwParams];
        }

        // config key is in the config map
        // check to see if there is a type associated with the key
        $normalizedValue = $value;
        $valueType = $this->configMap[$normalizedKey];
        if ( $valueType !== null ) {
            // normalize: 'true' => true, '1' => true, etc
            $normalizedValue = filter_var($value, $valueType, FILTER_NULL_ON_FAILURE);
        }

        // set the config with dot.notation
        $this->setWithDotNotation($mermaidConfig, $normalizedKey, $normalizedValue);
        return [$mermaidConfig, $mwParams];
      }, [[], []]);
  }

    /**
     * Removes "config." from the dot-notationed configuration key
     * @param string $key
     * @return false|string
     */
  protected function keyNamingNormalizer(string $key) {
    if (strpos($key, 'config.') === false) {
        return $key;
    }
    return substr($key, 7);
  }

  // Taken from Laravel's Arr::set function
    // https://github.com/laravel/framework/blob/7.x/src/Illuminate/Support/Arr.php
    /**
     * Sets a value onto an array with keys using dot.notation, in-place
     * @param &$array
     * @param $key
     * @param $value
     * @return array|mixed
     */
  protected function setWithDotNotation(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
