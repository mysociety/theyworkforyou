<?php

/**
 * Provides test methods for topics functionality.
 */
class TopicsTest extends FetchPageTestCase
{

    /**
     * Loads the topics testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/topics.xml');
    }

    private function fetch_topics_page($vars)
    {
        return $this->base_fetch_page($vars, 'topic', 'index.php', '/topic/index.php');
    }

    private function fetch_topic_page($vars)
    {
        return $this->base_fetch_page($vars, 'topic', 'topic.php', '/topic/topic.php');
    }

    public function testTopicsPage() {
        $page = $this->fetch_topics_page(array('url' => '/topic/'));
        $this->assertStringContainsString('Topics', $page);
        $this->assertStringContainsString('NHS', $page);
        $this->assertStringContainsString('Welfare', $page);
    }

    public function testTopicsOnFrontPage() {
        return $this->base_fetch_page(array('url' => '/'), '/');
        $this->assertStringContainsString('NHS', $page);
        $this->assertStringNotContainsString('Welfare', $page);
    }

    public function testTopicPage() {
        $page = $this->fetch_topic_page(array('topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertStringContainsString('NHS', $page);
        $this->assertStringNotContainsString('Welfare', $page);
        $this->assertStringContainsString('Test Hansard SubSection', $page);
        $this->assertStringNotContainsString('foundation hospitals', $page);
        $this->assertStringNotContainsString('Sign up for email alerts', $page);
    }

    public function testTopicPageWithSearch() {
        $page = $this->fetch_topic_page(array('topic' => 'welfare', 'url' => '/topic/welfare'));
        $this->assertStringContainsString('Welfare', $page);
        $this->assertStringNotContainsString('NHS', $page);
        $this->assertStringNotContainsString('Test Hansard SubSection', $page);
    }

    public function testTopicPageWithMP() {
        $page = $this->fetch_topic_page(array('pc' => 'SW1 1AA', 'topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertStringContainsString('NHS', $page);
        $this->assertStringNotContainsString('Welfare', $page);
        $this->assertStringContainsString('Test Current-MP', $page);
        $this->assertStringContainsString('Test Hansard SubSection', $page);
        $this->assertStringContainsString('foundation hospitals', $page);
    }

    public function testTopicPageWithMPAndPolicy() {
        $page = $this->fetch_topic_page(array('pc' => 'SW1 1AA', 'topic' => 'welfare', 'url' => '/topic/welfare'));
        $this->assertStringContainsString('Welfare', $page);
        $this->assertStringContainsString('Test Current-MP', $page);
        $this->assertStringContainsString('foundation hospitals', $page);
    }

    public function testTopicPageRecentVotes() {
        $page = $this->fetch_topic_page(array('topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertStringContainsString('pw-2013-01-01-1-commons">', $page);
        $this->assertStringContainsString('The majority of MPs  voted Agreed', $page);
    }

    public function testTopicPageContent() {
        $page = $this->fetch_topic_page(array('topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertStringContainsString('Test Hansard SubSection', $page);
    }
}
