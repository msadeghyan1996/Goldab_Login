<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Validator;

abstract class Controller {
    private static bool $createDocJson = false;

    public static function jsonForPhpDoc () : static {
        self::$createDocJson = true;

        return new static();
    }


    /**
     * Fix Params
     *
     * @param array|string $params
     *
     * @return array
     */
    private static function fixParams (array|string $params = []) : array {
        if ( is_string($params) ) {
            return [
                'message' => $params,
                'data'    => '',
                'notify'  => true,
                'title'   => '',
                'errors'  => [],
            ];
        }

        return [
            'message' => $params['message'] ?? '',
            'data'    => $params['data'] ?? '',
            'notify'  => $params['notify'] ?? true,
            'title'   => $params['title'] ?? '',
            'errors'  => $params['errors'] ?? [],
        ];
    }

    /**
     * Send JSON Response
     */
    private static function json (array $params, int $responseCode = 200) : JsonResponse {
        if ( self::$createDocJson ) {
            exit(formatJsonForPhpDoc($params, $responseCode));
        }

        return response()->json($params, $responseCode);
    }

    /**
     * Send Warning Response
     */
    protected static function warning (array|string $params = [], int $responseCode = 400) : JsonResponse {
        $params = self::fixParams($params);

        $response = [
            'success'    => false,
            'statusType' => 'warning',
            'title'      => $params['title'] ?: 'اخطار',
            'message'    => $params['message'],
            'errors'     => $params['errors'],
            'notify'     => $params['notify'],
        ];

        return self::json($response, $responseCode);
    }

    /**
     * Send Error Response
     */
    protected static function error (array|string $params = [], int $responseCode = 500) : JsonResponse {
        $params = self::fixParams($params);

        $response = [
            'success'    => false,
            'statusType' => 'error',
            'title'      => $params['title'] ?: 'خطا',
            'message'    => $params['message'],
            'errors'     => $params['errors'],
            'notify'     => $params['notify'],
        ];

        return self::json($response, $responseCode);
    }

    /**
     * Send Success Response
     */
    protected static function success (array|string $params = [], int $responseCode = 200) : JsonResponse {
        $params = self::fixParams($params);

        $response = [
            'success'    => true,
            'statusType' => 'success',
            'title'      => $params['title'] ?: 'موفق',
            'message'    => $params['message'],
            'data'       => $params['data'],
            'notify'     => $params['notify'],
        ];

        return self::json($response, $responseCode);
    }

    /**
     * Create Parameter Documentation
     */
    public function createParam (mixed $params = null, string $method = 'post') : void {
        if ( is_null($params) ) {
            $params = request()->all();
        }
        exit(formatJsonParamsForPhpDoc($params, $method));
    }


    /**
     * Validation Response (with detailed errors)
     */
    public static function validationResponse (Validator $validator, bool $isJsonDoc = false) : JsonResponse {
        $errors = $validator->errors()->toArray();

        $response = [
            'message' => 'Validation failed.',
            'errors'  => $errors,
            'notify'  => true,
        ];

        if ( $isJsonDoc ) {
            return self::jsonForPhpDoc()->error($response, 422);
        }

        return self::warning($response, 422);
    }

}
