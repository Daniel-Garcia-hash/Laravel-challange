<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        // RefreshDatabase mag uitsluitend de afzonderlijke testdatabase wissen.
        if (! $app->environment('testing')) {
            throw new RuntimeException('Tests gestopt: APP_ENV moet testing zijn. Voer php artisan config:clear uit.');
        }

        $connection = $app['db']->connection();

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            || $connection->getDatabaseName() !== 'laravel_challange_test') {
            throw new RuntimeException('Tests gestopt: gebruik alleen MySQL/MariaDB met database laravel_challange_test.');
        }

        $database = $connection->selectOne('SELECT DATABASE() AS database_name');

        if ($database->database_name !== 'laravel_challange_test') {
            throw new RuntimeException('Tests gestopt: de actieve database is niet laravel_challange_test.');
        }

        return $app;
    }
}
