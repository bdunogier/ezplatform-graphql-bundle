<?php
namespace BD\EzPlatformGraphQL\Schema\Domain\Content\Worker;

use BD\EzPlatformGraphQL\Schema\Domain\Content\NameHelper;

class BaseWorker
{
    /**
     * @var \BD\EzPlatformGraphQL\Schema\Domain\Content\NameHelper
     */
    private $nameHelper;

    public function setNameHelper(NameHelper $nameHelper)
    {
        $this->nameHelper = $nameHelper;
    }

    protected function getNameHelper()
    {
        return $this->nameHelper;
    }
}