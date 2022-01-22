<?php
    /** @var string $namespace */
    /** @var string $className */
    /** @var string $baseClass */

    echo '<?php';
?>

namespace <?= $namespace ?>;

use <?= $baseClass ?> as BaseClass;
use <?= $namespace ?>\DomainActions\ExampleDAM;

class <?= $className ?> extends BaseClass
{
    const METHOD_EXAMPLE = 'example';

    /**
     * @return array
     */
    public function actions()
    {
        return [
            self::METHOD_EXAMPLE => ExampleDAM::class,
        ];
    }
}
