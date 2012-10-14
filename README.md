# JoinableBehavior
JoinableBehavior takes an array in the structure similar to that of ContainableBehavior and creates automatic joins between the listed models, keeping your code clean.

## Installation
1. `cd app`
2. `git submodule add https://github.com/tigrang/cakephp-joinable.git Plugin/Joinable`
3. Add `CakePlugin::load('Joinable')` in `app/Config/bootstrap.php`
4. Add `public $actsAs = array('Joinable.Joinable');` to `AppModel`

## Options:
* `type` - Type of join to use. Default: `LEFT`
* `conditions` - Conditions to join on, if `true` is used, conditions will be set automatically. Default: `true`

## Usage:
	$this->User->find('all', array(
		'joins' => array(
			'Profile' => array(
				'type' => 'RIGHT',
			),
			'Subscription' => array(
				'conditions' => '`Subscription`.`id` = `User`.`subscription_id` AND `Subscription`.`active` = 1',
				'SubscriptionPlan',
			),
		),
	));