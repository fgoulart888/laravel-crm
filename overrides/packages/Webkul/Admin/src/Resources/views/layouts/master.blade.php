<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include ('admin::layouts.head')
</head>

<body>
    <div id="app" class="content-container">
        <flash-wrapper ref="flashes"></flash-wrapper>

        {{-- Conteúdo padrão do Admin --}}
        @yield('content-wrapper')
    </div>

    {{-- Pilha de scripts do próprio Krayin/Admin --}}
    @stack('scripts')

    {{-- === PT-BR: Calendário + Formato de data (Flatpickr) === --}}
    {{-- Carrega Flatpickr e locale pt sem alterar o backend --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>

    <script>
      (function () {
        function initDates(root) {
          if (!window.flatpickr) return;

          // Localiza para português (nomes dos meses, dias, etc.)
          flatpickr.localize(flatpickr.l10ns.pt);

          // Seletor dos campos de data mais comuns no Admin
          var selectors = [
            "input[type='date']",
            "input[type='datetime-local']",
            ".date",
            ".date-time",
            ".date-range input"
          ];

          selectors.forEach(function (sel) {
            (root || document).querySelectorAll(sel).forEach(function (el) {
              try {
                // Se já tiver instância, só reajusta; senão, cria
                if (el._flatpickr) {
                  el._flatpickr.set('locale', 'pt');
                  el._flatpickr.set('dateFormat', 'd/m/Y');
                } else {
                  flatpickr(el, {
                    locale: 'pt',
                    dateFormat: 'd/m/Y',
                    allowInput: true
                  });
                }
              } catch (e) {
                console.warn('Flatpickr PT-BR skip:', e);
              }
            });
          });
        }

        document.addEventListener('DOMContentLoaded', function () {
          initDates();

          // Em páginas com navegação dinâmica, reaplica
          setTimeout(initDates, 400);
          setTimeout(initDates, 1200);
        });

        // Caso o tema dispare eventos de troca de tela
        document.addEventListener('krayin:page:loaded', function (e) {
          initDates(e && e.target ? e.target : document);
        });
      })();
    </script>
    {{-- === /PT-BR: Calendário + Formato de data === --}}

    {{-- === PT-BR: Formatação visual de moeda para BRL (R$ 1.234,56) === --}}
    <script>
      (function () {
        function brlFormatText(text) {
          if (!text) return text;

          // 1) troca prefixos "US$" por "R$ "
          var t = text.replace(/US\$\s?/g, 'R$ ');

          // 2) converte números estilo EN -> PT-BR
          // casa números com possíveis milhares e decimais
          return t.replace(/(\d{1,3}(?:,\d{3})*(?:\.\d+)?|\d+(?:\.\d+)?)/g, function (num) {
            // remove vírgulas de milhar EN
            var n = num.replace(/,/g, '');
            var parts = n.split('.');
            var intPart = parts[0];
            var decPart = parts[1] || '00';

            // formata milhar com ponto
            intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

            // fixa 2 casas decimais
            decPart = (decPart + '00').slice(0, 2);

            return intPart + ',' + decPart;
          });
        }

        function applyBRL(root) {
          var targets = [
            '.card .value',
            '.statistics .value',
            '.datagrid-table td',
            '.datagrid-table th',
            '.summary',
            '.amount',
            '.price',
            '.total',
            '.grand-total',
            '.currency, .money, .balance'
          ].join(',');

          (root || document).querySelectorAll(targets).forEach(function (el) {
            if (el && el.textContent && /US\$/.test(el.textContent)) {
              el.textContent = brlFormatText(el.textContent.trim());
            }
          });
        }

        document.addEventListener('DOMContentLoaded', function () {
          applyBRL();
          setTimeout(applyBRL, 400);
          setTimeout(applyBRL, 1200);
        });

        // Reaplicar em mudanças dinâmicas
        document.addEventListener('krayin:page:loaded', function (e) {
          applyBRL(e && e.target ? e.target : document);
        });
      })();
    </script>
    {{-- === /PT-BR: Formatação visual de moeda === --}}
</body>
</html>
