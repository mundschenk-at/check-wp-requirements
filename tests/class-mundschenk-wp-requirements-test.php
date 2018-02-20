<?php
/**
 *  This file is part of mundschenk-at/check-wp-requirements.
 *
 *  Copyright 2017-2018 Peter Putzer.
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or ( at your option ) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *  @package mundschenk-at/check-wp-requirements/tests
 *  @license http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Mundschenk\Check_WordPress_Requirements\Tests;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

use org\bovigo\vfs\vfsStream;

use Mockery as m;

/**
 * Mundschenk_WP_Requirements unit test.
 *
 * @coversDefaultClass \Mundschenk_WP_Requirements
 * @usesDefaultClass \Mundschenk_WP_Requirements
 *
 * @uses Mundschenk_WP_Requirements::__construct
 */
class Mundschenk_WP_Requirements_Test extends TestCase {

	/**
	 * Test fixture.
	 *
	 * @var \Mundschenk_WP_Requirements
	 */
	protected $req;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() { // @codingStandardsIgnoreLine

		// Set up virtual filesystem.
		vfsStream::setup( 'root', null, [
			'plugin' => [
				'partials' => [
					'requirements-error-notice.php' => 'REQUIREMENTS_ERROR',
				],
			],
		] );
		set_include_path( 'vfs://root/' ); // @codingStandardsIgnoreLine

		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing( function( $array, $defaults ) {
			return \array_merge( $defaults, $array );
		} );

		$this->req = m::mock( \Mundschenk_WP_Requirements::class, [
			'Foobar',
			'plugin/plugin.php',
			'textdomain',
			[
				'php'       => '5.6.0',
				'multibyte' => true,
				'utf-8'     => true,
			],
		] )->shouldAllowMockingProtectedMethods()->makePartial();

		parent::setUp();
	}

	/**
	 * Necesssary clean-up work.
	 */
	protected function tearDown() { // @codingStandardsIgnoreLine
		parent::tearDown();
	}



	/**
	 * Test constructor.
	 *
	 * @covers ::__construct
	 */
	public function test_constructor() {
		Functions\expect( 'wp_parse_args' )->once()->andReturnUsing( function( $array, $defaults ) {
			return \array_merge( $defaults, $array );
		} );

		$req = m::mock( \Mundschenk_WP_Requirements::class, [ 'Foobar', 'plugin/plugin.php', 'textdomain', [ 'php' => '5.3.5' ] ] );

		$this->assertAttributeSame( 'plugin/plugin.php', 'plugin_file', $req );
		$this->assertAttributeSame( 'Foobar', 'plugin_name', $req );
		$this->assertAttributeSame( 'textdomain', 'textdomain', $req );

		$requirements = $this->getValue( $req, 'install_requirements', \Mundschenk_WP_Requirements::class );
		$this->assertArrayHasKey( 'php', $requirements );
		$this->assertArrayHasKey( 'multibyte', $requirements );
		$this->assertArrayHasKey( 'utf-8', $requirements );

		$this->assertEquals( '5.3.5', $requirements['php'] );
		$this->assertFalse( $requirements['multibyte'] );
		$this->assertFalse( $requirements['utf-8'] );
	}

	/**
	 * Test display_error_notice.
	 *
	 * @covers ::display_error_notice
	 */
	public function test_display_error_notice() {
		$this->expectOutputString( 'REQUIREMENTS_ERROR' );

		$this->invokeMethod( $this->req, 'display_error_notice', [ 'foo' ] );
	}

	/**
	 * Test display_error_notice.
	 *
	 * @covers ::display_error_notice
	 *
	 * @expectedExceptionMessage Too few arguments to function
	 */
	public function test_display_error_notice_no_arguments() {
		$this->expectOutputString( '' );

		// PHP < 7.0 raises an error instead of throwing an "exception".
		if ( version_compare( phpversion(), '7.0.0', '<' ) ) {
			$this->expectException( \PHPUnit_Framework_Error::class );
		} elseif ( version_compare( phpversion(), '7.1.0', '<' ) ) {
			$this->expectException( \PHPUnit\Framework\Error\Warning::class );
		} else {
			$this->expectException( \ArgumentCountError::class );
		}

		$this->invokeMethod( $this->req, 'display_error_notice', [] );
	}

	/**
	 * Test display_error_notice.
	 *
	 * @covers ::display_error_notice
	 */
	public function test_display_error_notice_empty_format() {
		$this->expectOutputString( '' );

		$this->invokeMethod( $this->req, 'display_error_notice', [ '' ] );
	}

	/**
	 * Test admin_notices_php_version_incompatible.
	 *
	 * @covers ::admin_notices_php_version_incompatible
	 */
	public function test_admin_notices_php_version_incompatible() {
		Functions\expect( '__' )->with( m::type( 'string' ), 'textdomain' )->atLeast()->once()->andReturn( 'translated' );
		$this->req->shouldReceive( 'display_error_notice' )->once();

		$this->assertNull( $this->req->admin_notices_php_version_incompatible() );
	}

	/**
	 * Test admin_notices_mbstring_incompatible.
	 *
	 * @covers ::admin_notices_mbstring_incompatible
	 */
	public function test_admin_notices_mbstring_incompatible() {
		Functions\expect( '__' )->with( m::type( 'string' ), 'textdomain' )->atLeast()->once()->andReturn( 'translated' );
		$this->req->shouldReceive( 'display_error_notice' )->once();

		$this->assertNull( $this->req->admin_notices_mbstring_incompatible() );
	}

	/**
	 * Test admin_notices_charset_incompatible.
	 *
	 * @covers ::admin_notices_charset_incompatible
	 */
	public function test_admin_notices_charset_incompatible() {
		Functions\expect( '__' )->with( m::type( 'string' ), 'textdomain' )->atLeast()->once()->andReturn( 'translated' );
		Functions\expect( 'get_bloginfo' )->with( 'charset' )->once()->andReturn( '8859-1' );
		$this->req->shouldReceive( 'display_error_notice' )->once();

		$this->assertNull( $this->req->admin_notices_charset_incompatible() );
	}

	/**
	 * Provides data for testing check_utf8_support.
	 *
	 * @return array
	 */
	public function provide_check_utf8_support_data() {
		return [
			[ 'utf-8', true ],
			[ 'UTF-8', true ],
			[ '8859-1', false ],
			[ 'foobar', false ],
		];
	}

	/**
	 * Test check_utf8_support.
	 *
	 * @covers ::check_utf8_support
	 *
	 * @dataProvider provide_check_utf8_support_data
	 *
	 * @param string $charset  The blog charset.
	 * @param bool   $expected The expected result.
	 */
	public function test_check_utf8_support( $charset, $expected ) {
		Functions\expect( 'get_bloginfo' )->with( 'charset' )->once()->andReturn( $charset );

		$this->assertSame( $expected, $this->invokeMethod( $this->req, 'check_utf8_support' ) );
	}

	/**
	 * Test check_utf8_support.
	 *
	 * @covers ::check_multibyte_support
	 */
	public function test_check_multibyte_support() {
		// This will be true because mbstring is a requirement for running the test suite.
		$this->assertTrue( $this->invokeMethod( $this->req, 'check_multibyte_support' ) );
	}

	/**
	 * Provides data for testing check.
	 *
	 * @return array
	 */
	public function provide_check_data() {
		return [
			[ true, true, true, true, true ],
			[ false, false, false, true, false ],
			[ true, false, false, true, false ],
			[ false, true, false, true, false ],
			[ false, false, true, true, false ],
			[ true, true, true, false, true ],
			[ false, false, false, false, false ],
			[ true, false, false, false, false ],
			[ false, true, false, false, false ],
			[ false, false, true, false, false ],
			[ true, true, false, true, false ],
		];
	}

	/**
	 * Test check.
	 *
	 * @covers ::check
	 *
	 * @dataProvider provide_check_data
	 *
	 * @param  bool $php_version PHP version check flag.
	 * @param  bool $multibyte   Multibyte support check flag.
	 * @param  bool $charset     Charset check flag.
	 * @param  bool $admin       Result of is_admin().
	 * @param  bool $expected    Expected result.
	 */
	public function test_check( $php_version, $multibyte, $charset, $admin, $expected ) {
		Functions\expect( 'is_admin' )->zeroOrMoreTimes()->andReturn( $admin );

		// Fake PHP version check.
		if ( ! $php_version ) {
			$this->setValue( $this->req, 'install_requirements', [
				'php'       => '999.0.0',
				'multibyte' => true,
				'utf-8'     => true,
			], \Mundschenk_WP_Requirements::class );
		}

		$this->req->shouldReceive( 'check_multibyte_support' )->times( (int) $php_version )->andReturn( $multibyte );
		$this->req->shouldReceive( 'check_utf8_support' )->times( (int) ( $php_version && $multibyte ) )->andReturn( $charset );

		if ( $admin ) {
			$php_times       = $php_version ? 0 : 1;
			$multibyte_times = ! $php_version || $multibyte ? 0 : 1;
			$charset_times   = ! $php_version || ! $multibyte || $charset ? 0 : 1;

			if ( ! $expected ) {
				Functions\expect( 'load_plugin_textdomain' )->once()->with( 'textdomain' );
			}

			Actions\expectAdded( 'admin_notices' )->with( [ $this->req, 'admin_notices_php_version_incompatible' ] )->times( $php_times );
			Actions\expectAdded( 'admin_notices' )->with( [ $this->req, 'admin_notices_mbstring_incompatible' ] )->times( $multibyte_times );
			Actions\expectAdded( 'admin_notices' )->with( [ $this->req, 'admin_notices_charset_incompatible' ] )->times( $charset_times );
		}

		$this->assertSame( $expected, $this->invokeMethod( $this->req, 'check' ) );
	}

	/**
	 * Test deactivate_plugin.
	 *
	 * @covers ::deactivate_plugin
	 */
	public function test_deactivate_plugin() {
		Functions\expect( 'plugin_basename' )->with( 'plugin/plugin.php' )->once()->andReturn( 'plugin' );
		Functions\expect( 'deactivate_plugins' )->with( 'plugin' )->once();

		$this->assertNull( $this->req->deactivate_plugin() );
	}
}
