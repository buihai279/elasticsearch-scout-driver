<?php

declare(strict_types=1);

namespace JeroenG\Explorer;

use Elastic\Elasticsearch\Client;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use JeroenG\Explorer\Application\DocumentAdapterInterface;
use JeroenG\Explorer\Application\IndexAdapterInterface;
use JeroenG\Explorer\Domain\Aggregations\AggregationSyntaxInterface;
use JeroenG\Explorer\Domain\IndexManagement\IndexConfigurationRepositoryInterface;
use JeroenG\Explorer\Infrastructure\Console\ElasticSearch;
use JeroenG\Explorer\Infrastructure\Console\ElasticUpdate;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticClientBuilder;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticClientFactory;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticDocumentAdapter;
use JeroenG\Explorer\Infrastructure\Elastic\ElasticIndexAdapter;
use JeroenG\Explorer\Infrastructure\IndexManagement\ElasticIndexConfigurationRepository;
use JeroenG\Explorer\Infrastructure\Scout\ElasticEngine;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;

class ExplorerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
        
        $this->app->when(ElasticClientFactory::class)
            ->needs(Client::class)
            ->give(fn() => ElasticClientBuilder::fromConfig(config())->build());
        
        $this->app->singleton(IndexAdapterInterface::class, ElasticIndexAdapter::class);
        
        $this->app->singleton(DocumentAdapterInterface::class, ElasticDocumentAdapter::class);
        
        $this->app->singleton(
            IndexConfigurationRepositoryInterface::class,
            fn() => new ElasticIndexConfigurationRepository(
                config('explorer.indexes') ?? [],
                config('explorer.prune_old_aliases'),
            )
        );
        
        resolve(EngineManager::class)->extend('elastic', fn(Application $app) => new ElasticEngine(
            $app->make(IndexAdapterInterface::class),
            $app->make(DocumentAdapterInterface::class),
            $app->make(IndexConfigurationRepositoryInterface::class)
        ));
        
        Builder::macro('must', function ($must) {
            $this->must[] = $must;
            return $this;
        });
        
        Builder::macro('should', function ($should) {
            $this->should[] = $should;
            return $this;
        });
        
        Builder::macro('filter', function ($filter) {
            $this->filter[] = $filter;
            return $this;
        });
        
        Builder::macro('field', function (string $field) {
            $this->fields[] = $field;
            return $this;
        });
        
        Builder::macro('fields', function (array $fields) {
            $this->fields = $fields;
            
            return $this;
        });
        
        Builder::macro('newCompound', function ($compound) {
            $this->compound = $compound;
            return $this;
        });
        
        Builder::macro('aggregation', function (string $name, AggregationSyntaxInterface $aggregation) {
            $this->aggregations[$name] = $aggregation;
            return $this;
        });
    }
    
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/explorer.php', 'explorer');
    }
    
    public function provides(): array
    {
        return ['explorer'];
    }
    
    protected function bootForConsole(): void
    {
        $this->publishes([
            __DIR__ . '/../config/explorer.php' => config_path('explorer.php'),
        ], 'explorer.config');
        
        $this->commands([
            ElasticSearch::class,
            ElasticUpdate::class,
        ]);
    }
}
