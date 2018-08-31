<?php

namespace Tests;

use Illuminate\Http\Request;
use Matthewbdaly\ETagMiddleware\ETag;
use Mockery as m;

/**
 * ETag test.
 */
class EtagTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test new request not cached.
     *
     * @return void
     */
    public function testModified()
    {
        // Create mock header
        $headers = m::mock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headers->shouldReceive('all')->andReturn(['Content-Type' => 'text/html']);

        // Create mock response
        $response = m::mock('Illuminate\Http\Response');
        $response->headers = $headers;
        $response->shouldReceive('getContent')->once()->andReturn('blah');
        $response->shouldReceive('setEtag')->with(md5('{"Content-Type":"text\/html"}blah'));
        $response->shouldNotReceive('setNotModified');

        // Create request
        $request = Request::create('http://example.com/admin', 'GET');

        // Pass it to the middleware
        $middleware = new ETag();
        $middlewareResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });
    }

    /**
     * Test repeated request not modified.
     *
     * @return void
     */
    public function testNotModified()
    {
        // Create mock header
        $headers = m::mock('Symfony\Component\HttpFoundation\ResponseHeaderBag');
        $headers->shouldReceive('all')->andReturn(['content-type' => 'text/html']);

        // Create mock response
        $response = m::mock('Illuminate\Http\Response');
        $response->headers = $headers;
        $response->shouldReceive('getContent')->once()->andReturn('blah');
        $response->shouldReceive('setEtag')->with(md5('{"content-type":"text\/html"}blah'));
        $response->shouldReceive('setNotModified')->once();

        // Create request
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('isMethod')->with('get')->andReturn(true);
        $request->shouldReceive('method')->andReturn('get');
        $request->shouldReceive('setMethod')->with('get')->andReturnTrue();
        $request->shouldReceive('getETags')->andReturn([
            md5('{"content-type":"text\/html"}blah'),
        ]);

        // Pass it to the middleware
        $middleware = new ETag();
        $middlewareResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });
    }

    /**
     * Test request not GET or HEAD.
     *
     * @return void
     */
    public function testNotGetOrHead()
    {
        // Create mock response
        $response = m::mock('Illuminate\Http\Response');
        $response->shouldNotReceive('setEtag');
        $response->shouldNotReceive('setNotModified');

        // Create request
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('isMethod')->with('get')->andReturn(false);
        $request->shouldReceive('isMethod')->with('head')->andReturn(false);

        // Pass it to the middleware
        $middleware = new ETag();
        $middlewareResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });
    }

    /**
     * Tear down the test.
     *
     * @return void
     */
    public function tearDown()
    {
        m::close();
    }
}
