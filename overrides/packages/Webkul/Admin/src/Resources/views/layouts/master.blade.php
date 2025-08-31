<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include ('admin::layouts.head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
</head>
<body>
    <div id="app" class="content-container">
        <flash-wrapper ref="flashes"></flash-wrapper>
        @yield('content-wrapper')
    </div>

    @stack('scripts')

    <script>console.log('override master OK');</script>

    {{-- Calendário em pt-BR + dd/mm/aaaa --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/pt.js"></script>
    <script>
    (function () {
        function initDates(root){
            if(!window.flatpickr) return;
            flatpickr.localize(flatpickr.l10ns.pt);
            const sels = [
                ".flatpickr-input","input[type='date']","input[type='datetime-local']",
                ".date",".date-time",".date-range input"
            ];
            (root||document).querySelectorAll(sels.join(',')).forEach(el=>{
                try{
                    if (el._flatpickr) {
                        el._flatpickr.set('locale','pt');
                        el._flatpickr.set('dateFormat','d/m/Y');
                    } else {
                        flatpickr(el,{ locale:'pt', dateFormat:'d/m/Y', allowInput:true });
                    }
                }catch(e){ console.warn('date init',e); }
            });
        }
        document.addEventListener('DOMContentLoaded', () => {
            initDates();
            setTimeout(initDates,300);
            setTimeout(initDates,1200);
        });
        document.addEventListener('krayin:page:loaded', e => initDates(e.target||document));
    })();
    </script>

    {{-- Moeda em BRL (R$) --}}
    <script>
    (function(){
        const brlFmt = new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'});
        function toNumberFromAny(text){
            if (!text) return null;
            let t = text.replace(/US\$\s*/g,'').replace(/\u00A0/g,' ').trim();
            if (/\d+\.\d{3}(?:\.\d{3})*,\d+|\d+,\d+/.test(t)) {
                t = t.replace(/\./g,'').replace(',','.');
            } else {
                t = t.replace(/,/g,'');
            }
            const m = t.match(/-?\d+(\.\d+)?/);
            const n = m ? parseFloat(m[0]) : NaN;
            return isNaN(n) ? null : n;
        }
        function convertText(txt){
            if (!/US\$/.test(txt)) return null;
            if (/\d+(\.\d{3})*,\d{2}/.test(txt)) {
                return txt.replace(/US\$\s*/g,'R$ ');
            }
            const num = toNumberFromAny(txt);
            if (num===null) return null;
            return txt.replace(/US\$\s*.*$/, brlFmt.format(num));
        }
        function walkAndReplace(root=document.body){
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode(n){ return /US\$/.test(n.nodeValue) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP; }
            });
            const nodes=[]; while(walker.nextNode()) nodes.push(walker.currentNode);
            nodes.forEach(n=>{
                const out=convertText(n.nodeValue);
                if(out) n.nodeValue=out;
            });
        }
        function runAll(root){
            walkAndReplace(root);
            const sels=[
                '.card .value','.statistics .value',
                '.datagrid-table td','.datagrid-table th',
                '.summary','.amount','.price','.total','.grand-total',
                '.currency','.money','.balance'
            ].join(',');
            (root||document).querySelectorAll(sels).forEach(el=>{
                const t=(el.textContent||'').trim();
                const out=convertText(t);
                if(out) el.textContent=out;
            });
        }
        document.addEventListener('DOMContentLoaded', ()=>{
            runAll();
            setTimeout(runAll,250);
            setTimeout(runAll,1200);
        });
        const mo=new MutationObserver(()=>runAll());
        mo.observe(document.documentElement,{childList:true,subtree:true});
    })();
    </script>
</body>
</html>
