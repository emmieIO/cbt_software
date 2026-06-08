<?php

test('application loads', function () {
    $response = $this->get('/login');
    $response->assertStatus(200);
});
