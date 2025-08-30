<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include ('admin::layouts.head')

    {{-- Flatpickr CSS para garantir estilo do calendário --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
</head>
<body>
    <div id="app" class="content-container">
        <flash-wrapper ref="flashes"></flash-wrapper>
        @yield('content-wrapper')
    </div>

    @stack('scripts')

    <script>console.log('override master OK');</script>

    {{-- Calendário pt-BR + data dd/mm/aaaa --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>

    <script>
      (function () {
        // ---- DATA/PT-BR ----
        function initDates(root) {
          if (!window.flatpickr) return;

          // Locale em português
          flatpickr.localize(flatpickr.l10ns.pt);

          // Seletor amplo para pegar tudo que vira Flatpickr
          var selectors = [
            ".flatpickr-input",             // inputs já inicializados
            "input[type='date']",
            "input[type='datetime-local']",
            ".date",
            ".date-time",
            ".date-range input"             // os 2 inputs do intervalo do dashboard
          ];

          (root || document).querySelectorAll(selectors.join(',')).forEach(function (el) {
            try {
              if (el._flatpickr) {
                el._flatpickr.set('locale', 'pt');
                el._flatpickr.set('dateFormat', 'd/m/Y');
              } else {
                // Evita criar duas vezes em inputs que o Krayin monta depois
                flatpickr(el, {
                  locale: 'pt',
                  dateFormat: 'd/m/Y',
                  allowInput: true
                });
              }
            } catch (e) {
              console.warn('Flatpickr PT-BR:', e);
            }
          });
        }

        // ---- BRL (visual) com Intl.NumberFormat ----
        const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

        // Extrai número de um texto que pode vir como "US$ 1,234.56" ou "1,234.56"
        function toNumberFromAny(text) {
          if (!text) return null;
          // remove US$, espaços e NBSP
          let t = text.replace(/US\$\s*/g, '')
                      .replace(/\u00A0/g, ' ')
                      .trim();

          // se já está em pt-BR (1.234,56), inverte para número
          if (/\d+\.\d{3}(?:\.\d{3})*,\d+|\d+,\d+/.test(t)) {
            t = t.replace(/\./g, '').replace(',', '.');
          } else {
            // estilo EN: 1,234,567.89 -> tira milhar
            t = t.replace(/,/g, '');
          }

          const n = parseFloat(t.match(/-?\d+(\.\d+)?/)?.[0] || '');
          return isNaN(n) ? null : n;
        }

        function applyBRL(root) {
          // Locais comuns de valores
          const targets = [
            '.card .value',
            '.statistics .value',
            '.datagrid-table td',
            '.datagrid-table th',
            '.summary',
            '.amount',
            '.price',
            '.total',
            '.grand-total',
            '.currency',
            '.money',
            '.balance'
          ].join(',');

          (root || document).querySelectorAll(targets).forEach(function (el) {
            const txt = (el.textContent || '').trim();

            // Só mexe se aparecer "US$" ou se for um número “nu” que parece preço
            if (/US\$/.test(txt) || /(^|[\s>])\d{1,3}([.,]\d{3})*([.,]\d{2})($|[\s<])/.test(txt)) {
              const num = toNumberFromAny(txt);
              if (num !== null) {
                el.textContent = brl.format(num);
              }
            }
          });
        }

        function applyAll(root) {
          initDates(root);
          applyBRL(root);
        }

        // Primeiras execuções
        document.addEventListener('DOMContentLoaded', function () {
          applyAll();
          setTimeout(applyAll, 300);
          setTimeout(applyAll, 1200);
        });

        // Se o tema disparar evento próprio
        document.addEventListener('krayin:page:loaded', function (e) {
          applyAll(e && e.target ? e.target : document);
        });

        // Observa mudanças no DOM (SPA-like, AJAX, componentes Vue)
        const mo = new MutationObserver(function (mutations) {
          // roda de forma barata; se ficar pesado, podemos refinar
          applyAll();
        });
        mo.observe(document.documentElement, { childList: true, subtree: true });
      })();
    </script>
</body>
</html>
