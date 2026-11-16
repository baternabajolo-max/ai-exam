<?php

namespace App\Services;

class AprioriTopicMiner
{
    /**
     * @param  array<int, array<int, string>>  $transactions
     * @return array{
     *     topic_support: array<string, int>,
     *     frequent_itemsets: array<int, array{items: array<int, string>, support: int}>
     * }
     */
    public function mine(array $transactions, int $minimumSupport = 2): array
    {
        $minimumSupport = max(1, $minimumSupport);
        $canonicalTopics = [];
        $normalizedTransactions = [];

        foreach ($transactions as $transaction) {
            $items = [];

            foreach ($transaction as $topic) {
                $topic = trim($topic);
                if ($topic === '') {
                    continue;
                }

                $key = mb_strtolower($topic);
                $canonicalTopics[$key] ??= $topic;
                $items[$key] = true;
            }

            $normalizedTransactions[] = $items;
        }

        $singleTopicSupport = [];
        foreach ($canonicalTopics as $key => $topic) {
            $support = 0;
            foreach ($normalizedTransactions as $transaction) {
                if (isset($transaction[$key])) {
                    $support++;
                }
            }

            if ($support >= $minimumSupport) {
                $singleTopicSupport[$key] = $support;
            }
        }

        $frequentItemsets = [];
        $currentFrequent = [];

        foreach ($singleTopicSupport as $key => $support) {
            $currentFrequent[] = [$key];
            $frequentItemsets[] = [
                'items' => [$canonicalTopics[$key]],
                'support' => $support,
            ];
        }

        for ($itemsetSize = 2; $currentFrequent !== []; $itemsetSize++) {
            $previousFrequentLookup = [];
            foreach ($currentFrequent as $itemset) {
                $previousFrequentLookup[$this->itemsetKey($itemset)] = true;
            }

            $candidates = [];
            $frequentCount = count($currentFrequent);
            for ($leftIndex = 0; $leftIndex < $frequentCount; $leftIndex++) {
                for ($rightIndex = $leftIndex + 1; $rightIndex < $frequentCount; $rightIndex++) {
                    $candidate = array_values(array_unique(array_merge(
                        $currentFrequent[$leftIndex],
                        $currentFrequent[$rightIndex],
                    )));
                    sort($candidate);

                    if (count($candidate) !== $itemsetSize || ! $this->allSubsetsAreFrequent($candidate, $previousFrequentLookup)) {
                        continue;
                    }

                    $candidates[$this->itemsetKey($candidate)] = $candidate;
                }
            }

            $nextFrequent = [];
            foreach ($candidates as $candidate) {
                $support = 0;
                foreach ($normalizedTransactions as $transaction) {
                    if ($this->transactionContains($transaction, $candidate)) {
                        $support++;
                    }
                }

                if ($support < $minimumSupport) {
                    continue;
                }

                $nextFrequent[] = $candidate;
                $frequentItemsets[] = [
                    'items' => array_map(
                        static fn (string $key): string => $canonicalTopics[$key],
                        $candidate,
                    ),
                    'support' => $support,
                ];
            }

            $currentFrequent = $nextFrequent;
        }

        $topicSupport = [];
        foreach ($singleTopicSupport as $key => $support) {
            $topicSupport[$key] = $support;
        }

        return [
            'topic_support' => $topicSupport,
            'frequent_itemsets' => $frequentItemsets,
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @param  array<string, bool>  $frequentLookup
     */
    private function allSubsetsAreFrequent(array $items, array $frequentLookup): bool
    {
        foreach ($items as $itemToRemove) {
            $subset = array_values(array_diff($items, [$itemToRemove]));
            if (! isset($frequentLookup[$this->itemsetKey($subset)])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, bool>  $transaction
     * @param  array<int, string>  $items
     */
    private function transactionContains(array $transaction, array $items): bool
    {
        foreach ($items as $item) {
            if (! isset($transaction[$item])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $items
     */
    private function itemsetKey(array $items): string
    {
        return implode("\0", $items);
    }
}
