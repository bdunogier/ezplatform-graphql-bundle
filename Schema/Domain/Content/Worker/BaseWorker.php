<?php
namespace BD\EzPlatformGraphQLBundle\Schema\Domain\Content\Worker;

use BD\EzPlatformGraphQLBundle\Schema\Domain\Content\NameHelper;

class BaseWorker
{
    /**
     * @var \BD\EzPlatformGraphQLBundle\Schema\Domain\Content\NameHelper
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