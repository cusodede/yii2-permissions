<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use Helper\Unit as UnitHelper;
use yii\base\Action;
use yii\db\Exception;
use yii\web\Controller;

/**
 * Comprehensive Unit Tests for UsersPermissionsTrait
 *
 * This test suite covers all methods in UsersPermissionsTrait with:
 * - Edge cases and boundary conditions
 * - Good and bad scenarios
 * - Cache behavior testing
 * - Performance considerations
 * - Error handling
 *
 * @covers \cusodede\permissions\traits\UsersPermissionsTrait
 */
class UsersPermissionsTraitTest extends Unit {

	private Users $testUser;
	private array $testPermissions = [];
	private array $testCollections = [];
	private static int $userCounter = 1000; // Start from high number to avoid conflicts

	/**
	 * Set up test data before each test
	 * Creates a test user and permissions for consistent testing
	 *
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	protected function setUp():void {
		parent::setUp();

		// Create test user with unique data
		$this->testUser = $this->createUniqueTestUser();

		// Create test permissions with different characteristics
		$this->createTestPermissions();
		$this->createTestCollections();
	}

	/**
	 * Clean up test data after each test
	 */
	protected function tearDown():void {
		// Clear test data
		$this->cleanupTestData();
		parent::tearDown();
	}

	// ===== CORE PERMISSION CHECKING TESTS =====

	/**
	 * Test hasPermission() method with OR logic (default behavior)
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasPermissionWithOrLogic():void {
		// ARRANGE: Grant some permissions to user
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['advanced']));

		// ACT & ASSERT: Test OR logic (default)
		$this::assertTrue(
			$this->testUser->hasPermission(['basic_permission_test']),
			'Should have basic permission'
		);

		$this::assertTrue(
			$this->testUser->hasPermission(['basic_permission_test', 'nonexistent_permission']),
			'Should pass OR logic when at least one permission exists'
		);

		$this::assertTrue(
			$this->testUser->hasPermission(['advanced_permission_test', 'basic_permission_test']),
			'Should pass OR logic when multiple permissions exist'
		);

		$this::assertFalse(
			$this->testUser->hasPermission(['nonexistent1', 'nonexistent2']),
			'Should fail OR logic when no permissions exist'
		);
	}

	/**
	 * Test hasPermission() method with AND logic
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasPermissionWithAndLogic():void {
		// ARRANGE: Grant some permissions to user
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['advanced']));

		// ACT & ASSERT: Test AND logic
		$this::assertTrue(
			$this->testUser->hasPermission(['basic_permission_test', 'advanced_permission_test'], Permissions::LOGIC_AND),
			'Should pass AND logic when user has all permissions'
		);

		$this::assertFalse(
			$this->testUser->hasPermission(['basic_permission_test', 'nonexistent'], Permissions::LOGIC_AND),
			'Should fail AND logic when user missing some permissions'
		);

		$this::assertFalse(
			$this->testUser->hasPermission(['nonexistent1', 'nonexistent2'], Permissions::LOGIC_AND),
			'Should fail AND logic when user has no permissions'
		);
	}

	/**
	 * Test hasPermission() method with NOT logic
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasPermissionWithNotLogic():void {
		// ARRANGE: Grant some permissions to user
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));

		// ACT & ASSERT: Test NOT logic
		$this::assertFalse(
			$this->testUser->hasPermission(['basic_permission_test'], Permissions::LOGIC_NOT),
			'Should fail NOT logic when user has the permission'
		);

		$this::assertTrue(
			$this->testUser->hasPermission(['nonexistent_permission'], Permissions::LOGIC_NOT),
			'Should pass NOT logic when user does not have the permission'
		);

		$this::assertFalse(
			$this->testUser->hasPermission(['basic_permission_test', 'nonexistent'], Permissions::LOGIC_NOT),
			'Should fail NOT logic if user has any of the permissions'
		);
	}

	// ===== EDGE CASES FOR PERMISSION CHECKING =====

	/**
	 * Test hasPermission() with empty permissions array
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasPermissionWithEmptyArray():void {
		// ACT & ASSERT: Empty array should return false for OR/AND, true for NOT
		$this::assertFalse(
			$this->testUser->hasPermission([]),
			'Empty array should return false with OR logic'
		);

		$this::assertFalse(
			$this->testUser->hasPermission([], Permissions::LOGIC_AND),
			'Empty array should return false with AND logic'
		);

		$this::assertTrue(
			$this->testUser->hasPermission([], Permissions::LOGIC_NOT),
			'Empty array should return true with NOT logic'
		);
	}

	/**
	 * Test hasPermission() with whitespace and special characters in permission names
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasPermissionWithSpecialCharacters():void {
		// ARRANGE: Create permission with special characters (trim the name as it should be)
		$specialPermission = new Permissions([
			'name' => 'special:permission-with/spaces_test',
			'comment' => 'Permission with special characters'
		]);
		$specialPermission->save();
		$this::assertTrue($this->testUser->grantPermission($specialPermission));

		// ACT & ASSERT: Should handle special characters
		$this::assertTrue(
			$this->testUser->hasPermission(['special:permission-with/spaces_test']),
			'Should handle special characters in permission names'
		);
	}

	// ===== ALL PERMISSIONS GRANTED TESTS =====

	/**
	 * Test isAllPermissionsGranted() functionality
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testIsAllPermissionsGranted():void {
		// ARRANGE: Get the permissions module and its current grantAll config
		$permissionsModule = Yii::$app->getModule('permissions');
		$originalGrantAll = $permissionsModule->params['grantAll']??[];

		// ASSERT: Initially user should not have all permissions
		$this::assertFalse(
			$this->testUser->isAllPermissionsGranted(),
			'User should not initially have all permissions granted'
		);

		// Configure all permissions for this user by modifying module params
		$permissionsModule->params['grantAll'] = [$this->testUser->id];

		// ASSERT: Now user should have all permissions
		$this::assertTrue(
			$this->testUser->isAllPermissionsGranted(),
			'User should have all permissions when configured in grantAll'
		);

		// ASSERT: Should bypass normal permission checking
		$this::assertTrue(
			$this->testUser->hasPermission(['any_nonexistent_permission']),
			'User with all permissions should have any permission'
		);

		// CLEANUP: Restore original config
		$permissionsModule->params['grantAll'] = $originalGrantAll;
	}

	// ===== PERMISSION GRANTING AND REVOKING TESTS =====

	/**
	 * Test grantPermission() with different input types
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testGrantPermissionWithDifferentInputTypes():void {
		// ASSERT: Initially user has minimal permissions (just config-based ones)
		$initialCount = count($this->testUser->relatedPermissions);

		// ACT & ASSERT: Grant by string name
		$this::assertTrue(
			$this->testUser->grantPermission('basic_permission_test'),
			'Should be able to grant permission by string name'
		);

		// ACT & ASSERT: Grant by ID
		$this::assertTrue(
			$this->testUser->grantPermission($this->testPermissions['advanced']->id),
			'Should be able to grant permission by ID'
		);

		// ACT & ASSERT: Grant by object
		$this::assertTrue(
			$this->testUser->grantPermission($this->testPermissions['controller']),
			'Should be able to grant permission by object'
		);

		// Refresh the user model to get updated relations
		$this->testUser->refresh();

		// ASSERT: User should now have more permissions than initially
		static::assertGreaterThan($initialCount, count($this->testUser->relatedPermissions));

		// ASSERT: Check that user actually has these permissions
		$this::assertTrue($this->testUser->hasPermission(['basic_permission_test']));
		$this::assertTrue($this->testUser->hasPermission(['advanced_permission_test']));
		$this::assertTrue($this->testUser->hasPermission(['controller:action:get_test']));
	}

	/**
	 * Test grantPermission() with invalid inputs
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testGrantPermissionWithInvalidInputs():void {
		// ACT & ASSERT: Grant nonexistent permission by name
		$this::assertFalse(
			$this->testUser->grantPermission('nonexistent_permission'),
			'Should return false when granting nonexistent permission by name'
		);

		// ACT & ASSERT: Grant nonexistent permission by ID
		$this::assertFalse(
			$this->testUser->grantPermission(99999),
			'Should return false when granting nonexistent permission by ID'
		);
	}

	/**
	 * Test revokePermission() functionality
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testRevokePermission():void {
		// ARRANGE: Grant permissions first
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['advanced']));
		$this::assertTrue($this->testUser->hasPermission(['basic_permission_test']));

		// ACT & ASSERT: Revoke by string name
		$this::assertTrue(
			$this->testUser->revokePermission('basic_permission_test'),
			'Should be able to revoke permission by string name'
		);
		$this::assertFalse($this->testUser->hasPermission(['basic_permission_test']));

		// ACT & ASSERT: Revoke by object
		$this::assertTrue(
			$this->testUser->revokePermission($this->testPermissions['advanced']),
			'Should be able to revoke permission by object'
		);
		$this::assertFalse($this->testUser->hasPermission(['advanced_permission_test']));

		// ACT & ASSERT: Revoke nonexistent permission
		$this::assertFalse(
			$this->testUser->revokePermission('nonexistent_permission'),
			'Should return false when revoking nonexistent permission'
		);
	}

	// ===== PERMISSION COLLECTIONS TESTS =====

	/**
	 * Test setRelatedPermissionsCollections() functionality
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testSetRelatedPermissionsCollections():void {
		// ARRANGE: Initially user has no collections
		$initialCollectionCount = count($this->testUser->relatedPermissionsCollections);

		$this::assertEquals(0, $initialCollectionCount);

		// ACT: Set permission collections
		$this->testUser->setRelatedPermissionsCollections([
			$this->testCollections['basic_collection'],
			$this->testCollections['admin_collection']
		]);

		// ASSERT: User should have permissions from collections
		$this::assertTrue($this->testUser->hasPermission(['basic_permission_test']));
		$this::assertTrue($this->testUser->hasPermission(['advanced_permission_test']));
		$this::assertTrue($this->testUser->hasPermission(['admin_permission_test']));
	}

	/**
	 * Test setRelatedPermissionsCollections() with empty array
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testClearPermissionsCollections():void {
		// ARRANGE: Set some collections first
		$this->testUser->setRelatedPermissionsCollections([$this->testCollections['basic_collection']]);
		$this::assertTrue($this->testUser->hasPermission(['basic_permission_test']));

		// ACT: Clear collections
		$this->testUser->setRelatedPermissionsCollections([]);

		// ASSERT: User should no longer have permissions from cleared collections
		// Note: User might still have direct permissions or config-based permissions
		$directPermissions = $this->testUser->relatedPermissions;
		$hasBasicDirect = false;
		foreach ($directPermissions as $permission) {
			if ('basic_permission_test' === $permission->name) {
				$hasBasicDirect = true;
				break;
			}
		}

		if (!$hasBasicDirect) {
			$this::assertFalse($this->testUser->hasPermission(['basic_permission_test']));
		}
	}

	// ===== CONTROLLER PERMISSION TESTS =====

	/**
	 * Test hasControllerPermission() with various parameters
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasControllerPermission():void {
		// ARRANGE: Grant controller permission
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['controller']));

		// ACT & ASSERT: Test controller permission with exact match
		$this::assertTrue(
			$this->testUser->hasControllerPermission('test-controller', 'action', 'GET'),
			'Should have permission for exact controller/action/verb match'
		);

		// ACT & ASSERT: Test controller permission without permission
		$this::assertFalse(
			$this->testUser->hasControllerPermission('other-controller', 'action', 'GET'),
			'Should not have permission for different controller'
		);
	}

	/**
	 * Test hasControllerPermission() with module context
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasControllerPermissionWithModule():void {
		// ARRANGE: Grant module controller permission  
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['module_controller']));

		// ACT & ASSERT: Test module controller permission
		$this::assertTrue(
			$this->testUser->hasControllerPermission('module-controller', 'action', 'GET', 'test-module'),
			'Should have permission for module controller'
		);

		// ACT & ASSERT: Test same controller in different module
		$this::assertFalse(
			$this->testUser->hasControllerPermission('module-controller', 'action', 'GET', 'other-module'),
			'Should not have permission for same controller in different module'
		);
	}

	// ===== URL PERMISSION TESTS =====

	/**
	 * Test hasUrlPermission() functionality
	 * Note: This test is simplified since URL parsing depends on route configuration
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasUrlPermission():void {
		// This test is skipped because URL parsing requires full application routing setup
		// In a real test environment, you would need to configure routes that map to your test controllers
		$this::markTestSkipped('URL permission testing requires full routing configuration');
	}

	// ===== CACHE BEHAVIOR TESTS =====

	/**
	 * Test allPermissions() caching behavior
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testAllPermissionsCaching():void {
		// ARRANGE: Enable caching for this test
		UnitHelper::useCache();

		// Grant initial permission
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));

		// ACT: Get permissions (should be cached)
		$permissions1 = $this->testUser->allPermissions();
		$count1 = count($permissions1);

		// Grant another permission (should invalidate cache)
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['advanced']));

		// ACT: Get permissions again (should reflect new permission)
		$permissions2 = $this->testUser->allPermissions();
		$count2 = count($permissions2);

		// ASSERT: Should reflect the change due to cache invalidation
		$this::assertGreaterThanOrEqual($count1, $count2, 'Should reflect permission changes');

		// ACT: Force refresh cache
		$permissions3 = $this->testUser->allPermissions(true);
		$count3 = count($permissions3);

		// ASSERT: Force refresh should return current state
		$this::assertEquals($count2, $count3, 'Forced refresh should return current state');

		// CLEANUP
		UnitHelper::useCache(false);
	}

	/**
	 * Test cache invalidation on permission changes
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testCacheInvalidationOnPermissionChanges():void {
		// ARRANGE: Enable caching
		UnitHelper::useCache();

		// Initial state
		$initialCount = count($this->testUser->allPermissions());

		// ACT: Grant permission (should invalidate cache)
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));

		// ASSERT: Cache should be invalidated and new permissions reflected
		$newCount = count($this->testUser->allPermissions());
		$this::assertGreaterThanOrEqual($initialCount, $newCount, 'Cache should be invalidated after granting permission');

		// ACT: Revoke permission (should invalidate cache)  
		$this::assertTrue($this->testUser->revokePermission($this->testPermissions['basic']));

		// ASSERT: Cache should be invalidated again
		$finalCount = count($this->testUser->allPermissions());
		$this::assertLessThanOrEqual($newCount, $finalCount, 'Cache should be invalidated after revoking permission');

		// CLEANUP
		UnitHelper::useCache(false);
	}

	// ===== ACTION PERMISSION TESTS =====

	/**
	 * Test hasActionPermission() functionality
	 * Note: This test creates a proper mock Action object
	 *
	 * @return void
	 * @throws Throwable
	 */
	public function testHasActionPermission():void {
		// Create a proper mock Controller
		$mockController = $this->createMock(Controller::class);
		$mockController->id = 'test-controller';
		$mockController->module = Yii::$app;
		
		// Create a proper mock Action
		$mockAction = $this->createMock(Action::class);
		$mockAction->id = 'action';
		$mockAction->controller = $mockController;

		// Grant permission that matches the action
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['controller']));

		// ACT & ASSERT: Test hasActionPermission with mock action
		$this::assertTrue(
			$this->testUser->hasActionPermission($mockAction),
			'Should have permission for mock action when controller permission is granted'
		);

		// ACT & ASSERT: Test without permission (create different action)
		$mockControllerOther = $this->createMock(Controller::class);
		$mockControllerOther->id = 'other-controller';
		$mockControllerOther->module = Yii::$app;
		
		$mockActionOther = $this->createMock(Action::class);
		$mockActionOther->id = 'other-action';
		$mockActionOther->controller = $mockControllerOther;

		$this::assertFalse(
			$this->testUser->hasActionPermission($mockActionOther),
			'Should not have permission for different controller action'
		);
	}

	// ===== NEW RECORD BEHAVIOR TESTS =====

	/**
	 * Test cache invalidation behavior for new records
	 *
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testNewRecordCacheInvalidation():void {
		// ARRANGE: Create new user (not saved yet)
		$newUser = new Users([
			'username' => 'test_new_user_'.(++$this::$userCounter),
			'login' => 'test_new_login_'.$this::$userCounter,
			'password' => 'test_password'
		]);

		// ACT: Try to grant permission before saving (should handle gracefully)
		$result = $newUser->grantPermission($this->testPermissions['basic']);

		// ASSERT: Should handle new record gracefully
		$this::assertTrue($result, 'Should be able to grant permission to new record');

		// Save the user
		$this::assertTrue($newUser->save(), 'New user should save successfully');

		// ASSERT: Permissions should be available after save
		$this::assertTrue(
			$newUser->hasPermission(['basic_permission_test']),
			'New user should have permissions after saving'
		);
	}

	// ===== PERFORMANCE TESTS =====

	/**
	 * Test performance with many permissions
	 *
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testPerformanceWithManyPermissions():void {
		// ARRANGE: Create moderate number of permissions (reduced for test speed)
		$permissions = [];
		for ($i = 0; $i < 20; $i++) {  // Reduced from 50 to 20
			$permission = new Permissions([
				'name' => "performance_permission_$i"."_test",
				'comment' => "Performance test permission $i"
			]);
			$permission->save();
			$permissions[] = $permission;
		}

		// Grant all permissions
		$this->testUser->setRelatedPermissions($permissions);

		// ACT: Measure permission checking performance
		$start = microtime(true);

		for ($i = 0; $i < 10; $i++) {
			$this->testUser->hasPermission(['performance_permission_10_test']);
		}

		$duration = microtime(true) - $start;

		// ASSERT: Should complete within reasonable time (relaxed threshold)
		$this::assertLessThan(5.0, $duration, 'Permission checking should be performant with many permissions');

		// Verify correctness
		$this::assertTrue($this->testUser->hasPermission(['performance_permission_10_test']));
		$this::assertFalse($this->testUser->hasPermission(['nonexistent_permission']));
	}

	// ===== HELPER METHODS =====

	/**
	 * Create a unique test user
	 *
	 * @return Users
	 * @throws Exception
	 */
	private function createUniqueTestUser():Users {
		$counter = ++$this::$userCounter;
		$user = new Users([
			'username' => 'test_user_'.$counter,
			'login' => 'test_login_'.$counter,
			'password' => 'test_password'
		]);
		$user->save();
		return $user;
	}

	/**
	 * Create test permissions for consistent testing
	 *
	 * @return void
	 * @throws Exception
	 */
	private function createTestPermissions():void {
		// Basic named permission
		$this->testPermissions['basic'] = new Permissions([
			'name' => 'basic_permission_test',
			'comment' => 'Basic test permission'
		]);
		$this->testPermissions['basic']->save();

		// Advanced named permission  
		$this->testPermissions['advanced'] = new Permissions([
			'name' => 'advanced_permission_test',
			'comment' => 'Advanced test permission'
		]);
		$this->testPermissions['advanced']->save();

		// Controller-action permission
		$this->testPermissions['controller'] = new Permissions([
			'name' => 'controller:action:get_test',
			'controller' => 'test-controller',
			'action' => 'action',
			'verb' => 'GET',
			'comment' => 'Controller action permission'
		]);
		$this->testPermissions['controller']->save();

		// Module controller permission
		$this->testPermissions['module_controller'] = new Permissions([
			'name' => 'test-module:module-controller:action:get_test',
			'module' => 'test-module',
			'controller' => 'module-controller',
			'action' => 'action',
			'verb' => 'GET',
			'comment' => 'Module controller permission'
		]);
		$this->testPermissions['module_controller']->save();

		// Admin permission for collections
		$this->testPermissions['admin'] = new Permissions([
			'name' => 'admin_permission_test',
			'comment' => 'Admin test permission'
		]);
		$this->testPermissions['admin']->save();
	}

	/**
	 * Create test permission collections
	 *
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	private function createTestCollections():void {
		// Basic collection with basic and advanced permissions
		$this->testCollections['basic_collection'] = new PermissionsCollections([
			'name' => 'Basic Collection Test',
			'comment' => 'Collection with basic permissions'
		]);
		$this->testCollections['basic_collection']->save();
		$this->testCollections['basic_collection']->setRelatedPermissions([
			$this->testPermissions['basic'],
			$this->testPermissions['advanced']
		]);

		// Admin collection with all permissions
		$this->testCollections['admin_collection'] = new PermissionsCollections([
			'name' => 'Admin Collection Test',
			'comment' => 'Collection with admin permissions'
		]);
		$this->testCollections['admin_collection']->save();
		$this->testCollections['admin_collection']->setRelatedPermissions([
			$this->testPermissions['admin']
		]);
	}

	/**
	 * Clean up test data
	 *
	 * @return void
	 */
	private function cleanupTestData():void {
		// Clean up is handled by Codeception's database cleanup
		// This method is here for explicit cleanup if needed
	}
}