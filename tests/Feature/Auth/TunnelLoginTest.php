<?php

test('login form uses https behind the local tunnel proxy', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeaders([
            'Host' => 'demo.trycloudflare.com',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Port' => '443',
        ])
        ->get('http://demo.trycloudflare.com/login')
        ->assertOk()
        ->assertSee('action="https://demo.trycloudflare.com/login"', false);
});

test('untrusted clients cannot change the login scheme with forwarded headers', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders([
            'Host' => 'demo.test',
            'X-Forwarded-Proto' => 'https',
        ])
        ->get('http://demo.trycloudflare.com/login')
        ->assertOk()
        ->assertSee('action="http://demo.trycloudflare.com/login"', false);
});
