<?php 
$I = new FunctionalTester($scenario);

// Create the new unactivated user account
$I->wantTo('activate a new user account');
$I->amOnPage('/register');
$I->fillField('username', 'tuzenbach');
$I->fillField('email','tuzenbach@aol.com');
$I->fillField('password','irina1899');
$I->fillField('password_confirmation', 'irina1899');
$I->click('Register', '.btn');
$I->see('Your account has been created.');
$I->seeRecord('users', [
    'email' => 'tuzenbach@aol.com',
    'username' => 'tuzenbach'
]);

// Prep for activation
$sentry = $I->grabService('sentry');
$user = $sentry->findUserByLogin('tuzenbach@aol.com');

// Attempt activation
$I->amOnPage(route('sentinel.reactivate.form'));
$I->fillField('email','tuzenbach@aol.com');
$I->click('Resend', '.btn');
$I->see('Check your email for the confirmation link');

