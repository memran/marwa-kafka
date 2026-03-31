<?php

declare(strict_types=1);

namespace Marwa\Kafka\Support;

final class Topic
{
    public static function normalize(string $topic): string
    {
        $normalized = trim($topic);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Kafka topic names must be non-empty strings.');
        }

        return $normalized;
    }

    /**
     * @param list<string> $topics
     * @return list<string>
     */
    public static function normalizeList(array $topics): array
    {
        $normalizedTopics = array_map(self::normalize(...), $topics);

        if ($normalizedTopics === []) {
            throw new \InvalidArgumentException('At least one Kafka topic must be provided.');
        }

        return array_values(array_unique($normalizedTopics));
    }
}
