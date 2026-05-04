<?php


class Response
{

    /**
     * Send a standard json success response
     * @param array $data
     * @param int $statusCode
     * @return void
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-type: application/json');;
        echo json_encode($data);
        exit;
    }

    public static function error(string $msg, int $statusCode = 400): void
    {
        self::json([
            'error' => $msg
        ], $statusCode);
    }

    /**
     * For debugging with more detail
     *
     * @param array $data
     * @param int $statusCode
     * @return void
     */
    public static function errorReason(array $data, int $statusCode = 500): void
    {
        self::json([
            'error' => $data['error']??'',
            'reason' => $data['reason']??null
        ], $statusCode);
    }

}

?>