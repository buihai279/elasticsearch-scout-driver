<?php

declare(strict_types=1);

namespace JeroenG\Explorer\Infrastructure\Elastic;

use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Contracts\Config\Repository;

final class ElasticClientBuilder
{
    private const HOST_KEYS = ['host', 'port', 'scheme'];
    
    public static function fromConfig(Repository $config): ClientBuilder
    {
        $builder = ClientBuilder::create();
    
        $hostConnectionProperties = array_filter(
            $config->get('explorer.connection'),
            static fn($key) => in_array($key, self::HOST_KEYS, true),
            ARRAY_FILTER_USE_KEY
        );
        
        $builder->setHosts(
            [$hostConnectionProperties['scheme'] . '://' . $hostConnectionProperties['host'] . ':' . $hostConnectionProperties['port']]
        );
        
        if ($config->has('explorer.additionalConnections')) {
            $builder->setHosts([$config->get('explorer.connection'), ...$config->get('explorer.additionalConnections')]
            );
        }
        
        if ($config->has('explorer.api')) {
            $builder->setApiKey(
                $config->get('explorer.api.id'),
                $config->get('explorer.api.key')
            );
        }
        
        if ($config->has('explorer.elasticCloudId')) {
            $builder->setElasticCloudId(
                $config->get('explorer.elasticCloudId'),
            );
        }
        
        if ($config->has('explorer.auth')) {
            $builder->setBasicAuthentication(
                $config->get('explorer.auth.username'),
                $config->get('explorer.auth.password')
            );
        }
        
        if ($config->has('explorer.ssl_verification')) {
            $builder->setSSLVerification($config->get('explorer.ssl_verification'));
        }
        
        if ($config->has('explorer.ssl.key')) {
            [$path, $password] = self::getPathAndPassword($config->get('explorer.ssl.key'));
            $builder->setSSLKey($path, $password);
        }
        
        if ($config->has('explorer.ssl.cert')) {
            [$path, $password] = self::getPathAndPassword($config->get('explorer.ssl.cert'));
            $builder->setSSLCert($path, $password);
        }
        
        return $builder;
    }
    
    /**
     * @param array|string $config
     */
    private static function getPathAndPassword(mixed $config): array
    {
        return is_array($config) ? $config : [$config, null];
    }
}
