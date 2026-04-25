<?php

test('offline route redirects to the static offline page', function () {
    $this->get('/offline')
        ->assertRedirect('/offline.html');
});

test('error pages render their dedicated screens when debug is disabled', function () {
    config()->set('app.debug', false);

    $this->get('/test-error/404')
        ->assertStatus(404)
        ->assertSeeText(__('The page could not be found'));

    $this->get('/test-error/500')
        ->assertStatus(500)
        ->assertSeeText(__('The system ran into an internal error'));

    $this->get('/test-error/503')
        ->assertStatus(503)
        ->assertSeeText(__('The application is currently under maintenance'));
});
