<?php
use Wslim\Common\Component;

include '../../../test_boot.php';

class SomeClass extends Component
{
	protected $options = [
			'name' => 'default'
	];
}

$some = new SomeClass();
$config = [
		'components' => [
				'SomeClass',
				'some2'	=> 'SomeClass',
				'some3'	=> ['SomeClass'],
				'some4'	=> [
						'class' => 'SomeClass',
						'name'	=> 'some3'
				],
				'some5'	=> $some
		],
];
$o = new SomeClass($config);

print_r($o->get('some4'));
print_r($o->get('some5') === $some);
print_r($o->get('some4') === $some);
// print_r($o);
