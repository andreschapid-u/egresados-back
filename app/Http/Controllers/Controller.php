<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Http\JsonResponse;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param string|null $message
     * @param int $code
     * @param array $errors
     *
     * @return JsonResponse
     */
    protected function fail($message = null, $code = 500, $errors = [])
    {
        return response()->json([
            'status' => 'failure',
            'status_code' => $code,
            'message' => $message ? $message : 'Internal Server Error',
            'errors' => $errors,
        ], 200);
        //  isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
    /**
     * @param $data
     * @param array $meta
     * @param int $code
     *
     * @return JsonResponse
     */
    protected function success($data, $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'status_code' => $code,
            'message' => JsonResponse::$statusTexts[$code] = 'OK',
            'data' => $data,
        ],200);
        // ], isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_OK);
    }
    /**
     * @param string|null $message
     * @param int $code
     *
     * @return JsonResponse
     */
    protected function badRequest($message, $code = 400)
    {
        return response()->json([
            'status' => 'Bad Request',
            'status_code' => $code,
            'message' => $message ? $message : 'Bad Request',
        ],200);
        // ], isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_OK);
    }
    /**
     * @param string|null $message
     * @param int $code
     *
     * @return JsonResponse
     */
    protected function notFound($message, $code = 404)
    {
        return response()->json([
            'status' => 'Not Found',
            'status_code' => $code,
            'message' => $message ? $message : 'Not Found',
        ],200);
        // ], isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_OK);
    }
    /**
     * @param string|null $message
     * @param int $code
     *
     * @return JsonResponse
     */
    protected function forbidden($message, $code = 403)
    {
        return response()->json([
            'status' => 'Forbidden',
            'status_code' => $code,
            'message' => $message ? $message : 'Forbidden',
        ],200);
        // ], isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_OK);
    }
    /**
     * @param string|null $message
     * @param int $code
     *
     * @return JsonResponse
     */
    protected function unauthorized($message, $code = 401)
    {
        return response()->json([
            'status' => 'Unauthorized',
            'status_code' => $code,
            'message' => $message ? $message : 'Unauthorized',
        ],200);
        // ], isset(JsonResponse::$statusTexts[$code]) ? $code : JsonResponse::HTTP_OK);
    }
}
