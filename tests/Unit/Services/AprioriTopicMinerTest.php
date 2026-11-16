<?php

namespace Tests\Unit\Services;

use App\Services\AprioriTopicMiner;
use PHPUnit\Framework\TestCase;

class AprioriTopicMinerTest extends TestCase
{
    public function test_returns_recurring_topics_and_frequent_topic_combinations(): void
    {
        $patterns = (new AprioriTopicMiner)->mine([
            ['Photosynthesis', 'Cellular Respiration'],
            ['Photosynthesis', 'Cellular Respiration'],
            ['Photosynthesis', 'Ecology'],
            ['Ecology'],
        ]);

        $this->assertSame(3, $patterns['topic_support']['photosynthesis']);
        $this->assertSame(2, $patterns['topic_support']['cellular respiration']);
        $this->assertContains([
            'items' => ['Cellular Respiration', 'Photosynthesis'],
            'support' => 2,
        ], $patterns['frequent_itemsets']);
    }

    public function test_excludes_topics_below_the_minimum_support(): void
    {
        $patterns = (new AprioriTopicMiner)->mine([
            ['Photosynthesis'],
            ['Ecology'],
            ['Photosynthesis'],
        ], minimumSupport: 2);

        $this->assertSame(['photosynthesis' => 2], $patterns['topic_support']);
        $this->assertSame([
            ['items' => ['Photosynthesis'], 'support' => 2],
        ], $patterns['frequent_itemsets']);
    }
}
