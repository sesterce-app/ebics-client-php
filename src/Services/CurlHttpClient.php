<?php

namespace AndrewSvirin\Ebics\Services;

use AndrewSvirin\Ebics\Contracts\HttpClientInterface;
use AndrewSvirin\Ebics\Models\Http\Request;
use AndrewSvirin\Ebics\Models\Http\Response;
use RuntimeException;

/**
 * Curl Http client.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class CurlHttpClient extends HttpClient implements HttpClientInterface
{
    /**
     * @inheritDoc
     */
    public function post(string $url, Request $request): Response
    {
        $body = $request->getContent();

        // echo '<pre>' . var_export($url, true) . '</pre>';
        // echo '<pre>' . var_export($body, true) . '</pre>';

        $ch = curl_init($url);
        if (false === $ch) {
            throw new RuntimeException('Can not create curl.');
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: ' . self::CONTENT_TYPE,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Inspect CURL config before exec
        // $info_before = curl_getinfo($ch);
        // echo '<pre>' . var_export($info_before, true) . '</pre>';

        $contents = curl_exec($ch);
        if ($contents === false) {
            throw new \Exception('Curl error: ' . curl_error($ch));
        }

        // Inspect CURL config and response after exec
        // $info_after = curl_getinfo($ch);
        // echo '<pre>' . var_export($info_after, true) . '</pre>';

        curl_close($ch);

        if (!is_string($contents)) {
            throw new RuntimeException('Response is not a string.');
        }

        return $this->createResponse($contents);
    }
}
