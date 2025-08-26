<?php

use Tests\TestCase;

uses(TestCase::class);

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});
