<?php

namespace Brezgalov\DomainModel\Utils;

use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\helpers\FileHelper;
use yii\web\View;

class FileGenerator extends Model
{
    public $templateFile;

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $modelClass;

    /**
     * @var string
     */
    public $baseModelClass;

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function generateModelFile()
    {
        if (empty($this->namespace)) {
            throw new InvalidConfigException('Namespace is not set');
        }

        if (empty($this->modelClass)) {
            throw new InvalidConfigException('ModelClass is not set');
        }

        $resultDir = dirname($this->path);
        FileHelper::createDirectory($resultDir);

        $resultFileName = "$this->path/{$this->modelClass}.php";

        $templatePath = \Yii::getAlias($this->templateFile);

        $view = new View();

        $code = $view->renderFile($templatePath, [
            'namespace' => $this->namespace,
            'className' => $this->modelClass,
            'baseClass' => $this->baseModelClass,
        ], $this);

        file_put_contents($resultFileName, $code);
    }
}