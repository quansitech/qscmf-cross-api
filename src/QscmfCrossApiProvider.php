<?php

namespace QscmfCrossApi;

use Bootstrap\LaravelProvider;
use Bootstrap\Provider;
use Bootstrap\RegisterContainer;

class QscmfCrossApiProvider implements Provider,LaravelProvider
{

    public function register(){
//        RegisterContainer::registerController('intranetApi','Rest', RestController::class);
    }

    public function registerLara()
    {
        RegisterContainer::registerMigration(__DIR__.'/migrations');
    }

}