<?php
namespace Lab;

use Exception;

final class Settings
{
    public static function required(string $key): string
    {
        $val = getenv($key);
        if ($val === false || $val === '') {
            throw new Exception("Missing required environment variable: {$key}");
        }
        return $val;
    }

    public static function queue(): array
    {
        $config = [
            'region'  => getenv('AWS_REGION') ?: 'ap-southeast-2',
            'version' => 'latest'
        ];

      
        if (getenv('AWS_ACCESS_KEY_ID') && getenv('AWS_SECRET_ACCESS_KEY')) {
            $config['credentials'] = [
                'key'    => getenv('AWS_ACCESS_KEY_ID'),
                'secret' => getenv('AWS_SECRET_ACCESS_KEY')
            ];
        } elseif (getenv('AWS_PROFILE')) {
            $config['profile'] = getenv('AWS_PROFILE');
        }

        return $config;
    }
}