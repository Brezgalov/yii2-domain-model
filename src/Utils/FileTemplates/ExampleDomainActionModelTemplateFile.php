<?php
/** @var string $namespace */
/** @var string $className */
/** @var string $baseClass */

echo '<?php';
?>

namespace <?= $namespace ?>;

use <?= $baseClass ?> as BaseClass;

class <?= $className ?> extends BaseClass
{
    /**
     * @return string
     */
    public function run()
    {
        return "Hello world!";
    }
}