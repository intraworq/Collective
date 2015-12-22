<?php
use Collective\Actions\HelloWorldAction;
use Collective\Actions\HelloWorldJsonAction;
use Collective\Middleware\LoggerMiddleware;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Uri;

/**
 * Created by PhpStorm.
 * User: Glenn
 * Date: 2015-12-22
 * Time: 8:30 AM
 */


class CollectiveTest extends PHPUnit_Framework_TestCase
{
    protected $collective;
    protected $container;

    public function setUp ()
    {
        $this->container = new Slim\Container();

        $this->container['app-middleware'] = [LoggerMiddleware::class];


        $this->container['routes'] = [
            'get' => [
                '/' => [ "callable" => HelloWorldAction::class, "mw" => ["Test1", "Test2"], "name" => "" ],
                '/json' => [ "callable" => HelloWorldJsonAction::class, "mw" => [], "name" => "" ]
            ]
        ];

        $this->container['twig'] = function ($c) {
            $view = new \Slim\Views\Twig("templates",
                ['cache' => false]);

            // Instantiate and add Slim specific extension
            $view->addExtension(
                new Slim\Views\TwigExtension(
                    $c['router'],
                    $c['request']->getUri()
                )
            );

            return $view;
        };

        $this->collective = new \Collective\Collective($this->container);

        $this->collective->addMw("Test1", function ($c) {
                return function ($req, $res, $next) {
                    $res->write("Test1");

                    return $next($req, $res);
                };
            }
        );

        $this->collective->addMw("Test2", function ($c) {
                return function ($req, $res, $next) {
                    $res = $next($req, $res);
                    $res->write("Test2");

                    return $res;
                };
            }
        );

        $this->collective->applyMiddleware();

        $this->collective->applyRoutes();
    }

    public function requestFactory($uri = '/') {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        return $req;
    }


    public function testOutputForDefaultRoute() {
        $output = "Test1Hi!Test2";
        $req = $this->requestFactory();
        $res = new \Slim\Http\Response();

        $collective = $this->collective;
        $res = $collective($req, $res);
        $res->getBody()->rewind();
        $this->assertEquals($output, $res->getBody()->getContents());
    }

}
