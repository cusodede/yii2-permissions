<?php
declare(strict_types = 1);

use yii\web\View;

/**
 * @var View $this
 * @var string $className
 * @var string $permissions_collections_to_permissions
 * @var null|string $namespace
 */

echo "<?php\ndeclare(strict_types = 1);\n";
if (!empty($namespace)) {
	echo "\nnamespace {$namespace};\n";
}
?>
use app\components\db\Migration;

/**
* Class <?= $className."\n" ?>
*/
class <?= $className ?> extends Migration {
	/**
	* {@inheritdoc}
	*/
	public function safeUp():void {
		$this->upsert('sys_relation_permissions_collections_to_permissions', <?= $permissions_collections_to_permissions ?>)
	}

	/**
	* {@inheritdoc}
	*/
	public function safeDown():void {

	}

}
