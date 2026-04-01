<?php

namespace Workbench\Database\Seeders;

use CleaniqueCoders\MailHistory\MailHistory as MailHistoryConstants;
use CleaniqueCoders\MailHistory\Models\MailHistory;
use CleaniqueCoders\MailHistory\Models\MailHistoryEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Workbench\Database\Factories\UserFactory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        UserFactory::new()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedMailHistory();
    }

    protected function seedMailHistory(): void
    {
        $statuses = [
            MailHistoryConstants::STATUS_DELIVERED,
            MailHistoryConstants::STATUS_OPENED,
            MailHistoryConstants::STATUS_CLICKED,
            MailHistoryConstants::STATUS_BOUNCED,
            MailHistoryConstants::STATUS_COMPLAINED,
            MailHistoryConstants::STATUS_FAILED,
            MailHistoryConstants::STATUS_SENT,
            MailHistoryConstants::STATUS_SENDING,
        ];

        $subjects = [
            'Welcome to Our Platform',
            'Your Order Confirmation #1234',
            'Password Reset Request',
            'Monthly Newsletter - January',
            'Invoice #INV-2025-001',
            'Account Verification',
            'Shipping Notification',
            'Weekly Digest',
        ];

        $recipients = [
            'alice@example.com',
            'bob@example.com',
            'charlie@example.com',
            'dave@example.com',
            'eve@example.com',
            'bad-email@invalid.test',
            'bounced@nowhere.test',
        ];

        $providers = ['mailgun', 'ses', 'postmark', 'sendgrid', 'resend'];

        // Create 50 mail history records spread over last 30 days
        for ($i = 0; $i < 50; $i++) {
            $status = $statuses[array_rand($statuses)];
            $recipient = $recipients[array_rand($recipients)];
            $subject = $subjects[array_rand($subjects)];
            $createdAt = now()->subDays(rand(0, 30))->subHours(rand(0, 23));
            $hash = sha1(Str::orderedUuid());

            $mail = MailHistory::create([
                'uuid' => Str::orderedUuid(),
                'hash' => $hash,
                'status' => $status,
                'headers' => [
                    'Subject' => $subject,
                    'To' => $recipient,
                    'From' => 'noreply@example.com',
                ],
                'body' => "<p>Sample email body for {$subject}</p>",
                'content' => [
                    'text' => "Sample email body for {$subject}",
                    'text-charset' => 'utf-8',
                    'html' => "<p>Sample email body for {$subject}</p>",
                    'html-charset' => 'utf-8',
                ],
                'meta' => [
                    'origin' => 'Mail',
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add events based on status
            $provider = $providers[array_rand($providers)];
            $eventTime = $createdAt->copy()->addSeconds(rand(1, 60));

            if (in_array($status, [
                MailHistoryConstants::STATUS_DELIVERED,
                MailHistoryConstants::STATUS_OPENED,
                MailHistoryConstants::STATUS_CLICKED,
            ])) {
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'delivered',
                    'provider' => $provider,
                    'payload' => ['simulated' => true],
                    'occurred_at' => $eventTime,
                    'created_at' => $eventTime,
                    'updated_at' => $eventTime,
                ]);
            }

            if (in_array($status, [
                MailHistoryConstants::STATUS_OPENED,
                MailHistoryConstants::STATUS_CLICKED,
            ])) {
                $openTime = $eventTime->copy()->addMinutes(rand(1, 120));
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'opened',
                    'provider' => null,
                    'ip_address' => rand(1, 255).'.'.rand(0, 255).'.'.rand(0, 255).'.'.rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'payload' => [],
                    'occurred_at' => $openTime,
                    'created_at' => $openTime,
                    'updated_at' => $openTime,
                ]);
            }

            if ($status === MailHistoryConstants::STATUS_CLICKED) {
                $clickTime = $eventTime->copy()->addMinutes(rand(2, 180));
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'clicked',
                    'provider' => null,
                    'ip_address' => rand(1, 255).'.'.rand(0, 255).'.'.rand(0, 255).'.'.rand(1, 255),
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
                    'url' => 'https://example.com/'.Str::random(8),
                    'payload' => [],
                    'occurred_at' => $clickTime,
                    'created_at' => $clickTime,
                    'updated_at' => $clickTime,
                ]);
            }

            if ($status === MailHistoryConstants::STATUS_BOUNCED) {
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'bounced',
                    'provider' => $provider,
                    'payload' => ['reason' => 'Mailbox not found', 'simulated' => true],
                    'occurred_at' => $eventTime,
                    'created_at' => $eventTime,
                    'updated_at' => $eventTime,
                ]);
            }

            if ($status === MailHistoryConstants::STATUS_COMPLAINED) {
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'complained',
                    'provider' => $provider,
                    'payload' => ['feedback_type' => 'abuse', 'simulated' => true],
                    'occurred_at' => $eventTime,
                    'created_at' => $eventTime,
                    'updated_at' => $eventTime,
                ]);
            }

            if ($status === MailHistoryConstants::STATUS_FAILED) {
                MailHistoryEvent::create([
                    'uuid' => Str::orderedUuid(),
                    'mail_history_id' => $mail->id,
                    'type' => 'failed',
                    'provider' => $provider,
                    'payload' => ['error' => 'Connection timeout', 'simulated' => true],
                    'occurred_at' => $eventTime,
                    'created_at' => $eventTime,
                    'updated_at' => $eventTime,
                ]);
            }
        }

        // Add a few stale "Sending" records for the alert demo
        for ($i = 0; $i < 3; $i++) {
            $staleTime = now()->subHours(rand(2, 6));
            MailHistory::create([
                'uuid' => Str::orderedUuid(),
                'hash' => sha1(Str::orderedUuid()),
                'status' => MailHistoryConstants::STATUS_SENDING,
                'headers' => [
                    'Subject' => 'Stuck Email #'.($i + 1),
                    'To' => 'stuck@example.com',
                    'From' => 'noreply@example.com',
                ],
                'body' => 'This email got stuck.',
                'content' => ['text' => 'Stuck', 'html' => '<p>Stuck</p>'],
                'meta' => ['origin' => 'Mail'],
                'created_at' => $staleTime,
                'updated_at' => $staleTime,
            ]);
        }
    }
}
