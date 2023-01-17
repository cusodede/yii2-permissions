<?php
declare(strict_types = 1);

use yii\web\View;

/**
 * @var View $this
 * @var string $className
 * @var string $permissions
 * @var null|string $namespace
 */

echo "<?php\ndeclare(strict_types = 1);\n";
if (!empty($namespace)) {
	echo "\nnamespace {$namespace};\n";
}
?>
use app\components\db\Migration;

/**
* Class <?= $className."php\n" ?>
*/
class <?= $className ?> extends Migration {
	/**
	* {@inheritdoc}
	*/
	public function safeUp():void {
		$this->upsert('sys_permissions', <?= $permissions ?>)
	}

	/**
	* {@inheritdoc}
	*/
	public function safeDown():void {

	}

}
