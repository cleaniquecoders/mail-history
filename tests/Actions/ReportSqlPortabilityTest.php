<?php

use CleaniqueCoders\MailHistory\Actions\GetMailHistoryReport;
use Illuminate\Support\Facades\DB;

/**
 * The report builds raw SQL, so it has to speak the dialect of whatever
 * connection the host application runs on.
 *
 * This went unnoticed because the suite runs on SQLite, which accepts MySQL's
 * backtick as an identifier quote. PostgreSQL does not: it answers
 * SQLSTATE[42601] "syntax error at or near `" and takes the dashboard down.
 * DATE_FORMAT() is MySQL-only in the same way.
 *
 * These assertions read the grammar rather than a live server, so no
 * PostgreSQL or MySQL instance is needed to keep the dialects honest.
 */
function reportOn(string $driver): GetMailHistoryReport
{
    config()->set("database.connections.portability-{$driver}", [
        'driver' => $driver,
        'host' => '127.0.0.1',
        'database' => 'unused',
        'username' => 'unused',
        'password' => '',
        'prefix' => '',
    ]);

    config()->set('database.default', "portability-{$driver}");
    DB::purge("portability-{$driver}");

    return new class extends GetMailHistoryReport
    {
        public function aliasFor(string $alias): string
        {
            return $this->alias($alias);
        }

        public function periodExpressionFor(string $interval): string
        {
            return $this->periodExpression($interval);
        }
    };
}

afterEach(function () {
    config()->set('database.default', 'testing');
});

it('quotes column aliases the way each connection expects', function () {
    expect(reportOn('mysql')->aliasFor('sending'))->toBe('`sending`')
        ->and(reportOn('pgsql')->aliasFor('sending'))->toBe('"sending"')
        ->and(reportOn('sqlite')->aliasFor('sending'))->toBe('"sending"');
});

it('never emits a backtick on postgres', function () {
    $report = reportOn('pgsql');

    foreach (['sending', 'delivered', 'bounced', 'complained'] as $alias) {
        expect($report->aliasFor($alias))->not->toContain('`');
    }
});

it('buckets periods with a function the connection actually has', function () {
    $pgsql = reportOn('pgsql');

    // to_char, not DATE_FORMAT — the latter does not exist on PostgreSQL.
    expect($pgsql->periodExpressionFor('daily'))->toBe("to_char(created_at, 'YYYY-MM-DD')")
        ->and($pgsql->periodExpressionFor('monthly'))->toBe("to_char(created_at, 'YYYY-MM')")
        ->and($pgsql->periodExpressionFor('weekly'))->toContain('IYYY')
        ->and($pgsql->periodExpressionFor('daily'))->not->toContain('DATE_FORMAT');

    expect(reportOn('mysql')->periodExpressionFor('daily'))->toContain('DATE_FORMAT');
    expect(reportOn('sqlite')->periodExpressionFor('daily'))->toContain('strftime');
});
