<?php

namespace MauticPlugin\MauticCitrixBundle\Helper;

class CitrixHelper
{
    /**
     * This is necessary because the core Code needs one of the refactored GoToBundle-Classes.
     *
     * @param $integration
     *
     * @return bool
     */
    public static function isAuthorized($integration)
    {
        return \MauticPlugin\MauticGoToBundle\Helper\GoToHelper::isAuthorized($integration);
    }
}
