<?php

namespace Lagueuche\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LagueucheUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
