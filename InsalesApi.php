<?php

namespace idsite\insales_api;

use InvalidArgumentException;

/**
 * Description of InsalesApi
 *
 * @author Derzhavin A.
 */
class InsalesApi extends \yii\base\Object {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';

    /**
     * 
     * @var string 
     */
    public $id;

    /**
     *
     * @var string 
     */
    public $password;

    /**
     * домен маназина пример 'shop-40118.myinsales.ru'
     * @var string 
     */
    public $host;

    public function init() {
        if (!$this->id || !$this->password || !$this->host) {
            throw new InvalidArgumentException('Not all fields are defined');
        }
    }

    public function request($url, $method = self::METHOD_GET, $data = null) {
        $url = 'http://' . $this->id . ':' . $this->password . '@' . $this->host . '/' . $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data) {
            if ($method !== self::METHOD_GET) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                $url.='?' . http_build_query($data);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);

        $result = curl_exec($ch);

        if (!$result && ($codeError = curl_errno($ch))) {
            throw new \yii\base\Exception('curl error: ' . $codeError . ': ' . curl_error($ch));
        }
        curl_close($ch);

        $g = strpos($result, "\r\n\r\n");
        $header = substr($result, 0, $g);

        if (strncmp($result, 'HTTP/1.1 100', 12) === 0) {
            $g = strpos($result, "\r\n\r\n", $g + 4);
            $header = substr($result, 0, $g);
        }

        $body = substr($result, $g + 4);

        return $body;
    }

}
