<?php

declare(strict_types=1);

namespace ClientFence\Mod\Customcss;

class Service implements \ClientFence\InjectionAwareInterface, \ClientFence\Interfaces\WidgetProviderInterface
{
    /**
     * Widget slot the stylesheet is output in.
     *
     * 'client.theme.body.start' is rendered by the Palisade layout right after
     * <body>, on every page including signed-out and gated ones. It comes after
     * palisade.css and the brand stylesheet in the cascade, so these rules win.
     */
    private const SLOTS = [
        'client.theme.body.start',
    ];

    /** Largest stylesheet the editor will save, in bytes. */
    private const MAX_BYTES = 262144;

    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function getModulePermissions(): array
    {
        return [
            'view' => [
                'type' => 'bool',
                'display_name' => __trans('View custom CSS'),
                'description' => __trans('Allows the staff member to view the custom CSS.'),
            ],
            'manage' => [
                'type' => 'bool',
                'display_name' => __trans('Manage custom CSS'),
                'description' => __trans('Allows the staff member to change the CSS loaded on every client area page.'),
            ],
        ];
    }

    public function getWidgets(): array
    {
        $widgets = [];
        foreach (self::SLOTS as $slot) {
            $widgets[] = ['slot' => $slot, 'template' => 'mod_customcss_style'];
        }

        return $widgets;
    }

    public function upgrade(string $from): void
    {
        // Nothing to migrate: the stylesheet lives in custom.css.
    }

    private function file(): string
    {
        return __DIR__ . '/custom.css';
    }

    /**
     * The stylesheet exactly as saved, for the editor.
     */
    public function getRawCss(): string
    {
        $file = $this->file();
        if (!is_file($file) || !is_readable($file)) {
            return '';
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return '';
        }

        // A shared lock, so a visitor never reads a half-written file while staff save.
        flock($handle, LOCK_SH);
        $raw = stream_get_contents($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $raw === false ? '' : $raw;
    }

    /**
     * True when the web server user can write custom.css (or create it).
     */
    public function isWritable(): bool
    {
        $file = $this->file();

        return is_file($file) ? is_writable($file) : is_writable(__DIR__);
    }

    /**
     * Saves the stylesheet. Throws an InformationException with a message
     * staff can act on when it cannot.
     */
    public function saveCss(string $css): void
    {
        // Normalise line endings and drop a byte order mark if one was pasted in.
        $css = str_replace(["\r\n", "\r"], "\n", $css);
        $css = preg_replace('/^\xEF\xBB\xBF/', '', $css) ?? $css;

        if (strlen($css) > self::MAX_BYTES) {
            throw new \ClientFence\InformationException('The stylesheet is too large (limit :kb KB).', [':kb' => self::MAX_BYTES / 1024]);
        }

        if (!$this->isWritable()) {
            throw new \ClientFence\InformationException('modules/Customcss/custom.css is not writable by the web server user.');
        }

        // Written in place under an exclusive lock, so only the file itself
        // needs to be writable, not the module folder.
        $handle = fopen($this->file(), 'cb');
        if ($handle === false) {
            throw new \ClientFence\InformationException('Could not open custom.css for writing.');
        }

        $written = false;
        if (flock($handle, LOCK_EX)) {
            $written = ftruncate($handle, 0)
                && rewind($handle)
                && fwrite($handle, $css) === strlen($css)
                && fflush($handle);
            flock($handle, LOCK_UN);
        }
        fclose($handle);

        if (!$written) {
            throw new \ClientFence\InformationException('Could not write custom.css.');
        }

        $this->di['logger']->info('Updated custom CSS');
    }

    /**
     * Returns the stylesheet, safe to place inside a <style> tag.
     */
    public function getCss(): string
    {
        $raw = $this->getRawCss();

        // Stop a stray closing tag from ending the <style> element early.
        $raw = preg_replace('#</style#i', '<\/style', $raw) ?? '';

        // Strip a UTF-8 byte order mark if the editor added one.
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? '';

        return trim($raw);
    }
}
