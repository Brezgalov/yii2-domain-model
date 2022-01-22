<?php

namespace Brezgalov\DomainModel\Utils;

use Brezgalov\DomainModel\BasicDomainActionModel;
use Brezgalov\DomainModel\BasicDomainModel;
use yii\base\Exception;
use yii\base\Model;
use yii\helpers\FileHelper;

class CreateDomainModelDirectoryService extends Model
{
    /**
     * @var string
     */
    public $domainName;

    /**
     * @var FileGenerator
     */
    public $modelGenerator;

    /**
     * @var string
     */
    public $baseDomainNamespace = 'app\domain';

    /**
     * @var string
     */
    public $actionsFolder = 'DomainActions';

    /**
     * CreateDomainDirectoryService constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        if (empty($this->modelGenerator)) {
            $this->modelGenerator = new FileGenerator();
        }
    }

    /**
     * @return \string[][]
     */
    protected function getFoldersStructure()
    {
        return [
            $this->actionsFolder => [],
            'DelayedEvents' => ['.gitkeep'],
            'ResultFormatters' => ['.gitkeep'],
        ];
    }

    /**
     * @throws \yii\base\Exception
     */
    public function createDomainFolder()
    {
        $basePath = \Yii::getAlias('@' . str_replace('\\', '/', $this->baseDomainNamespace));
        $domainPath = "{$basePath}/{$this->domainName}";

        if (is_dir($domainPath)) {
            throw new Exception('Domain dir is already initialized');
        }

        // generate base directory
        FileHelper::createDirectory($domainPath);

        foreach ($this->getFoldersStructure() as $folder => $fileOrders) {
            $dirPath = "{$domainPath}/{$folder}";

            FileHelper::createDirectory($dirPath);

            foreach ($fileOrders as $fileOrder) {
                if (is_string($fileOrder)) {
                    $fileName = "{$dirPath}/{$fileOrder}";
                    if (!is_file($fileName)) {
                        file_put_contents($fileName, '');
                    }
                }
            }
        }

        $modelGen = clone $this->modelGenerator;

        $modelGen->templateFile = __DIR__ . '/FileTemplates/ExampleDomainModelTemplateFile.php';
        $modelGen->path = $domainPath;
        $modelGen->namespace = $this->baseDomainNamespace . "\\{$this->domainName}";
        $modelGen->modelClass = "{$this->domainName}DM";
        $modelGen->baseModelClass = BasicDomainModel::class;

        $modelGen->generateModelFile();

        $actionGen = clone $this->modelGenerator;

        $actionGen->templateFile = __DIR__ . '/FileTemplates/ExampleDomainActionModelTemplateFile.php';
        $actionGen->path = "{$domainPath}/{$this->actionsFolder}";
        $actionGen->namespace = $this->baseDomainNamespace . "\\{$this->domainName}\\{$this->actionsFolder}";
        $actionGen->modelClass = "ExampleDAM";
        $actionGen->baseModelClass = BasicDomainActionModel::class;

        $actionGen->generateModelFile();
    }
}