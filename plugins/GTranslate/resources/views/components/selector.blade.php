@php
    $displayMode = config('pluginSettings.GTranslate.displayMode', 'flags');
    $includedLanguages = explode(',', config('pluginSettings.GTranslate.includedLanguages', 'pt,en,es,fr,de,it'));

    // Mapeamento dos códigos de idioma do Google para os códigos de país das bandeiras (ISO 3166-1 alpha-2)
    $flagCountryMap = [
        'pt' => 'br', // ou 'pt' para Portugal
        'en' => 'us', // ou 'gb' para Reino Unido
        'es' => 'es',
        'fr' => 'fr',
        'de' => 'de',
        'it' => 'it',
        'ja' => 'jp',
        'zh-CN' => 'cn',
        'ru' => 'ru',
    ];
@endphp

<div class="gtranslate-container" style="display: inline-flex; align-items: center; gap: 10px;">
    {{-- Bandeiras em SVG real --}}
    @if(in_array($displayMode, ['flags', 'flags_and_dropdown']))
        <div class="gtranslate-flags" style="display: inline-flex; gap: 6px; align-items: center;">
            @foreach($includedLanguages as $lang)
                @php
                    $lang = trim($lang);
                    $countryCode = $flagCountryMap[$lang] ?? $lang;
                @endphp
                <button type="button"
                        onclick="window.triggerGoogleTranslate('{{ $lang }}')"
                        title="Traduzir para {{ strtoupper($lang) }}"
                        class="gtranslate-flag-btn"
                        style="background: none; border: none; cursor: pointer; padding: 0; line-height: 0; display: inline-flex; align-items: center; transition: transform 0.15s ease;"
                        onmouseover="this.style.transform='scale(1.15)'"
                        onmouseout="this.style.transform='scale(1)'">
                    <img
                        src="https://flagcdn.com/24x18/{{ $countryCode }}.png"
                        srcset="https://flagcdn.com/48x36/{{ $countryCode }}.png 2x"
                        width="22"
                        height="16"
                        alt="{{ strtoupper($lang) }}"
                        style="border-radius: 3px; box-shadow: 0 1px 3px rgba(0,0,0,0.15); display: block;"
                    >
                </button>
            @endforeach
        </div>
    @endif

    {{-- Dropdown do Google --}}
    <div id="google_translate_element" style="{{ $displayMode === 'flags' ? 'display: none !important;' : '' }}"></div>
</div>

<script>
    window.triggerGoogleTranslate = function(langCode) {
        const defaultLang = '{{ config('pluginSettings.GTranslate.defaultLanguage', 'pt') }}';

        const cookieValue = `/${defaultLang}/${langCode}`;

        document.cookie = `googtrans=${cookieValue}; path=/;`;
        document.cookie = `googtrans=${cookieValue}; path=/; domain=${window.location.hostname};`;
        document.cookie = `googtrans=${cookieValue}; path=/; domain=.${window.location.hostname};`;

        const select = document.querySelector('.goog-te-combo');
        if (select) {
            select.value = langCode;
            select.dispatchEvent(new Event('change'));
        }

        window.location.reload();
    };
</script>

<style>
    .goog-te-combo {
        padding: 4px 8px !important;
        border-radius: 4px !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #fff !important;
        color: #1e293b !important;
        font-size: 0.85rem !important;
        cursor: pointer !important;
        outline: none !important;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: none;
    }
    .goog-te-combo:focus {
        border-color: #2563eb !important;
    }
    #goog-gt-tt, .goog-te-balloon-frame {
        display: none !important;
    }
    .goog-text-highlight {
        background: none !important;
        box-shadow: none !important;
    }
</style>
