<?php

declare(strict_types=1);

namespace ClientFence\Mod\Customcss\Api;

class Guest extends \ClientFence\Api\AbstractApi
{
    /**
     * Called from the widget template as guest.customcss_css so the CSS
     * is also available to signed-out visitors.
     */
    public function css(): string
    {
        return $this->getService()->getCss();
    }
}
