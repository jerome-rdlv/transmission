<?php

namespace Rdlv\JDanger;

use Exception;
use getID3;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class Meta
{
    const MD_LENGTH = 65536; // 64 KiB

    const TITLE = 'title';
    const ALBUM = 'album';
    const ARTIST = 'artist';
    const YEAR = 'year';
    const TRACK = 'track_number';
    const GENRE = 'genre';
    const PUBLISHER = 'publisher';
    const BITRATE = 'bitrate';
    const LENGTH_FORMATTED = 'length_formatted';
    const LENGTH = 'length';
    const DATA_FORMAT = 'dataformat';
    const MIME_TYPE = 'mime_type';
    const IMAGE = 'image';
    const FILESIZE = 'filesize';
    
    const IMAGE_DATA = 'data';
    const IMAGE_EXTENSION = 'extension';
    const IMAGE_MIME = 'mime';

    private static $fields = [
        self::TITLE            => [
            'tags/id3v2/title/0',
            'tags/id3v1/title/0',
        ],
        self::ALBUM            => [
            'tags/id3v2/album/0',
            'tags/id3v1/album/0',
        ],
        self::ARTIST           => [
            'tags/id3v2/artist/0',
            'tags/id3v1/artist/0',
        ],
        self::YEAR             => [
            'tags/id3v2/year/0',
            'tags/id3v1/year/0',
        ],
        self::TRACK            => [
            'tags/id3v2/track_number/0',
            'tags/id3v1/track/0',
        ],
        self::GENRE            => [
            'tags/id3v2/genre/0',
            'tags/id3v1/genre/0',
        ],
        self::PUBLISHER        => [
            'tags/id3v2/publisher/0',
        ],
        self::BITRATE          => [
            'bitrate',
        ],
        self::LENGTH_FORMATTED => [
            'playtime_string',
        ],
        self::LENGTH           => [
            'playtime_seconds',
        ],
        self::PUBLISHER        => [
            'tags/id3v2/publisher/0',
        ],
        self::PUBLISHER        => [
            'tags/id3v2/publisher/0',
        ],
        self::DATA_FORMAT      => [
            'fileformat',
        ],
        self::MIME_TYPE        => [
            'mime_type',
        ],
        self::FILESIZE         => [
            'filesize',
        ],
    ];
    
    const IMG_DATA_PATH = [
        'comments/picture/0/data',
    ];
    const IMG_TYPE_PATH = [
        'comments/picture/0/image_mime',
    ];
    
    private static $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif'
    ];
    
    public static function getMeta($url)
    {
        if (!class_exists('getID3')) {
            require ABSPATH . WPINC . '/ID3/getid3.php';
        }

        $ID3 = new getID3();
        $raw = null;

        if (strpos($url, get_home_url()) === 0) {
            // local file
            $path = str_replace(get_home_url(), get_home_path(), $url);
            if (file_exists($path)) {
                $raw = $ID3->analyze($path);
            }
        }

        if (!$raw) {
            // remote file, must copy before analyse

            try {
                $client  = new \GuzzleHttp\Client();
                
                $headers = [];

                // handle authorization for local file
                $aHeaders = apache_request_headers();
                if ($aHeaders && isset($aHeaders['Authorization']) && strpos($url, get_home_url()) === 0) {
                    $headers['Authorization'] = $aHeaders['Authorization'];
                }
                
                $request = new Request('HEAD', $url, $headers);

                /** @var Response $response */
                $response = $client->send($request);

                $length = $response->getHeader('Content-Length');
                $length = is_array($length) ? $length[0] : $length;
                $local_path = tempnam('/tmp', 'getID3');
                $request = $request->withMethod('GET');

                $ranges = array();
                $ranges[] = '0-'. ($length > self::MD_LENGTH) ? self::MD_LENGTH : '';
                if ($length > 2 * self::MD_LENGTH) {
                    $ranges[] = ($length - self::MD_LENGTH) .'-';
                }

                foreach ($ranges as $range) {
                    $request = $request->withHeader('Range', 'bytes=' . $range);
                    $response = $client->send($request);
                    if ($response->getBody()->isReadable()) {
                        file_put_contents($local_path, $response->getBody()->getContents());
                        $raw = $ID3->analyze($local_path);
                        unlink($local_path);
                        break;
                    }
                }
            }
            catch (Exception $e) {
                //echo "Exception ". $e->getMessage() ."\n";
            }
        }

        if ($raw) {
            // construct clean meta structure
            $meta = [];

            foreach (self::$fields as $field => $paths) {
                $meta[$field] = self::getFirstByPaths($raw, $paths);
            }

            $imgData = self::getFirstByPaths($raw, self::IMG_DATA_PATH);
            $imgType = self::getFirstByPaths($raw, self::IMG_TYPE_PATH);
            if ($imgData) {
                $extension = array_key_exists($imgType, self::$extensions) ? self::$extensions[$imgType] : 'jpg';
                $meta[self::IMAGE] = [
                    self::IMAGE_DATA => $imgData,
                    self::IMAGE_MIME => $imgType,
                    self::IMAGE_EXTENSION => $extension,
                ];
            }
            return $meta;
        }
        return null;
    }

    private static function getFirstByPaths($data, $paths)
    {
        foreach ($paths as $path) {
            $value = self::getByPath($data, $path);
            if ($value != null) {
                return $value;
            }
        }
        return null;
    }

    private static function getByPath(&$data, $path)
    {
        if (is_array($data)) {
            if (!is_array($path)) {
                $path = explode('/', $path);
            }
            $key = array_shift($path);
            if (array_key_exists($key, $data)) {
                if ($path) {
                    return self::getByPath($data[$key], $path);
                }
                else {
                    return $data[$key];
                }
            }
        }
        return null;
    }
} 
