<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->post('/_test/flash-notification', fn () => redirect('/dashboard')
        ->with(request('type'), request('message')));

    $this->actingAs(User::factory()->create());
    $this->withHeader('X-Inertia-Version', app(HandleInertiaRequests::class)->version(request()) ?? '');
});

it('delivers notifications once without replaying them in partial reloads', function (string $type) {
    $message = 'Question generation has been queued.';
    $this->post('/_test/flash-notification', ['type' => $type, 'message' => $message])->assertRedirect('/dashboard');

    $this->get('/dashboard', ['X-Inertia' => 'true'])
        ->assertOk()
        ->assertJsonPath('flash.'.$type, $message)
        ->assertJsonPath('props.flash.'.$type, $message);

    foreach (range(1, 3) as $poll) {
        $this->get('/dashboard', [
            'X-Inertia' => 'true',
            'X-Inertia-Partial-Component' => 'Dashboard',
            'X-Inertia-Partial-Data' => 'recentQuestions',
        ])->assertOk()->assertJsonMissingPath('flash')->assertJsonMissingPath('props.flash');
    }

    $this->post('/_test/flash-notification', ['type' => $type, 'message' => $message])->assertRedirect();
    $this->get('/dashboard', ['X-Inertia' => 'true'])->assertJsonPath('flash.'.$type, $message);
})->with(['success', 'error']);
