<?php
declare(strict_types = 1);
use cusodede\permissions\models\Permissions;
use yii\web\View;

/**
 * @var View $this
 * @var string $className
 * @var Permissions[] $permissions
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
	public function safeUp() {

	}

	/**
	* {@inheritdoc}
	*/
	public function safeDown() {

	}

}
