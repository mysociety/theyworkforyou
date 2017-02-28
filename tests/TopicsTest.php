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
        $this->assertContains('Topics', $page);
        $this->assertContains('NHS', $page);
        $this->assertContains('Welfare', $page);
    }

    public function testTopicsOnFrontPage() {
        return $this->base_fetch_page(array('url' => '/'), '/');
        $this->assertContains('NHS', $page);
        $this->assertNotContains('Welfare', $page);
    }

    public function testTopicPage() {
        $page = $this->fetch_topic_page(array('topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertContains('NHS', $page);
        $this->assertNotContains('Welfare', $page);
        $this->assertContains('Test Hansard Section', $page);
        $this->assertNotContains('foundation hospitals', $page);
        $this->assertNotContains('Sign up for email alerts', $page);
    }

    public function testTopicPageWithSearch() {
        $page = $this->fetch_topic_page(array('topic' => 'welfare', 'url' => '/topic/welfare'));
        $this->assertContains('Welfare', $page);
        $this->assertNotContains('NHS', $page);
        $this->assertNotContains('Test Hansard Section', $page);
    }

    public function testTopicPageWithMP() {
        $page = $this->fetch_topic_page(array('pc' => 'SW1 1AA', 'topic' => 'nhs', 'url' => '/topic/nhs'));
        $this->assertContains('NHS', $page);
        $this->assertNotContains('Welfare', $page);
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('Test Hansard Section', $page);
        $this->assertContains('foundation hospitals', $page);
    }

    public function testTopicPageWithMPAndPolicy() {
        $page = $this->fetch_topic_page(array('pc' => 'SW1 1AA', 'topic' => 'welfare', 'url' => '/topic/welfare'));
        $this->assertContains('Welfare', $page);
        $this->assertContains('Test Current-MP', $page);
        $this->assertContains('foundation hospitals', $page);
    }
}
