<?php

declare(strict_types=1);

namespace Marwa\Kafka\Tests\Support;

use Marwa\Kafka\Support\Topic;
use PHPUnit\Framework\TestCase;

final class TopicTest extends TestCase
{
    public function testItNormalizesAndDeduplicatesTopicLists(): void
    {
        $topics = Topic::normalizeList([' orders ', 'payments', 'orders']);

        self::assertSame(['orders', 'payments'], $topics);
    }

    public function testItRejectsEmptyTopicNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka topic names must be non-empty strings.');

        Topic::normalize('   ');
    }

    public function testItRejectsEmptyTopicLists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one Kafka topic must be provided.');

        Topic::normalizeList([]);
    }
}
