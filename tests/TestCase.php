<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $application = parent::createApplication();
        $driver = $application['config']->get('database.default');
        $database = $application['config']->get('database.connections.'.$driver.'.database');

        if ($driver !== 'mysql' || $database !== 'moc_blog_test') {
            throw new \LogicException('Kiểm thử chỉ được chạy trên MySQL database moc_blog_test. Tuyệt đối không dùng database website.');
        }

        return $application;
    }
}
