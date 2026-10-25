<?php

use PHPUnit\Framework\TestCase;

class ProviderDemoTest extends TestCase {

	public static function provideNumbers() {
		return array(
			array( 2, 3, 5 ),
			array( 10, 5, 15 ),
			array( 0, 0, 0 ),
		);
	}

	/**
	 * @dataProvider provideNumbers
	 */
	public function test_adding( $a, $b, $expected ) {
		$this->assertSame( $expected, $a + $b );
	}
}