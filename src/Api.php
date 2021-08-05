<?php

namespace think;

class Api
{
    private $app;

    public function __construct(App $app)
    {
        $viewPath = __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        dump($app->view);
    }
}