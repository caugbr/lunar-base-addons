

document.addEventListener('DOMContentLoaded', () => {
    const buttonExp = document.createElement('button');
    buttonExp.className = 'admin-btn admin-btn-secondary';
    buttonExp.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="lucid-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-expand-icon lucide-expand"><path d="m15 15 6 6"/><path d="m15 9 6-6"/><path d="M21 16v5h-5"/><path d="M21 8V3h-5"/><path d="M3 16v5h5"/><path d="m3 21 6-6"/><path d="M3 8V3h5"/><path d="M9 9 3 3"/></svg>';
    buttonExp.type = 'button';

    const buttons = document.querySelector('.image-buttons');
    buttons.style.cssText = 'display: flex; gap: 4px;';
    buttons.append(buttonExp);

    const buttonShr = document.createElement('button');
    buttonShr.className = 'admin-btn transparent-btn';
    buttonShr.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="lucid-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shrink-icon lucide-shrink"><path d="m15 15 6 6m-6-6v4.8m0-4.8h4.8"/><path d="M9 19.8V15m0 0H4.2M9 15l-6 6"/><path d="M15 4.2V9m0 0h4.8M15 9l6-6"/><path d="M9 4.2V9m0 0H4.2M9 9 3 3"/></svg>';
    buttonShr.type = 'button';
    buttonShr.style.cssText = 'position: absolute; top: 8px;right: 14px; z-index: 10001; display: none;';
    document.body.append(buttonShr);

    buttonExp.addEventListener('click', event => {
        event.preventDefault();
        const editor = document.querySelector('.tox-tinymce');
        editor.dataset.originalCss = editor.style.cssText;
        editor.style.position = 'fixed';
        editor.style.top = '0';
        editor.style.left = '0';
        editor.style.width = '100vw';
        editor.style.height = '100vh';
        editor.style.zIndex = '9999';
        buttonShr.style.display = 'inline-block';
        document.body.style.overflow = 'hidden';
    });

    buttonShr.addEventListener('click', event => {
        event.preventDefault();
        const editor = document.querySelector('.tox-tinymce');
        editor.style.cssText = editor.dataset.originalCss;
        buttonShr.style.display = 'none';
        document.body.style.overflow = 'auto';
    });
})
