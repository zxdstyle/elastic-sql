<?php

namespace Zxdstyle\ElasticSql;

use Zxdstyle\ElasticSql\Facades\Elastic as ElasticFacade;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = true;

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->registerConfigs();

        $this->registerPublish();

        $this->app->singleton(Elastic::class, function () {
            return Elastic::builder(config('elastic.config'));
        });
    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return [Elastic::class];
    }

    /**
     * Register the package configs.
     */
    protected function registerConfigs()
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/elastic.php', 'elastic');
    }

    /**
     * Register the publishable files.
     */
    protected function registerPublish()
    {
        $this->publishes([
            dirname(__DIR__) . '/config/elastic.php' => config_path("elastic.php")
        ], 'elastic_config');
    }
}