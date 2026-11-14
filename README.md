# Custom CSS for ClientFence

A small [ClientFence](https://clientfence.com/) module that loads your own CSS on every client area page, so you can override the Palisade theme without editing or copying a template.

Your CSS lives in `modules/Customcss/custom.css`. ClientFence updates never touch your own module folders, so your styles survive updates.

## What it does

- Adds **Extensions → Custom CSS**, a simple editor for your stylesheet.
- Outputs the CSS as a `<style>` tag (with the page's CSP nonce) right after `<body>`, on every client area page, including the sign-in page and the signed-out order form.
- Because the tag comes after `palisade.css` and the brand stylesheet, rules with the same specificity win.

It uses the documented widget slot `client.theme.body.start`, so no template is changed.

## Install

1. On this page, select **Code → Download ZIP** and unzip it.
2. Copy the `Customcss` folder from inside it to `modules/` in your ClientFence directory, so you end up with `modules/Customcss/`
3. Make the web server user the owner of the folder, as the ClientFence module docs describe.
4. Make `modules/Customcss/custom.css` writable by the web server user. Without this you can still use the module, but the editor cannot save and will tell you so. Only the file needs to be writable, not the folder.
5. Go to **Extensions**, find **Custom CSS** and select **Activate**.
6. Open **Extensions → Custom CSS**, add your CSS and select **Save**.

If the client area still shows the old styles after saving, go to **System → Settings → System**, open the **Cache** tab and select **Invalidate cache**.

## Updating

Replace the module files with the new release, but **keep your existing `modules/Customcss/custom.css`**, because the release zip contains an empty placeholder that would overwrite your styles.

## Tested

- ClientFence version: 1.0.49
- Super Administrator: can open the editor, save, and see the CSS on the client area.

## Dark mode

Palisade sets `data-bs-theme` on the `<html>` element, so you can scope rules to dark mode like this:

```css
[data-bs-theme="dark"] .your-selector {
    /* dark-mode-only rules */
}
```

## Security notes

- CSS cannot run script. The module also escapes a stray `</style` in the saved CSS, so it cannot end the tag early.
- The saved CSS is shown to everyone who loads a client area page, so don't put anything private in it.
- External resources referenced from your CSS (fonts, images, `@import`) are subject to ClientFence's Content Security Policy.

## Licence

GPL version 2 or later. See `LICENSE`.
