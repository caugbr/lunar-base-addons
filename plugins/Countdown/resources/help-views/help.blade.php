<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-timer class="lucid-icon" />
            Countdown (Contador Regressivo)
        </h3>
        <p>
            Permite aos editores de conteúdo inserir blocos de relógio regressivo em tempo real em qualquer post ou página pública do site, sendo ideal para criar senso de urgência em lançamentos, eventos ou ofertas por tempo limitado.
        </p>
    </header>

    <h4>Como Utilizar no Editor</h4>
    <p>
        Abra o menu <strong>"Inserir Bloco"</strong> no editor e selecione a opção <strong>"Contador Regressivo"</strong> na categoria <em>Estrutura & Layout</em>.
    </p>

    <h4>Recursos do Bloco</h4>
    <ul>
        <li><strong>Data e Hora Limite:</strong> Seletor nativo de data e hora para configurar o término da contagem.</li>
        <li><strong>Título Customizável:</strong> Digite o texto de chamada diretamente sobre o bloco (ex: <em>"A promoção encerra em:"</em>).</li>
        <li><strong>Temas Visuais:</strong> Alterne entre os estilos <strong>Escuro</strong>, <strong>Azul</strong> e <strong>Dourado</strong> com 1 clique.</li>
        <li><strong>Mensagem de Encerramento:</strong> Quando o relógio atinge zero, a grade de números é substituída automaticamente por uma mensagem elegante de tempo esgotado.</li>
    </ul>

    <h4>Comportamento no Front-End</h4>
    <p>
        O motor do relógio é executado via JavaScript nativo leve a cada segundo no navegador do visitante, sem gerar requisições ao servidor ou consumir recursos da hospedagem.
    </p>

    <x-configurable-plugin-values plugin="Countdown" />
</div>
