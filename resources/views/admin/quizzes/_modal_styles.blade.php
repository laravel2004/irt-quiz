<style>
    /* Multiple Benar/Salah styling */
    .mbs-statement-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid var(--glass-border);
    }
    .mbs-statement-item input[type="text"] {
        flex: 1;
        background: transparent;
        border: none;
        color: #0f172a;
        outline: none;
    }
    .mbs-toggle-group {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }
    .mbs-toggle-btn {
        padding: 6px 14px;
        border-radius: 6px;
        border: 1px solid var(--glass-border);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    .mbs-toggle-btn.active-benar {
        background: rgba(16, 185, 129, 0.2) !important;
        border-color: #10b981 !important;
        color: #10b981 !important;
    }
    .mbs-toggle-btn.active-salah {
        background: rgba(239, 68, 68, 0.2) !important;
        border-color: #ef4444 !important;
        color: #ef4444 !important;
    }

    /* Option Editor Styles */
    .option-editor-wrapper {
        display: flex;
        flex-direction: column;
        gap: 10px;
        width: 100%;
    }
    .option-editor-toolbar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .option-editor-toolbar .math-quick-btn {
        min-width: auto;
        padding: 8px 10px;
    }
    .option-editor-toolbar .option-image-btn {
        border: 1px solid var(--glass-border);
        background: #ffffff;
        color: var(--text-secondary);
        border-radius: 8px;
        padding: 8px 10px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    .option-editor-toolbar .option-image-btn:hover {
        background: #eff6ff;
        color: var(--accent);
    }
    .option-editor-content {
        min-height: 88px;
        width: 100%;
        background: #ffffff;
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 12px 14px;
        color: var(--text-primary);
        outline: none;
        line-height: 1.6;
    }
    .option-editor-content:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
    }
    .option-editor-content img {
        max-width: 100%;
        height: auto;
        display: block;
        margin-top: 8px;
    }
    .option-editor-placeholder {
        color: var(--text-secondary);
    }
    .option-editor-hidden-input {
        display: none;
    }

    /* TinyMCE light theme overrides */
    .tox-tinymce {
        border: 1px solid var(--glass-border) !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08) !important;
    }
    .tox-tinymce,
    .tox .tox-editor-container,
    .tox .tox-edit-area,
    .tox .tox-statusbar,
    .tox .tox-toolbar,
    .tox .tox-toolbar__primary,
    .tox .tox-toolbar-overlord,
    .tox .tox-menubar,
    .tox .tox-edit-area__iframe {
        background: #ffffff !important;
        color: #0f172a !important;
    }
    .tox .tox-tbtn,
    .tox .tox-mbtn,
    .tox .tox-statusbar__path-item,
    .tox .tox-statusbar__wordcount,
    .tox .tox-statusbar__branding svg,
    .tox .tox-icon svg {
        color: #334155 !important;
        fill: #334155 !important;
    }
    .tox .tox-tbtn:hover,
    .tox .tox-tbtn--enabled,
    .tox .tox-mbtn:hover {
        background: #eff6ff !important;
        color: #2563eb !important;
    }
    .tox .tox-collection,
    .tox .tox-menu,
    .tox .tox-pop,
    .tox .tox-dialog {
        background: #ffffff !important;
        color: #0f172a !important;
        border-color: var(--glass-border) !important;
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12) !important;
    }
    .tox .tox-collection__item,
    .tox .tox-collection__item-label,
    .tox .tox-form__group label,
    .tox .tox-dialog__title {
        color: #0f172a !important;
    }
    .tox .tox-collection__item--active,
    .tox .tox-collection__item:hover {
        background: #eff6ff !important;
    }
    /* Fix TinyMCE dropdowns/menus appearing behind the question modal */
    .tox-tinymce-aux {
        z-index: 4000 !important;
    }

    /* ===== Math Editor Modal ===== */
    .math-modal-overlay {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(15, 23, 42, 0.18);
        backdrop-filter: blur(8px);
        z-index: 5000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .math-modal-overlay.active {
        display: flex;
    }
    .math-modal {
        background: #ffffff;
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        width: 100%;
        max-width: 720px;
        padding: 32px;
        box-shadow: 0 25px 50px rgba(15, 23, 42, 0.12);
        animation: fadeIn 0.3s ease;
    }
    .math-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }
    .math-modal-header h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.2rem;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .math-modal-header h3 i {
        color: var(--accent);
    }
    .math-close-btn {
        background: none;
        border: none;
        color: var(--text-secondary);
        font-size: 1.5rem;
        cursor: pointer;
        transition: color 0.2s;
    }
    .math-close-btn:hover { color: #0f172a; }

    /* MathLive field styling */
    math-field {
        display: block;
        width: 100%;
        min-height: 80px;
        font-size: 1.4rem;
        background: #f8fafc;
        border: 2px solid var(--glass-border);
        border-radius: 12px;
        padding: 16px;
        color: #0f172a;
        --caret-color: #3b82f6;
        --selection-background-color: rgba(59, 130, 246, 0.3);
        --contains-highlight-background-color: transparent;
        --primary-color: #3b82f6;
        --text-font-family: 'Inter', sans-serif;
        margin-bottom: 16px;
        transition: border-color 0.3s;
    }
    math-field:focus-within {
        border-color: var(--accent);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
    }

    /* Quick formulas palette */
    .math-quick-formulas {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 20px;
    }
    .math-quick-btn {
        background: #ffffff;
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        padding: 8px 14px;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
    }
    .math-quick-btn:hover {
        background: rgba(59, 130, 246, 0.08);
        border-color: var(--accent);
        color: #0f172a;
        transform: translateY(-1px);
    }

    /* Preview box */
    .math-preview-box {
        background: #ffffff;
        border: 1px solid var(--glass-border);
        border-radius: 12px;
        padding: 20px;
        min-height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }
    .math-preview-box .katex {
        font-size: 1.5rem;
    }
    .math-preview-label {
        font-size: 0.8rem;
        color: var(--text-secondary);
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .math-latex-raw {
        background: #ffffff;
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        padding: 10px 14px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: var(--text-secondary);
        word-break: break-all;
        margin-bottom: 20px;
    }
    .math-insert-btn {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #0f172a;
        border: none;
        padding: 14px 32px;
        border-radius: 12px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        font-size: 1rem;
        cursor: pointer;
        width: 100%;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .math-insert-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
    }
    .math-mode-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 16px;
    }
    .math-mode-tab {
        flex: 1;
        padding: 10px;
        border: 1px solid var(--glass-border);
        border-radius: 8px;
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
        text-align: center;
    }
    .math-mode-tab.active {
        background: rgba(59, 130, 246, 0.15);
        border-color: var(--accent);
        color: var(--accent);
    }
    
    /* Preview Modal Custom Styling */
    .preview-option-item {
        padding: 12px 16px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid var(--glass-border);
        display: flex;
        align-items: flex-start;
        gap: 12px;
        overflow: hidden;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .preview-option-html {
        flex: 1;
        min-width: 0;
        max-width: 100%;
    }
    .preview-option-item img,
    .preview-option-html img,
    #previewText img,
    #previewExplanation img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
        border-radius: 8px;
        object-fit: contain;
    }
    .preview-option-item .katex-display,
    .preview-option-html .katex-display {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }
    .preview-option-item.correct {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
    }
</style>
