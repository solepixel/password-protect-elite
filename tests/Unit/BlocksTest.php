<?php
/**
 * Tests for Blocks class.
 *
 * @package PasswordProtectElite\Tests\Unit
 */

namespace PasswordProtectElite\Tests\Unit;

use PasswordProtectElite\Tests\TestCase;
use PasswordProtectElite\Blocks;
use WP_Mock;

/**
 * Blocks test case.
 */
class BlocksTest extends TestCase {

	/**
	 * Test register_blocks action is added.
	 */
	public function test_constructor_adds_action() {
		WP_Mock::expectActionAdded( 'init', array( Blocks::class, 'register_blocks' ) );

		new Blocks();

		$this->assertConditionsMet();
	}

	/**
	 * Test register_blocks registers password entry block.
	 */
	public function test_register_blocks() {
		WP_Mock::userFunction(
			'register_block_type',
			array(
				'times' => 2, // Two blocks: password-entry and protected-content.
			)
		);

		Blocks::register_blocks();

		$this->assertTrue( true );
	}
}

