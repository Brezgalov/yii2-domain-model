<?php

namespace Brezgalov\DomainModel\Commands;

use Brezgalov\DomainModel\Utils\CreateDomainModelDirectoryService;
use yii\console\Controller;

class DomainDirectoriesController extends Controller
{
    /**
     * Формирует структуру папок доменной модели в указанной директории
     */
    public function actionIndex()
    {
        //$domainsDir, $modelName;


        $a = new CreateDomainModelDirectoryService();
        $a->domainName = 'Timeslot';

        $a->createDomainFolder();
    }
}