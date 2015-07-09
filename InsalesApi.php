<?php

namespace idsite\insales_api;

/**
 * Description of InsalesApi
 *
 * @author Derzhavin A.
 */
class InsalesApi {

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
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
    
    /**
     *  пример $options = $insales->request('/admin/option_names.json');
     * @param type $url
     * @param type $method
     * @param type $data
     * @return type
     * @throws \Exception
     * @throws \yii\base\Exception
     */
    public function request($url, $method = self::METHOD_GET, $data = null) {
        if (!$this->id || !$this->password || !$this->host) {
            throw new \Exception('Not all fields are defined');
        }
        $url = 'http://' . $this->id . ':' . $this->password . '@' . $this->host . $url;

        do {
            $ch = curl_init();




            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            if ($data) {
                if ($method !== self::METHOD_GET) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    $ex = pathinfo($url, PATHINFO_EXTENSION);
                    if (strcasecmp($ex, 'json') === 0) {
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8", 'Expect:'));
                    }
                } else {
                    $url.='?' . http_build_query($data);
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);


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

            $ok = true;
            // органичение на количество запросов https://wiki.insales.ru/wiki/%D0%9A%D0%B0%D0%BA_%D0%B8%D0%BD%D1%82%D0%B5%D0%B3%D1%80%D0%B8%D1%80%D0%BE%D0%B2%D0%B0%D1%82%D1%8C%D1%81%D1%8F_%D1%81_InSales#.D0.A7.D0.B0.D1.81.D1.82.D0.BE.D1.82.D0.B0_.D0.B7.D0.B0.D0.BF.D1.80.D0.BE.D1.81.D0.BE.D0.B2_.D0.BA_API
            if (strncmp($header, 'HTTP/1.1 503', 12) === 0) {
                if ($i = strpos($header, 'Retry-After:')) {
                    $i+=12;
                    $s = intval(substr($header, $i, strpos($header, "\r", $i) - $i));
                    if ($s) {
                        $ok = false;
                        $s+=5;
                        sleep($s);
                    }
                }
            }
        } while (!$ok);




        $body = substr($result, $g + 4);

        return $body;
    }

}
