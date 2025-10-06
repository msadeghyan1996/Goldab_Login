<?php

namespace app\Supports;

use Closure;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;


class Sanitizer {
    private array        $data;
    private array        $rules;
    private static array $purifierCache    = [];
    private static array $compiledPatterns = [];

    // Performance optimizations
    private const BULK_OPERATIONS_THRESHOLD = 50;
    private bool $enableBulkMode = false;

    public function __construct (array $data, array $rules, array $options = []) {
        $this->data           = $data;
        $this->rules          = $rules;
        $this->enableBulkMode = ($options['bulk_mode'] ?? false) || count($data) > self::BULK_OPERATIONS_THRESHOLD;
    }

    /**
     * Create a new Sanitizer instance.
     *
     * @param array<string, mixed>                $data
     * @param array<string, string|array<string>> $rules List of rules to apply per field.
     *                                                   Available rules include:
     *                                                   - trim
     *                                                   - ltrim
     *                                                   - rtrim
     *                                                   - lower, lowercase
     *                                                   - upper, uppercase
     *                                                   - capitalize
     *                                                   - array
     *                                                   - string
     *                                                   - int, integer
     *                                                   - float
     *                                                   - bool, boolean
     *                                                   - strip_tags
     *                                                   - strip_spaces
     *                                                   - remove_extra_spaces
     *                                                   - alphanumeric
     *                                                   - alpha
     *                                                   - numeric
     *                                                   - deepClean
     *                                                   - filename
     *                                                   - uuid
     *                                                   - slug
     *                                                   - email
     *                                                   - url
     *                                                   - phone
     *                                                   - null_if_empty
     *                                                   - empty_if_null
     *                                                   - version
     *                                                   - html:1-5
     *                                                   - max_length, maxlen
     *                                                   - min_length, minlen
     *                                                   - replace
     *                                                   - regex
     *                                                   - remove
     *                                                   - pad_left
     *                                                   - pad_right
     *                                                   - round
     *                                                   - default
     *                                                   - in
     *                                                   - not_in
     *                                                   - between
     *
     * @param array<string, mixed>                $options
     *
     * @return static
     */
    public static function make (array $data, array $rules, array $options = []) : static {
        return new static($data, $rules, $options);
    }


    public function sanitize () : array {
        $sanitized = $this->data;

        // Pre-compile patterns for performance
        $this->preCompilePatterns();

        foreach ( $this->rules as $pattern => $ruleSet ) {
            $resolvedPaths = $this->resolvePattern($sanitized, $pattern);
            if ( $this->enableBulkMode && count($resolvedPaths) > 10 ) {
                $this->processBulk($sanitized, $resolvedPaths, $ruleSet);
            } else {
                $this->processIndividual($sanitized, $resolvedPaths, $ruleSet);
            }
        }

        return $sanitized;
    }

    private function preCompilePatterns () : void {
        foreach ( $this->rules as $pattern => $rules ) {
            if ( !isset(self::$compiledPatterns[$pattern]) ) {
                self::$compiledPatterns[$pattern] = $this->compilePattern($pattern);
            }
        }
    }

    private function compilePattern (string $pattern) : array {
        return [
            'segments'      => explode('.', $pattern),
            'has_wildcards' => str_contains($pattern, '*'),
            'is_simple'     => !str_contains($pattern, '*') && !str_contains($pattern, '[]')
        ];
    }

    private function processBulk (array &$sanitized, array $paths, mixed $ruleSet) : void {
        $values = [];
        foreach ( $paths as $path ) {
            $values[$path] = Arr::get($sanitized, $path);
        }

        $sanitizedValues = $this->applySanitizerRulesBulk($values, $ruleSet);

        foreach ( $sanitizedValues as $path => $value ) {
            Arr::set($sanitized, $path, $value);
        }
    }

    private function processIndividual (array &$sanitized, array $paths, mixed $ruleSet) : void {
        foreach ( $paths as $path ) {
            $value          = Arr::get($sanitized, $path);
            $sanitizedValue = $this->applySanitizerRules($value, $ruleSet);
            Arr::set($sanitized, $path, $sanitizedValue);
        }
    }

    private function resolvePattern (array $data, string $pattern) : array {
        $compiled = self::$compiledPatterns[$pattern];
        if ( $compiled['is_simple'] ) {
            return Arr::has($data, $pattern) ? [$pattern] : [];
        }

        return $this->expandWildcardPattern($data, $compiled['segments']);
    }

    private function expandWildcardPattern (array $data, array $segments, string $prefix = '') : array {
        if ( empty($segments) ) {
            return [$prefix];
        }

        $segment = array_shift($segments);

        if ( $segment === '*' ) {
            if ( !is_array($data) ) {
                return [];
            }

            $paths = [];
            foreach ( $data as $key => $value ) {
                $newPrefix = $prefix === '' ? $key : "$prefix.$key";
                if ( is_array($value) ) {
                    $paths = array_merge($paths, $this->expandWildcardPattern($value, $segments, $newPrefix));
                } else {
                    $paths = array_merge($paths, [$newPrefix]);
                }
            }

            return $paths;
        }

        if ( str_ends_with($segment, '[]') ) {
            $arrayKey = substr($segment, 0, -2);
            if ( isset($data[$arrayKey]) && is_array($data[$arrayKey]) ) {
                $paths = [];
                foreach ( array_keys($data[$arrayKey]) as $index ) {
                    $newPrefix = $prefix === '' ? "$arrayKey.$index" : "$prefix.$arrayKey.$index";
                    if ( is_array($data[$arrayKey][$index]) ) {
                        $paths = array_merge($paths, $this->expandWildcardPattern($data[$arrayKey][$index] ?? [], $segments, $newPrefix));
                    } else {
                        $paths = array_merge($paths, [$newPrefix]);
                    }
                }

                return $paths;
            }

            return [];
        }

        if ( array_key_exists($segment, $data) ) {
            $newPrefix = $prefix === '' ? $segment : "$prefix.$segment";
            if ( is_array($data[$segment]) ) {
                return $this->expandWildcardPattern($data[$segment], $segments, $newPrefix);
            } else {
                return [$newPrefix];
            }
        }

        return [];
    }

    private function applySanitizerRulesBulk (array $values, mixed $ruleSet) : array {
        $rules   = $this->normalizeRules($ruleSet);
        $results = [];

        foreach ( $values as $path => $value ) {
            $results[$path] = $this->applyRulesSequence($value, $rules);
        }

        return $results;
    }

    private function applySanitizerRules (mixed $value, mixed $ruleSet) : mixed {
        $rules = $this->normalizeRules($ruleSet);

        return $this->applyRulesSequence($value, $rules);
    }

    private function normalizeRules (mixed $ruleSet) : array {
        if ( is_string($ruleSet) ) {
            return explode('|', $ruleSet);
        }

        return is_array($ruleSet) ? $ruleSet : [$ruleSet];
    }

    private function applyRulesSequence (mixed $value, array $rules) : mixed {

        foreach ( $rules as $rule ) {
            $value = $this->applyRule($value, $rule);
        }

        return $value;
    }

    private function applyRule (mixed $value, mixed $rule) : mixed {
        // Handle rule with parameters
        if ( is_string($rule) && str_contains($rule, ':') ) {
            [$ruleName, $params] = explode(':', $rule, 2);

            return $this->applyParameterizedRule($value, $ruleName, $params);
        }

        // Handle simple string rules
        return $this->applySimpleRule($value, $rule);
    }

    private function applySimpleRule (mixed $value, string|Closure $rule) : mixed {
        return match ($rule) {
            'trim' => is_string($value) ? trim($value) : $value,
            'ltrim' => is_string($value) ? ltrim($value) : $value,
            'rtrim' => is_string($value) ? rtrim($value) : $value,
            'lowercase', 'lower' => is_string($value) ? mb_strtolower(trim($value)) : $value,
            'uppercase', 'upper' => is_string($value) ? mb_strtoupper(trim($value)) : $value,
            'capitalize' => is_string($value) ? mb_convert_case(trim($value), MB_CASE_TITLE) : $value,
            'array' => is_array($value) ? $value : [],
            'string' => is_string($value) ? $value : (string) $value,
            'integer', 'int' => is_numeric($value) ? (int) $value : 0,
            'float' => is_numeric($value) ? (float) $value : 0.0,
            'boolean', 'bool' => (bool) $value,
            'strip_tags' => is_string($value) ? strip_tags($value) : $value,
            'strip_spaces' => is_string($value) ? preg_replace('/\s+/', ' ', trim($value)) : $value,
            'remove_extra_spaces' => is_string($value) ? preg_replace('/\s{2,}/', ' ', trim($value)) : $value,
            'alphanumeric' => is_string($value) ? preg_replace('/[^\p{Arabic}A-Za-z0-9\s\-\_\.\[\]\(\)]+/u', '', $value) : $value,
            'alpha' => is_string($value) ? preg_replace('/[^A-Za-z]/', '', $value) : $value,
            'numeric' => is_string($value) ? preg_replace('/[^0-9]/', '', $value) : $value,
            'deepClean' => is_string($value) ? $this->sanitizeDeep($value) : $value,
            'filename' => $this->sanitizeFilename($value),
            'uuid' => $this->sanitizeUuid($value),
            'slug' => $this->createSlug($value),
            'email' => $this->sanitizeEmail($value),
            'url' => $this->sanitizeUrl($value),
            'phone' => $this->sanitizePhone($value),
            'null_if_empty' => empty($value) ? null : $value,
            'empty_if_null' => $value === null ? '' : $value,
            'version' => $this->sanitizeVersion($value),
            default => $this->finalStep($value, $rule),
        };
    }

    private function finalStep (mixed $value, string|Closure $rule) {
        if ( is_callable($rule) ) {
            return $rule($value);
        }

        return $value;
    }

    private function applyParameterizedRule (mixed $value, string|Closure $ruleName, string $params) : mixed {


        return match ($ruleName) {
            'html' => $this->sanitizeHtml($value, $params),
            'max_length', 'maxlen' => is_string($value) ? mb_substr($value, 0, (int) $params) : $value,
            'min_length', 'minlen' => is_string($value) && mb_strlen($value) < (int) $params ? str_pad($value, (int) $params) : $value,
            'replace' => $this->replaceRule($value, $params),
            'regex' => $this->regexRule($value, $params),
            'remove' => is_string($value) ? str_replace($params, '', $value) : $value,
            'pad_left' => is_string($value) ? str_pad($value, (int) $params, ' ', STR_PAD_LEFT) : $value,
            'pad_right' => is_string($value) ? str_pad($value, (int) $params, ' ', STR_PAD_RIGHT) : $value,
            'round' => is_numeric($value) ? round((float) $value, (int) $params) : $value,
            'default' => $value ?? $params,
            'in' => $this->inRule($value, $params),
            'not_in' => $this->notInRule($value, $params),
            'between' => $this->betweenRule($value, $params),
            default => $this->finalStep($value, $ruleName),
        };
    }

    // Specialized sanitization methods
    private function sanitizeDeep (string $value) : string {
        // Remove all HTML tags
        $value = strip_tags($value);

        // Remove dangerous characters but keep basic punctuation
        $value = preg_replace('/[^\p{L}\p{N}\s\-_.,:;!?()@#$%]/u', '', $value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private function sanitizePhone (?string $phone) : string {
        if ( !is_string($phone) ) {
            return '';
        }

        // Keep only digits, plus, and hyphens
        return preg_replace('/[^0-9+\-]/', '', $phone);
    }

    private function sanitizeHtml (mixed $value, string $level) : string|array|object {
        if ( is_scalar($value) ) {
            return $this->sanitizeHtmlProcess($value, $level);
        }
        if ( is_array($value) ) {
            $data = [];
            foreach ( $value as $key => $item ) {
                $data[$key] = $this->sanitizeHtml($item, $level);
            }

            return $data;
        }
        if ( is_object($value) ) {
            $data = new \stdClass();
            foreach ( $value as $key => $item ) {
                $data->{$key} = $this->sanitizeHtml($item, $level);
            }

            return $data;
        }
    }

    private function sanitizeHtmlProcess (string $value, string $level) {
        if ( !is_string($value) ) {
            return '';
        }

        $levelInt = (int) $level;
        $cacheKey = "level_$levelInt";

        if ( !isset(self::$purifierCache[$cacheKey]) ) {
            self::$purifierCache[$cacheKey] = $this->createPurifier($levelInt);
        }

        // Remove inline event handlers first
        $value = preg_replace('/\s+on\w+="[^"]*"/i', '', $value);

        return trim(self::$purifierCache[$cacheKey]->purify($value));
    }



    private function createPurifier (int $level) : HTMLPurifier {
        $config = HTMLPurifier_Config::createDefault();

        // Security settings
        $config->set('HTML.SafeObject', false);
        $config->set('HTML.SafeEmbed', false);
        $config->set('HTML.ForbiddenElements', ['script', 'style', 'object', 'embed', 'meta', 'link']);
        $config->set('HTML.SafeIframe', $level <= 2);
        $config->set('HTML.SafeScripting', []);

        //Security links
        $config->set('URI.MakeAbsolute', false);

        // Css
        $config->set('CSS.AllowTricky', false);
        $config->set('CSS.Trusted', false);

        //Cache version
        $config->set('HTML.DefinitionID', "sanitizer-level-$level");
        $config->set('HTML.DefinitionRev', 2);


        $config->set('HTML.ForbiddenElements', ['script', 'style', 'object', 'embed', 'meta', 'link']);
        $config->set('URI.SafeIframeRegexp', '#^https?://#');
        //  $config->set('URI.SafeIframeRegexp', '#^https?://(www\.youtube\.com/embed/|player\.vimeo\.com/video/)#');


        $allowedTags = $this->getAllowedTags($level);

        if ( !empty($allowedTags) ) {
            $config->set('HTML.Allowed', $this->buildAllowedString($allowedTags));
            $this->addCustomElements($config);
        } else {
            $config->set('HTML.Allowed', '');
        }


        return new HTMLPurifier($config);
    }

    private function addCustomElements (HTMLPurifier_Config $config) : void {

        if ( $def = $config->maybeGetRawHTMLDefinition() ) {


            $def->addElement('audio', 'Block', 'Optional: #PCDATA | source | track', 'Common', [
                'controls' => 'Bool',
                'autoplay' => 'Bool',
                'loop'     => 'Bool',
                'muted'    => 'Bool',
                'preload'  => 'Enum#auto,metadata,none',
                'src'      => 'URI',
            ]);

            // تگ video
            $def->addElement('video', 'Block', 'Optional: #PCDATA | source | track', 'Common', [
                'controls' => 'Bool',
                'autoplay' => 'Bool',
                'loop'     => 'Bool',
                'muted'    => 'Bool',
                'poster'   => 'URI',
                'preload'  => 'Enum#auto,metadata,none',
                'width'    => 'Length',
                'height'   => 'Length',
                'src'      => 'URI',
            ]);

            $def->addElement('source', 'Block', 'Empty', 'Common', [
                'src'  => 'URI',
                'type' => 'Text',
            ]);

            $def->addElement('track', 'Block', 'Empty', 'Common', [
                'src'     => 'URI',
                'kind'    => 'Enum#subtitles,captions,descriptions,chapters,metadata',
                'srclang' => 'Text',
                'label'   => 'Text',
                'default' => 'Bool',
            ]);

            $def->addElement('svg', 'Block', 'Flow', 'Common', [
                'width'   => 'Length',
                'height'  => 'Length',
                'viewBox' => 'Text',
                'xmlns'   => 'Text',
                'fill'    => 'Text',
                'stroke'  => 'Text',
            ]);

            $def->addElement('path', 'Block', 'Empty', 'Common', [
                'd'      => 'Text',
                'fill'   => 'Text',
                'stroke' => 'Text',
            ]);

            $def->addElement('circle', 'Block', 'Empty', 'Common', [
                'cx'     => 'Text',
                'cy'     => 'Text',
                'r'      => 'Text',
                'fill'   => 'Text',
                'stroke' => 'Text',
            ]);

        }
    }

    private function getAllowedTags (int $level) : array {
        return match ($level) {
            1 => [
                'a'          => [
                    'class'  => true,
                    'style'  => true,
                    'href'   => true,
                    'title'  => true,
                    'target' => true,
                    'rel'    => true
                ],
                'b'          => ['class' => true, 'style' => true],
                'strong'     => ['class' => true, 'style' => true],
                'i'          => ['class' => true, 'style' => true],
                'em'         => ['class' => true, 'style' => true],
                'u'          => ['class' => true, 'style' => true],
                'del'        => ['class' => true, 'style' => true],
                'ins'        => ['class' => true, 'style' => true],
                'p'          => ['class' => true, 'style' => true],
                'br'         => ['class' => true, 'style' => true],
                'ul'         => ['class' => true, 'style' => true],
                'ol'         => ['class' => true, 'style' => true],
                'li'         => ['class' => true, 'style' => true],
                'h1'         => ['class' => true, 'style' => true],
                'h2'         => ['class' => true, 'style' => true],
                'h3'         => ['class' => true, 'style' => true],
                'h4'         => ['class' => true, 'style' => true],
                'h5'         => ['class' => true, 'style' => true],
                'h6'         => ['class' => true, 'style' => true],
                'blockquote' => ['class' => true, 'style' => true],
                'code'       => ['class' => true, 'style' => true],
                'pre'        => ['class' => true, 'style' => true],
                'img'        => [
                    'class'  => true,
                    'style'  => true,
                    'src'    => true,
                    'alt'    => true,
                    'title'  => true,
                    'width'  => true,
                    'height' => true
                ],
                'table'      => ['class' => true, 'style' => true],
                'tbody'      => ['class' => true, 'style' => true],
                'tr'         => ['class' => true, 'style' => true],
                'th'         => ['class' => true, 'style' => true],
                'td'         => ['class' => true, 'style' => true],
                'caption'    => ['class' => true, 'style' => true],
                'small'      => ['class' => true, 'style' => true],
                'hr'         => ['class' => true, 'style' => true],

                'audio'  => [
                    'class'    => true,
                    'style'    => true,
                    'controls' => true,
                    'autoplay' => true,
                    'loop'     => true,
                    'muted'    => true,
                    'src'      => true
                ],
                'video'  => [
                    'class'    => true,
                    'style'    => true,
                    'controls' => true,
                    'autoplay' => true,
                    'loop'     => true,
                    'muted'    => true,
                    'width'    => true,
                    'height'   => true,
                    'src'      => true,
                    'poster'   => true
                ],
                'source' => [
                    'src'  => true,
                    'type' => true
                ],
                'span'   => ['class' => true, 'style' => true],
                'div'    => ['class' => true, 'style' => true],
            ],
            2 => [
                'a'          => ['class' => true, 'style' => true, 'href' => true, 'title' => true],
                'b'          => ['class' => true, 'style' => true,],
                'strong'     => ['class' => true, 'style' => true,],
                'i'          => ['class' => true, 'style' => true,],
                'em'         => ['class' => true, 'style' => true,],
                'p'          => ['class' => true, 'style' => true,],
                'ul'         => ['class' => true, 'style' => true,],
                'ol'         => ['class' => true, 'style' => true,],
                'li'         => ['class' => true, 'style' => true,],
                'blockquote' => ['class' => true, 'style' => true,],
                'code'       => ['class' => true, 'style' => true,],
                'img'        => ['class' => true, 'style' => true, 'src' => true, 'alt' => true],
                'table'      => ['class' => true, 'style' => true,],
                'tr'         => ['class' => true, 'style' => true,],
                'th'         => ['class' => true, 'style' => true,],
                'td'         => ['class' => true, 'style' => true,],
                'hr'         => ['class' => true, 'style' => true,],
                'audio'      => ['class' => true, 'style' => true, 'controls' => true, 'src' => true],
                'video'      => [
                    'class'    => true,
                    'style'    => true,
                    'controls' => true,
                    'src'      => true,
                    'width'    => true,
                    'height'   => true,
                    'loop'     => true,
                    'poster'   => true
                ],
            ],
            3 => [
                'a'          => ['class' => true, 'style' => true, 'href' => true],
                'b'          => ['class' => true, 'style' => true,],
                'strong'     => ['class' => true, 'style' => true,],
                'i'          => ['class' => true, 'style' => true,],
                'p'          => ['class' => true, 'style' => true,],
                'ul'         => ['class' => true, 'style' => true,],
                'ol'         => ['class' => true, 'style' => true,],
                'li'         => ['class' => true, 'style' => true,],
                'blockquote' => ['class' => true, 'style' => true,],
                'code'       => ['class' => true, 'style' => true,],
                'img'        => ['class' => true, 'style' => true, 'src' => true, 'alt' => true],
                'hr'         => ['class' => true, 'style' => true,],
            ],
            4 => [
                'a'      => ['class' => true, 'style' => true, 'href' => true],
                'b'      => ['class' => true, 'style' => true],
                'strong' => ['class' => true, 'style' => true],
                'em'     => ['class' => true, 'style' => true],
                'ul'     => ['class' => true, 'style' => true],
                'li'     => ['class' => true, 'style' => true],
                'ol'     => ['class' => true, 'style' => true],
                'i'      => ['class' => true, 'style' => true],
                'p'      => ['class' => true, 'style' => true],
            ],
            default => [] // Strip all HTML
        };
    }

    private function buildAllowedString (array $tags) : string {
        $allowed = [];

        foreach ( $tags as $tag => $attributes ) {
            if ( !is_string($tag) ) {
                continue;
            }
            if ( is_array($attributes) && !empty($attributes) ) {
                $attrs     = implode('|', array_keys($attributes));
                $allowed[] = $tag . '[' . $attrs . ']';
            } else {
                $allowed[] = $tag;
            }
        }

        return implode(',', $allowed);
    }



    private function inRule (mixed $value, string $params) : mixed {
        $allowed = explode(',', $params);

        return in_array($value, $allowed) ? $value : $allowed[0] ?? null;
    }

    private function notInRule (mixed $value, string $params) : mixed {
        $forbidden = explode(',', $params);

        return in_array($value, $forbidden) ? null : $value;
    }

    private function betweenRule (mixed $value, string $params) : mixed {
        if ( !is_numeric($value) ) {
            return $value;
        }
        $type = gettype($value);

        [$min, $max] = explode(',', $params, 2);
        $num = (float) $value;
        $min = (float) $min;
        $max = (float) $max;
        if ( $type === 'integer' ) {
            return (int) max($min, min($max, $num));
        }

        return max($min, min($max, $num));
    }

    // Batch processing for large datasets
    public function sanitizeBatch (array $datasets, array $rules) : array {
        return array_map(fn($data) => static::make($data, $rules)->sanitize(), $datasets);
    }

    /**
     * validate and return
     *
     * @param array $validationRules
     *
     * @return \Illuminate\Validation\Validator
     */
    public function sanitizeAndValidate (array $validationRules = []) : \Illuminate\Validation\Validator {
        $sanitized = $this->sanitize();

        return Validator::make($sanitized, $validationRules);
    }

    // Clear static caches (useful for testing or memory management)
    public static function clearCache () : void {
        self::$purifierCache    = [];
        self::$compiledPatterns = [];
    }

    private function toAscii (string $value) : string {
        $value = mb_strtolower($value);

        $persianToEnglish = [
            'ا' => 'a',
            'ب' => 'b',
            'پ' => 'p',
            'ت' => 't',
            'ث' => 's',
            'ج' => 'j',
            'چ' => 'ch',
            'ح' => 'h',
            'خ' => 'kh',
            'د' => 'd',
            'ذ' => 'z',
            'ر' => 'r',
            'ز' => 'z',
            'ژ' => 'zh',
            'س' => 's',
            'ش' => 'sh',
            'ص' => 's',
            'ض' => 'z',
            'ط' => 't',
            'ظ' => 'z',
            'ع' => 'a',
            'غ' => 'gh',
            'ف' => 'f',
            'ق' => 'gh',
            'ک' => 'k',
            'گ' => 'g',
            'ل' => 'l',
            'م' => 'm',
            'ن' => 'n',
            'و' => 'v',
            'ه' => 'h',
            'ی' => 'y',
            'ء' => '',
            'أ' => 'a',
            'ؤ' => 'v',
            'ئ' => 'y',
            'إ' => 'e',
            'ي' => 'y',
            'ة' => 'h',
            'َ' => '',
            'ُ' => '',
            'ِ' => '',
            'ّ' => '',
            'ً' => '',
            'ٌ' => '',
            'ٍ' => '',
            'ٰ' => ''
        ];

        return strtr($value, $persianToEnglish);
    }

    private function isValidUuid (string $uuid) : bool {
        return Uuid::isValid($uuid);
    }

}
