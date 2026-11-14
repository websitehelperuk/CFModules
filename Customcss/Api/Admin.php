<?php

declare(strict_types=1);

namespace ClientFence\Mod\Customcss\Api;

class Admin extends \ClientFence\Api\AbstractApi
{
    /**
     * Get the saved stylesheet and whether it can be saved.
     *
     * @return array{css: string, writable: bool}
     */
    public function get_css($data = null): array
    {
        $this->checkPermissions('customcss', 'view');

        $service = $this->getService();

        return [
            'css' => $service->getRawCss(),
            'writable' => $service->isWritable(),
        ];
    }

    /**
     * Save the stylesheet loaded on every client area page.
     *
     * An empty string is allowed: it removes all custom CSS.
     */
    public function save($data): bool
    {
        $this->checkPermissions('customcss', 'manage');

        if (!is_array($data) || !array_key_exists('css', $data) || !is_string($data['css'])) {
            throw new \ClientFence\InformationException('The CSS was not passed.');
        }

        $this->getService()->saveCss($data['css']);

        return true;
    }
}
