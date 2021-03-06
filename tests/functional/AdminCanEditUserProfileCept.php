<?php 
$I = new FunctionalTester($scenario);

// Prep
$sentry = $I->grabService('sentry');
$user = $sentry->findUserByLogin('user@user.com');

// Test
$I->amActingAs('admin@admin.com');
$I->wantTo('edit a users profile as an admin');
$I->amOnPage('/users/' . $user->hash . '/edit');
$I->seeElement('form', ['class' => 'form-horizontal']);
$I->fillField('first_name', 'Irina');
$I->fillField('last_name', 'Sergeyevna');
$I->click('Submit Changes');
$I->seeRecord('users', [
    'email'      => 'user@user.com',
    'first_name' => 'Irina',
    'last_name'  => 'Sergeyevna'
]);