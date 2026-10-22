<?php

/**
 * Testing for grouping a person's policy distributions by recency.
 */

use MySociety\TheyWorkForYou\PolicyDistribution;
use MySociety\TheyWorkForYou\PolicyDistributionCollection;
use MySociety\TheyWorkForYou\PolicyDistributionPair;

class PolicyDistributionCollectionTest extends PHPUnit\Framework\TestCase {
    private function makePair(int $start_year, int $end_year): PolicyDistributionPair {
        $distribution = (new ReflectionClass(PolicyDistribution::class))->newInstanceWithoutConstructor();
        $distribution->start_year = $start_year;
        $distribution->end_year = $end_year;

        $pair = (new ReflectionClass(PolicyDistributionPair::class))->newInstanceWithoutConstructor();
        $pair->member_distribution = $distribution;
        return $pair;
    }

    private function makeCollection(array $pairs): PolicyDistributionCollection {
        return new PolicyDistributionCollection('transport', 'Transport', 'all_time', 1, 'labour', $pairs);
    }

    private function labels(array $buckets): array {
        return array_map(function ($bucket) {
            return $bucket->label();
        }, $buckets);
    }

    public function testBucketsAreClampedToPersonDates() {
        $collection = $this->makeCollection([
            $this->makePair(2011, 2012),
            $this->makePair(2016, 2018),
        ]);

        $this->assertSame(
            ['between 2015 and 2017', 'between 2011 and 2014'],
            $this->labels($collection->getPairsByRecencyBucket(5, 2011, 2017))
        );
    }

    public function testBucketStartIsNotAfterBucketEnd() {
        // Someone who left and later returned: votes from their earlier
        // time in Parliament must not be labelled with a range starting
        // at the year of their most recent return.
        $collection = $this->makeCollection([
            $this->makePair(2001, 2008),
            $this->makePair(2020, 2021),
        ]);

        $this->assertSame(
            ['between 2020 and 2022', 'between 2005 and 2009'],
            $this->labels($collection->getPairsByRecencyBucket(5, 2020, 2022))
        );
    }
}
