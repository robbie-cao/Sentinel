<?php

use Sentinel\Repositories\User\SentryUserRepository;
use Cartalyst\Sentry\Sentry;

/**
 * Class SentryUserTest
 * Test the methods in the SentryUserRepository class
 * /src/Sentinel/Repo/User/SentryUser
 */
class SentryUserRepositoryTest extends \Codeception\TestCase\Test
{
    protected $sentinelConfiguration = [];
    protected $configMock;
    protected $dispatcherMock;
    protected $sentry;

    /******************************************************************************************************************
     * Test Preparation
     ******************************************************************************************************************/
    protected function _before()
    {
        $this->dispatcherMock = Mockery::mock('Illuminate\Events\Dispatcher');
        $this->configMock     = Mockery::mock('Illuminate\Config\Repository');
        $this->sentry         = $this->tester->grabService('sentry');
        $this->repo           = new SentryUserRepository($this->sentry, $this->configMock, $this->dispatcherMock);

        $this->sentinelConfiguration['Sentinel::auth.activation']             = true;
        $this->sentinelConfiguration['Sentinel::auth.allow_usernames']        = true;
        $this->sentinelConfiguration['Sentinel::auth.default_user_groups']    = ['Users'];
        $this->sentinelConfiguration['Sentinel::auth.additional_user_fields'] = [
            'first_name' => 'alpha_spaces',
            'last_name'  => 'alpha_spaces'
        ];

    }

    protected function _after()
    {
    }

    /******************************************************************************************************************
     * Tests
     ******************************************************************************************************************/

    /**
     * Test the instantiation of the Sentinel SentryUser repository
     */
    function testRepoInstantiation()
    {
        // Test that we are able to properly instantiate the SentryUser object for testing
        $this->assertInstanceOf('Sentinel\Repositories\User\SentryUserRepository', $this->repo);
    }

    /**
     * Test that the seed data exists and is reachable
     */
    public function testDatabaseSeeds()
    {
        // Double check that the test data is present and correctly seeded
        $this->tester->seeRecord('users', array('email' => 'user@user.com'));
        $this->tester->seeRecord('users', array('email' => 'admin@admin.com'));
    }

    /**
     * Test the creation of a user using the default configuration options
     */
    function testSavingUser()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(true);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.require_activation', true)
                         ->once()->andReturn(true);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();

        // This is the code we are testing
        $result = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha'
        ]);

        // Grab the "Users" group object for assertions
        $usersGroup = $this->sentry->findGroupByName('Users');

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->getPayload()['activated']);
        $this->tester->seeRecord('users', array(
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'first_name' => null,
            'last_name'  => null
        ));
        $this->assertTrue($result->getPayload()['user']->inGroup($usersGroup));
    }

    /**
     * Test the creation of a user that should be activated upon creation
     */
    function testSavingActivatedUser()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(true);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();

        // This is the code we are testing
        $result = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha',
            'activate'   => true
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($result->getPayload()['activated']);
        $this->tester->seeRecord('users', array(
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'first_name' => null,
            'last_name'  => null,
            'activated'  => 1
        ));

    }

    /**
     * Test the creation of a user without the use of a username
     */
    function testSavingUserWithoutUsername()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(false);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.require_activation', true)
                         ->once()->andReturn(true);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();

        // This is the code we are testing
        $result = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha'
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->getPayload()['activated']);
        $this->tester->seeRecord('users', array(
            'username'   => null,
            'email'      => 'andrei@prozorov.net',
            'first_name' => null,
            'last_name'  => null
        ));
    }

    /**
     * Test the creation of users with additional user fields
     */
    function testSavingUserWithAdditionalData()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(true);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.require_activation', true)
                         ->once()->andReturn(true);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.additional_user_fields']);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();


        // This is the code we are testing
        $result = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha'
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->getPayload()['activated']);
        $this->tester->seeRecord('users', array(
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov'
        ));
    }

    /**
     * Test updating an existing user as an admin operator
     */
    public function testUpdatingUser()
    {
        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.updated', \Mockery::hasKey('user'))->once();

        // Find the user we are going to update
        $user = $this->sentry->findUserByLogin('user@user.com');

        // This is the code we are testing
        $result = $this->repo->update([
            'id'         => $user->id,
            'first_name' => 'Irina',
            'last_name'  => 'Prozorova',
            'username'   => 'muscovite04',
            'email'      => 'irina@prozorov.net'
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $user);
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->tester->seeRecord('users', array(
            'id'         => $user->id,
            'first_name' => null,
            'last_name'  => null,
            'username'   => 'muscovite04',
            'email'      => 'irina@prozorov.net',
        ));
    }

    /**
     * Update a user without referencing the username
     */
    public function testUpdatingUserWithNoUsername()
    {
        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.updated', \Mockery::hasKey('user'))->once();

        // Find the user we are going to update
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Find the admin user we are going to impersonate
        $admin = $this->sentry->findUserByLogin('admin@admin.com');
        $this->sentry->setUser($admin);

        // This is the code we are testing
        $result = $this->repo->update([
            'id'         => $user->id,
            'first_name' => 'Irina',
            'last_name'  => 'Prozorova',
            'email'      => 'irina@prozorov.net'
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $user);
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->tester->seeRecord('users', array(
            'id'         => $user->id,
            'first_name' => null,
            'last_name'  => null,
            'username'   => '',
            'email'      => 'irina@prozorov.net',
        ));
    }

    /**
     * Update a user without referencing the username
     */
    public function testUpdatingUserWithAdditionalData()
    {
        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.additional_user_fields']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.updated', \Mockery::hasKey('user'))->once();

        // Find the user we are going to update
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Find the admin user we are going to impersonate
        $admin = $this->sentry->findUserByLogin('admin@admin.com');
        $this->sentry->setUser($admin);

        // This is the code we are testing
        $result = $this->repo->update([
            'id'         => $user->id,
            'first_name' => 'Irina',
            'last_name'  => 'Prozorova',
            'email'      => 'irina@prozorov.net'
        ]);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $user);
        $this->assertInstanceOf('Sentinel\Models\User', $result->getPayload()['user']);
        $this->assertTrue($result->isSuccessful());
        $this->tester->seeRecord('users', array(
            'id'         => $user->id,
            'first_name' => 'Irina',
            'last_name'  => 'Prozorova',
            'email'      => 'irina@prozorov.net',
        ));
    }

    /**
     * Test deleting a user from storage
     */
    public function testDestroyUser()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.destroyed', \Mockery::hasKey('user'))->once();

        // Find the user we are going to delete
        $user = $this->sentry->findUserByLogin('user@user.com');

        // This is the code we are testing
        $this->repo->destroy($user->id);

        // Assertions
        $this->tester->dontSeeRecord('users', [
            'email' => 'user@user.com'
        ]);
    }

    /**
     * Test user activation
     */
    public function testActivatingUser()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(true);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.require_activation', true)
                         ->once()->andReturn(true);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.activated', \Mockery::hasKey('user'))->once();

        // Create a new user that is not activated
        $userResponse = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha'
        ]);

        $user         = $userResponse->getPayload()['user'];

        // This is the code we are testing
        $result = $this->repo->activate($user->id, $user->GetActivationCode());

        // Assertions
        $this->assertTrue($result->isSuccessful());
        $this->tester->seeRecord('users', [
            'email'     => 'andrei@prozorov.net',
            'activated' => 1
        ]);
    }

    public function testResendActivationEmail()
    {
        // Mock the Config::has() calls
        $this->configMock->shouldReceive('has')
                         ->with('Sentinel::auth.allow_usernames', false)
                         ->once()->andReturn(true);

        // Mock the Config::get() calls
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.require_activation', true)
                         ->once()->andReturn(true);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.additional_user_fields', [])
                         ->once()->andReturn([]);
        $this->configMock->shouldReceive('get')
                         ->with('Sentinel::auth.default_user_groups', [])
                         ->once()->andReturn($this->sentinelConfiguration['Sentinel::auth.default_user_groups']);

        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.registered', \Mockery::hasKey('user'))->once();
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.resend', \Mockery::hasKey('user'))->once();

        // Create a new user that is not activated
        $userResponse = $this->repo->store([
            'first_name' => 'Andrei',
            'last_name'  => 'Prozorov',
            'username'   => 'theviolinist',
            'email'      => 'andrei@prozorov.net',
            'password'   => 'natasha'
        ]);
        $user         = $userResponse->getPayload()['user'];

        // This is the code we are testing
        $this->repo->resend([
            'email' => 'andrei@prozorov.net'
        ]);

        // No need for assertions here - we are only looking for the
        // 'sentinel.user.resend' event to be fired
    }

    public function testChangeUserPassword()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.passwordchange', \Mockery::hasKey('user'))->once();

        // Find the user we are going to update
        $user = $this->sentry->findUserByLogin('user@user.com');

        // This is the code we are testing
        $result = $this->repo->changePassword([
            'id'          => $user->id,
            'oldPassword' => 'sentryuser',
            'newPassword' => 'sergeyevna'
        ]);

        // Pull the user data again
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Assertions
        $this->assertTrue($result->isSuccessful());
        $this->assertTrue($user->checkHash('sergeyevna', $user->getPassword()));
    }

    /**
     * @expectedException \Cartalyst\Sentry\Throttling\UserSuspendedException
     */
    public function testSuspendUser()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.suspended', \Mockery::hasKey('userId'))->once();

        // Find the user we are going to suspend
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Prepare the Throttle Provider
        $throttleProvider = $this->sentry->getThrottleProvider();

        // This is the code we are testing
        $result = $this->repo->suspend($user->id, 15);

        // Ask the Throttle Provider to gather information for this user
        $throttle = $throttleProvider->findByUserId($user->id);

        // Check the throttle status.  This will throw a 'user suspended' exception.
        $throttle->check();
    }

    /**
     * Test removing a user suspension
     */
    public function testUnsuspendUser()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.suspended', \Mockery::hasKey('userId'))->once();
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.unsuspended', \Mockery::hasKey('userId'))->once();

        // Find the user we are going to suspend
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Prepare the Throttle Provider
        $throttleProvider = $this->sentry->getThrottleProvider();

        // Suspend the user
        $this->repo->suspend($user->id, 15);

        // This is the code we are testing
        $result = $this->repo->unsuspend($user->id);

        // Ask the Throttle Provider to gather information for this user
        $throttle = $throttleProvider->findByUserId($user->id);

        // Check the throttle status.  This should do nothing
        $throttle->check();

        // Assertions
        $this->assertTrue($result->isSuccessful());
    }

    /**
     * @expectedException \Cartalyst\Sentry\Throttling\UserBannedException
     */
    public function testBanUser()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.banned', \Mockery::hasKey('userId'))->once();

        // Find the user we are going to suspend
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Prepare the Throttle Provider
        $throttleProvider = $this->sentry->getThrottleProvider();

        // This is the code we are testing
        $result = $this->repo->ban($user->id);

        // Ask the Throttle Provider to gather information for this user
        $throttle = $throttleProvider->findByUserId($user->id);

        // Check the throttle status.  This will throw a 'user banned' exception.
        $throttle->check();
    }

    public function testUnbanUser()
    {
        // Mock the Event::fire() calls
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.banned', \Mockery::hasKey('userId'))->once();
        $this->dispatcherMock->shouldReceive('fire')
                             ->with('sentinel.user.unbanned', \Mockery::hasKey('userId'))->once();

        // Find the user we are going to suspend
        $user = $this->sentry->findUserByLogin('user@user.com');

        // Prepare the Throttle Provider
        $throttleProvider = $this->sentry->getThrottleProvider();

        // Ban the user
        $this->repo->ban($user->id);

        // This is the code we are testing
        $result = $this->repo->unban($user->id);

        // Ask the Throttle Provider to gather information for this user
        $throttle = $throttleProvider->findByUserId($user->id);

        // Check the throttle status.  This should do nothing.
        $throttle->check();

        // Assertions
        $this->assertTrue($result->isSuccessful());
    }

    public function testRetrieveUserById()
    {
        // This is the code we are testing
        $user = $this->repo->retrieveById(1);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $user);
        $this->assertEquals('admin@admin.com', $user->email);
    }

    public function testRetrieveUserByEmail()
    {
        // This is the code we are testing
        $user = $this->repo->retrieveByCredentials(['email' => 'admin@admin.com']);

        // Assertions
        $this->assertInstanceOf('Sentinel\Models\User', $user);
        $this->assertEquals(1, $user->id);
    }

    public function testRetrieveAllUsers()
    {
        // This is the code we are testing
        $users = $this->repo->all();

        // Assertions
        $this->assertTrue(is_array($users));
        $this->assertEquals(2, count($users));
    }

}