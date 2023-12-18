<?php

class UserPageTest extends FetchPageTestCase
{

    /**
     * Loads the member testing fixture.
     */
    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet(dirname(__FILE__).'/_fixtures/user.xml');
    }

    private function fetch_page($vars)
    {
        return $this->base_fetch_page($vars, 'user');
    }

    private function fetch_user_page( $vars = array(), $page = 'user' )
    {
        return $this->base_fetch_page_user( $vars, '1.fbb689a0c092f5534b929d302db2c8a9', $page );
    }

    public function testLoginPageLoads()
    {
        $page = $this->base_fetch_page( array(), 'user/login' );
        $this->assertStringContainsString('Sign in', $page);
    }

    public function testLoginPage()
    {
        $vars = array(
            'email' => 'user@example.org',
            'password' => 'password',
            'submitted' => 'true',
        );
        $page = $this->base_fetch_page( $vars, 'user/login' );
        # it's a redirect which means we should get nothing
        # as we're using the cli version of php :(
        $this->assertEquals('', $page);
    }

    public function testUserInfoPageLoads()
    {
        $page = $this->fetch_user_page();
        $this->assertStringContainsString('Your details', $page);
        $this->assertStringContainsString('Test User', $page);
    }

    public function testEditUserInfo()
    {
        $page = $this->fetch_user_page( array('pg' => 'edit' ) );
        $this->assertStringContainsString('Edit your details', $page);
        $this->assertStringContainsString('name="pg" value="edit"', $page);
        $this->assertStringContainsString('value="Test"', $page);

        $vars = array(
            'pg' => 'edit',
            'user_id' => 1,
            'firstname' => 'Example',
            'lastname' => 'User',
            'email' => 'user@example.org',
            'submitted' => 'true',
        );
        $page = $this->fetch_user_page( $vars );
        $this->assertStringContainsString('Example User', $page);
    }
}
