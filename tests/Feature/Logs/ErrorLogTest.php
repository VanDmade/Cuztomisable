<?php

namespace VanDmade\Cuztomisable\Tests\Feature\Logs;

use RuntimeException;
use VanDmade\Cuztomisable\Models\Logs\Error;
use VanDmade\Cuztomisable\Services\Logs\ErrorLogService;
use VanDmade\Cuztomisable\Tests\Concerns\ActsAsAdmin;
use VanDmade\Cuztomisable\Tests\TestCase;

/**
 * Error logs admin - the list of logged errors and closing them (one, or every open copy of the same message) -
 * behind permission:view-logs.
 */
class ErrorLogTest extends TestCase
{

    use ActsAsAdmin;

    private const TABLE = ['page' => 1, 'size' => 25];

    private function error(string $message, array $attributes = []): Error
    {
        return Error::create(array_merge(['message' => $message, 'code' => 500, 'file' => 'app.php', 'line' => 1], $attributes));
    }

    private function listed(array $query = []): array
    {
        return collect($this->getJson('/api/logs/error?'.http_build_query(self::TABLE + $query))->assertOk()->json('data'))
            ->pluck('message')->all();
    }

    public function test_the_error_log_routes_turn_away_a_user_without_view_logs(): void
    {
        $error = $this->error('Boom');
        $this->actingAsUserWith('manage-users');

        $this->getJson('/api/logs/error?'.http_build_query(self::TABLE))->assertForbidden();
        $this->patchJson("/api/logs/error/{$error->id}/close", ['reason' => 'x'])->assertForbidden();

        $this->assertNull($error->fresh()->closed_at);
    }

    public function test_the_service_logs_an_error_with_its_context(): void
    {
        $log = app(ErrorLogService::class)->log(new RuntimeException('Disk full', 507), 'Uploading', ['file' => 'a.png']);

        $this->assertDatabaseHas('error_logs', ['id' => $log->id, 'message' => 'Disk full', 'code' => 507]);
        $this->assertSame('Uploading', $log->fresh()->parameters['context']);
        $this->assertSame('a.png', $log->fresh()->parameters['file']);
    }

    public function test_the_list_shows_open_errors_only_by_default(): void
    {
        $this->actingAsUserWith('view-logs');
        $this->error('Still broken');
        $this->error('Already fixed', ['closed_at' => now()]);

        $messages = $this->listed();
        $this->assertContains('Still broken', $messages);
        $this->assertNotContains('Already fixed', $messages);
    }

    public function test_show_closed_includes_the_closed_errors_too(): void
    {
        $this->actingAsUserWith('view-logs');
        $this->error('Still broken');
        $this->error('Already fixed', ['closed_at' => now()]);

        $messages = $this->listed(['show_closed' => 1]);
        $this->assertContains('Still broken', $messages);
        $this->assertContains('Already fixed', $messages);
    }

    public function test_the_list_searches_by_message(): void
    {
        $this->actingAsUserWith('view-logs');
        $this->error('Database timeout');
        $this->error('Mail bounced');

        $this->assertSame(['Database timeout'], $this->listed(['search' => 'timeout']));
    }

    public function test_closing_an_error_records_who_closed_it_and_why(): void
    {
        $admin = $this->actingAsUserWith('view-logs');
        $error = $this->error('Boom');

        $this->patchJson("/api/logs/error/{$error->id}/close", ['reason' => 'Fixed in 1.2'])->assertOk();

        $error->refresh();
        $this->assertNotNull($error->closed_at);
        $this->assertSame($admin->id, (int) $error->closed_by);
        $this->assertSame('Fixed in 1.2', $error->closed_reason);
    }

    public function test_closing_with_close_duplicates_closes_every_open_copy_of_the_same_message(): void
    {
        $this->actingAsUserWith('view-logs');
        $first = $this->error('Boom');
        $copy = $this->error('Boom');
        $other = $this->error('Something else');

        $this->patchJson("/api/logs/error/{$first->id}/close", ['reason' => 'Fixed', 'close_duplicates' => 1])->assertOk();

        $this->assertNotNull($first->fresh()->closed_at);
        $this->assertNotNull($copy->fresh()->closed_at);
        $this->assertNull($other->fresh()->closed_at);
    }

    public function test_closing_without_close_duplicates_leaves_the_copies_open(): void
    {
        $this->actingAsUserWith('view-logs');
        $first = $this->error('Boom');
        $copy = $this->error('Boom');

        $this->patchJson("/api/logs/error/{$first->id}/close", ['reason' => 'One-off'])->assertOk();

        $this->assertNotNull($first->fresh()->closed_at);
        $this->assertNull($copy->fresh()->closed_at);
    }

    public function test_closing_an_unknown_error_returns_not_found(): void
    {
        $this->actingAsUserWith('view-logs');
        $this->patchJson('/api/logs/error/999999/close', ['reason' => 'x'])->assertNotFound();
    }

}
