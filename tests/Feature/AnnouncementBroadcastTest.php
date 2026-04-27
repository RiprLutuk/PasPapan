<?php

use App\Events\AnnouncementsChanged;
use App\Livewire\Admin\AnnouncementManager;
use App\Livewire\Shared\HighPriorityAnnouncementModal;
use App\Livewire\Shared\NotificationsDropdown;
use App\Models\User;
use App\Support\AnnouncementRefresh;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

test('announcement manager broadcasts queued change events', function () {
    Event::fake([AnnouncementsChanged::class]);
    Config::set('broadcasting.default', 'reverb');

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AnnouncementManager::class)
        ->set('title', 'High Priority Notice')
        ->set('content', 'Please review the new operational update.')
        ->set('priority', 'high')
        ->set('modal_behavior', 'acknowledge')
        ->set('publish_date', now()->toDateString())
        ->set('expire_date', null)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatched(AnnouncementsChanged::class, fn (AnnouncementsChanged $event): bool => $event->action === 'created');
});

test('announcement manager skips broadcast jobs on shared hosting fallback', function () {
    Event::fake([AnnouncementsChanged::class]);
    Config::set('broadcasting.default', 'log');

    $superadmin = User::factory()->admin(true)->create();

    $this->actingAs($superadmin);

    Livewire::test(AnnouncementManager::class)
        ->set('title', 'Shared Hosting Notice')
        ->set('content', 'This install uses polling fallback.')
        ->set('priority', 'high')
        ->set('modal_behavior', 'acknowledge')
        ->set('publish_date', now()->toDateString())
        ->set('expire_date', null)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertNotDispatched(AnnouncementsChanged::class);
});

test('announcement refresh uses broadcast on vps and polling fallback on shared hosting', function () {
    Config::set('realtime.announcements.refresh_mode', 'auto');
    Config::set('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);

    Config::set('broadcasting.default', 'log');

    expect(AnnouncementRefresh::shouldPoll())->toBeTrue();

    Config::set('broadcasting.default', 'reverb');

    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();
});

test('announcement refresh mode can force poll broadcast or off', function () {
    Config::set('broadcasting.default', 'log');

    Config::set('realtime.announcements.refresh_mode', 'poll');
    expect(AnnouncementRefresh::shouldPoll())->toBeTrue();

    Config::set('realtime.announcements.refresh_mode', 'broadcast');
    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();

    Config::set('realtime.announcements.refresh_mode', 'off');
    expect(AnnouncementRefresh::shouldPoll())->toBeFalse();
});

test('announcement polling interval is sanitized for livewire modifiers', function () {
    Config::set('realtime.announcements.poll_interval', '120s');
    expect(AnnouncementRefresh::pollInterval())->toBe('120s');

    Config::set('realtime.announcements.poll_interval', 'bad value');
    expect(AnnouncementRefresh::pollInterval())->toBe('60s');
});

test('echo listeners are only registered when realtime broadcasting is enabled', function () {
    Config::set('realtime.announcements.refresh_mode', 'auto');
    Config::set('realtime.announcements.broadcast_connections', ['reverb', 'pusher', 'ably']);
    Config::set('broadcasting.default', 'log');

    expect(componentListeners(new HighPriorityAnnouncementModal()))->toBe([])
        ->and(componentListeners(new NotificationsDropdown()))
        ->not->toHaveKey('echo:announcements,.announcements.changed');

    Config::set('broadcasting.default', 'reverb');

    expect(componentListeners(new HighPriorityAnnouncementModal()))
        ->toHaveKey('echo:announcements,.announcements.changed')
        ->and(componentListeners(new NotificationsDropdown()))
        ->toHaveKey('echo:announcements,.announcements.changed');
});

function componentListeners(object $component): array
{
    $method = new ReflectionMethod($component, 'getListeners');
    $method->setAccessible(true);

    return $method->invoke($component);
}

test('announcement event uses public announcement channel payload', function () {
    $event = new AnnouncementsChanged('updated');

    expect($event->broadcastAs())->toBe('announcements.changed')
        ->and($event->broadcastWith())->toBe(['action' => 'updated'])
        ->and($event->broadcastOn()->name)->toBe('announcements');
});
