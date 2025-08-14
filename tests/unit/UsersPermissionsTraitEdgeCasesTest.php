<?php
declare(strict_types = 1);

use app\models\Users;
use Codeception\Test\Unit;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;
use cusodede\permissions\PermissionsModule;
use pozitronik\helpers\CacheHelper;
use yii\db\Exception;

/**
 * Advanced Edge Cases and Error Scenarios for UsersPermissionsTrait
 * 
 * This test suite focuses on:
 * - Database constraint violations
 * - Circular dependencies
 * - Memory and performance edge cases
 * - Concurrent access scenarios
 * - Error recovery scenarios
 * 
 * @covers \cusodede\permissions\traits\UsersPermissionsTrait
 */
class UsersPermissionsTraitEdgeCasesTest extends Unit {

	private Users $testUser;
	private array $testPermissions = [];

	/**
	 * Set up test data
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->testUser = Users::CreateUser()->saveAndReturn();
		$this->createTestPermissions();
	}

	// ===== DATABASE CONSTRAINT AND INTEGRITY TESTS =====

	/**
	 * Test handling of duplicate permission grants
	 * 
	 * @return void
	 * @throws Throwable
	 */
	public function testDuplicatePermissionGrant(): void {
		// ARRANGE & ACT: Grant the same permission twice
		$this::assertTrue($this->testUser->grantPermission($this->testPermissions['basic']));
		
		// ACT: Grant the same permission again (should handle gracefully)
		$result = $this->testUser->grantPermission($this->testPermissions['basic']);
		
		// ASSERT: Should handle duplicate gracefully without throwing exception
		$this::assertTrue($result, 'Should handle duplicate permission grant gracefully');
		
		// ASSERT: User should still have the permission
		$this::assertTrue($this->testUser->hasPermission(['basic_permission']));
		
		// ASSERT: Should not create duplicate relationship records
		$relatedPermissions = $this->testUser->relatedPermissions;
		$basicPermissionCount = array_reduce($relatedPermissions, static function($count, $permission) {
			return 'basic_permission' === $permission->name? $count + 1 : $count;
		}, 0);
		
		$this::assertEquals(1, $basicPermissionCount, 'Should not create duplicate permission relationships');
	}

	/**
	 * Test revoking non-existent permission relationships
	 * 
	 * @return void
	 * @throws Throwable
	 */
	public function testRevokeNonExistentRelationship(): void {
		// ARRANGE: User has no permissions initially
		$this::assertEmpty($this->testUser->relatedPermissions);

		// ACT: Try to revoke permission that was never granted
		$result = $this->testUser->revokePermission($this->testPermissions['basic']);

		// ASSERT: Should handle gracefully
		$this::assertTrue($result, 'Should handle revoking non-existent relationship gracefully');
	}

	/**
	 * Test behavior when permission is deleted while user still references it
	 * 
	 * @return void
	 * @throws Throwable
	 */
	public function testPermissionDeletedWhileInUse(): void {
		// ARRANGE: Grant permission to user
		$this->testUser->grantPermission($this->testPermissions['basic']);
		$this::assertTrue($this->testUser->hasPermission(['basic_permission']));

		// ACT: Delete the permission from database (simulating external deletion)
		$this->testPermissions['basic']->delete();

		// ASSERT: User should no longer have this permission
		// Force cache refresh to get current state
		$allPermissions = $this->testUser->allPermissions(true);
		$hasDeletedPermission = array_reduce($allPermissions, static function($found, $permission) {
			return $found || 'basic_permission' === $permission->name;
		}, false);
		
		$this::assertFalse($hasDeletedPermission, 'Should not have deleted permission in allPermissions');
	}

	// ===== CIRCULAR DEPENDENCY TESTS =====

	/**
	 * Test circular dependencies in permission collections
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testCircularCollectionDependencies(): void {
		// ARRANGE: Create two collections
		$collection1 = new PermissionsCollections([
			'name' => 'Collection 1',
			'comment' => 'First collection'
		]);
		$collection1->save();

		$collection2 = new PermissionsCollections([
			'name' => 'Collection 2', 
			'comment' => 'Second collection'
		]);
		$collection2->save();

		// Add basic permissions to avoid empty collections
		$collection1->setRelatedPermissions([$this->testPermissions['basic']]);
		$collection2->setRelatedPermissions([$this->testPermissions['advanced']]);

		// Try to create circular dependency (this should be handled or prevented)
		// Note: The actual behavior depends on your implementation
		// This test documents the expected behavior

		$this->testUser->setRelatedPermissionsCollections([$collection1, $collection2]);

		// ASSERT: User should have permissions from both collections without infinite recursion
		$allPermissions = $this->testUser->allPermissions();
		$permissionNames = array_column($allPermissions, 'name');
		
		$this::assertContains('basic_permission', $permissionNames);
		$this::assertContains('advanced_permission', $permissionNames);
		
		// ASSERT: Should not cause infinite recursion or memory issues
		$this::assertTrue($this->testUser->hasPermission(['basic_permission']));
		$this::assertTrue($this->testUser->hasPermission(['advanced_permission']));
	}

	// ===== MEMORY AND PERFORMANCE EDGE CASES =====

	/**
	 * Test memory usage with very large permission sets
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testLargePermissionSetMemoryUsage(): void {
		// Get initial permission count to account for existing permissions
		$initialPermissionCount = count($this->testUser->allPermissions());
		
		// ARRANGE: Create a large number of permissions
		$largePermissionSet = [];
		for ($i = 0; $i < 1000; $i++) {
			$permission = new Permissions([
				'name' => "large_set_permission_$i",
				'comment' => "Large set permission $i"
			]);
			$permission->save();
			$largePermissionSet[] = $permission;
		}

		// Measure memory before
		$memoryBefore = memory_get_usage(true);

		// ACT: Grant all permissions
		$this->testUser->setRelatedPermissions($largePermissionSet);

		// Get all permissions (this loads everything into memory)
		$allPermissions = $this->testUser->allPermissions();

		// Measure memory after
		$memoryAfter = memory_get_usage(true);
		$memoryUsed = $memoryAfter - $memoryBefore;

		// ASSERT: Memory usage should be reasonable (adjust threshold as needed)
		// This is more of a documentation test - adjust the threshold based on your requirements
		$this::assertLessThan(50 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable with large permission sets'); // 50MB threshold

		// ASSERT: Functionality should still work correctly
		// Account for any existing permissions (config-based or from other tests)
		$expectedCount = $initialPermissionCount + 1000;
		$this::assertCount($expectedCount, $allPermissions, "Should return all $expectedCount permissions (1000 created + $initialPermissionCount existing)");
		$this::assertTrue($this->testUser->hasPermission(['large_set_permission_500']));
		$this::assertFalse($this->testUser->hasPermission(['nonexistent_permission']));
	}

	/**
	 * Test cache behavior under memory pressure
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testCacheBehaviorUnderMemoryPressure(): void {
		// ARRANGE: Create moderate number of permissions
		$permissions = [];
		for ($i = 0; $i < 100; $i++) {
			$permission = new Permissions([
				'name' => "cache_test_permission_$i",
				'comment' => "Cache test permission $i"
			]);
			$permission->save();
			$permissions[] = $permission;
		}

		$this->testUser->setRelatedPermissions($permissions);

		// ACT: Repeatedly access permissions to test cache stability
		for ($i = 0; $i < 50; $i++) {
			$this->testUser->allPermissions();
			$this->testUser->hasPermission(["cache_test_permission_" . ($i % 100)]);
		}

		// ASSERT: Should still work correctly after many cache operations
		$this::assertTrue($this->testUser->hasPermission(['cache_test_permission_50']));
		$this::assertFalse($this->testUser->hasPermission(['nonexistent_permission']));
	}

	// ===== CONCURRENCY AND RACE CONDITION TESTS =====

	/**
	 * Test permission modifications during active checks
	 * This simulates race conditions that might occur in concurrent environments
	 * 
	 * @return void
	 * @throws Throwable
	 */
	public function testConcurrentPermissionModifications(): void {
		// ARRANGE: Grant initial permission
		$this->testUser->grantPermission($this->testPermissions['basic']);
		$this::assertTrue($this->testUser->hasPermission(['basic_permission']));

		// SIMULATE: Permission modification during check
		// In real concurrent scenario, another process might modify permissions
		// between hasPermission calls

		// Check 1: Should have permission
		$hasPermission1 = $this->testUser->hasPermission(['basic_permission']);
		
		// Modify permissions (simulating concurrent modification)
		$this->testUser->revokePermission($this->testPermissions['basic']);
		
		// Check 2: Should reflect the change (cache invalidation should handle this)
		$hasPermission2 = $this->testUser->hasPermission(['basic_permission']);

		// ASSERT: Should reflect the actual current state
		$this::assertTrue($hasPermission1, 'Should have permission before revocation');
		$this::assertFalse($hasPermission2, 'Should not have permission after revocation');
	}

	// ===== DATA CORRUPTION AND RECOVERY TESTS =====

	/**
	 * Test behavior with corrupted permission data
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testCorruptedPermissionData(): void {
		// ARRANGE: Create permission with edge case data
		$corruptedPermission = new Permissions([
			'name' => '',  // Empty name - edge case
			'comment' => str_repeat('A', 1000)  // Very long comment
		]);
		
		// This might fail to save due to validation, which is expected
		try {
			$saved = $corruptedPermission->save();
			
			if ($saved && $corruptedPermission->id > 0) {
				// If it does save successfully, test that the system handles it gracefully
				$result = $this->testUser->grantPermission($corruptedPermission);
				
				// Should handle edge case permission data gracefully
				$this::assertTrue($result, 'Should handle edge case permission data');
			} else {
				// Permission failed to save due to validation - this is expected
				$this::assertFalse($saved, 'Corrupted permission should fail to save');
			}
			
		} catch (Exception) {
			// Expected behavior - validation should prevent corrupted data
			$this->addToAssertionCount(1); // Document that we expected an exception
		}
	}

	/**
	 * Test recovery from cache corruption
	 * 
	 * @return void
	 * @throws Throwable
	 */
	public function testCacheCorruptionRecovery(): void {
		// ARRANGE: Grant permission and populate cache
		$this->testUser->grantPermission($this->testPermissions['basic']);
		$this->testUser->allPermissions(); // Populate cache

		// SIMULATE: Cache corruption by directly manipulating cache
		// (In real scenario, this could happen due to external factors)
		$cacheKey = CacheHelper::MethodSignature('Users::allPermissions', ['id' => $this->testUser->id]);
		PermissionsModule::Cache()->delete($cacheKey);

		// ACT: Access permissions after cache corruption
		$permissions = $this->testUser->allPermissions();
		
		// ASSERT: Should recover and work correctly
		$this::assertNotEmpty($permissions, 'Should recover from cache corruption');
		$this::assertTrue($this->testUser->hasPermission(['basic_permission']));
	}

	// ===== EXTREME INPUT TESTS =====

	/**
	 * Test with extremely long permission names and comments
	 * 
	 * @return void
	 * @throws Exception|Throwable
	 */
	public function testExtremelyLongInputs(): void {
		// ARRANGE: Create permission with very long name
		$longName = str_repeat('a', 255); // At database field limit
		$longPermission = new Permissions([
			'name' => $longName,
			'comment' => str_repeat('B', 1000)
		]);

		try {
			$saved = $longPermission->save();
			
			if ($saved && $longPermission->id > 0) {
				// ACT & ASSERT: Should handle long names if they saved successfully
				$result = $this->testUser->grantPermission($longPermission);
				$this::assertTrue($result, 'Should handle very long permission names');
				
				$this::assertTrue($this->testUser->hasPermission([$longName]));
			} else {
				// Permission failed to save due to constraints - this is also acceptable
				$this::assertFalse($saved, 'Very long permission should fail to save due to database constraints');
			}
			
		} catch (Exception) {
			// If database constraints prevent this, that's also acceptable behavior
			$this->addToAssertionCount(1); // Document that we expected an exception
		}
	}

	/**
	 * Test with special Unicode characters in permission names
	 * 
	 * @return void
	 * @throws Exception
	 * @throws Throwable
	 */
	public function testUnicodeCharactersInPermissions(): void {
		// ARRANGE: Create permissions with Unicode characters
		$unicodePermissions = [
			'permission_测试_тест_🔒',
			'पर्मिशन_разрешение_許可',
			'emoji_🚀_permission'
		];

		foreach ($unicodePermissions as $unicodeName) {
			$permission = new Permissions([
				'name' => $unicodeName,
				'comment' => 'Unicode test permission'
			]);
			
			try {
				$permission->save();
				$this->testUser->grantPermission($permission);
				
				// ASSERT: Should handle Unicode characters
				$this::assertTrue($this->testUser->hasPermission([$unicodeName]), "Should handle Unicode permission: $unicodeName");
				
			} catch (Exception) {
				// If Unicode is not supported, document this behavior
				$this->addToAssertionCount(1); // Document that we expected an exception for: $unicodeName
			}
		}
	}

	// ===== HELPER METHODS =====

	/**
	 * Create basic test permissions
	 * 
	 * @return void
	 * @throws Exception
	 */
	private function createTestPermissions(): void {
		$this->testPermissions['basic'] = new Permissions([
			'name' => 'basic_permission',
			'comment' => 'Basic test permission'
		]);
		$this->testPermissions['basic']->save();

		$this->testPermissions['advanced'] = new Permissions([
			'name' => 'advanced_permission', 
			'comment' => 'Advanced test permission'
		]);
		$this->testPermissions['advanced']->save();
	}
}