<?php
declare(strict_types = 1);

use yii\web\View;

/**
 * @var View $this
 * @var string $className
 * @var string $code
 * @var null|string $namespace
 */

echo "<?php\ndeclare(strict_types = 1);\n";
if (!empty($namespace)) {
	echo "\nnamespace {$namespace};\n";
}
?>
use app\components\db\Migration;
use cusodede\permissions\models\Permissions;
use cusodede\permissions\models\PermissionsCollections;

/**
* Class <?= $className."php\n" ?>
*/
class <?= $className ?> extends Migration {
	/**
	* {@inheritdoc}
	*/
	public function safeUp():void {
		<?= $code."\n" ?>
	}

	/**
	* {@inheritdoc}
	*/
	public function safeDown():void {

	}

}
