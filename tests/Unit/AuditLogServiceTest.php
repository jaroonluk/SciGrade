<?php

namespace Tests\Unit;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    #[Test]
    public function it_does_not_throw_when_audit_table_is_unavailable(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                return $message === 'Failed to write audit_log_scigrad'
                    && ($context['event'] ?? null) === 'auth.login';
            });

        $this->app->instance('request', Request::create('/dashboard', 'GET'));

        (new AuditLogService)->record(
            'auth.login',
            subjectType: 'staff',
            subjectId: 'testuser',
            metadata: ['email' => 'test@example.com'],
            actorUsername: 'testuser',
            actorRole: 'instructor',
        );

        $this->addToAssertionCount(1);
    }
}
