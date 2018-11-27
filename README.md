laravel-scout-solr
==================

## Introduction

Laravel Scout or Solr Engine

## Documentation

Official Documentation for Scout can be found on the [Laravel website](https://laravel.com/docs/master/scout).


Добавте в ваш `AppServiceProvider` реализацию инстанса `SolrSearchEngine`

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Fobia\Solrquent\ScoutSolr\SolrSearchEngine;
// ...

class AppServiceProvider extends ServiceProvider
{
    // ...
    
    public function register()
    {
        // ...
        $this->app->singleton(SolrSearchEngine::class, function ($app) {
            return new SolrSearchEngine($solrClient, $onCommitHandle);
        });
    }
}
```
, тут `$solrClient` - это клиет подключения к Solr, a `$onCommitHandle` функция обратного вызова 
перед отправкой запроса update в Solr.
`$onCommitHandle(\Solarium\QueryType\Update\Query\Query $query);`

В нее вы установить нужно ли отправлять команду `commit`


## License

Laravel Scout is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT)

https://github.com/pxslip/laravel-scout-solr/blob/master/src/Builder.php
https://github.com/grey-dev-0/laravel-scout-solr/blob/master/src/SolrEngine.php
https://github.com/jeroenherczeg/laravel-scout-solr/blob/master/src/SolrProvider.php
