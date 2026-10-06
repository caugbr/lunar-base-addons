<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-scan-eye class="lucid-icon" />
            Visualizador de comportamento
        </h3>
        <p>Sobreposição gráfica de interações em tempo real sobre o layout da página selecionada.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Modo cliques:</strong>
                Exibe manchas térmicas nos botões, links, imagens e elementos onde os visitantes efetivamente clicaram. As áreas em vermelho e amarelo indicam os pontos de maior conversão da tela.
            </li>
            <li>
                <strong>Modo atenção (Hover):</strong>
                Revela onde os usuários repousaram o cursor do mouse por mais de 1 segundo enquanto liam o conteúdo. Esse mapa ajuda a identificar quais textos e títulos despertaram interesse e hesitação, mesmo sem clique direto.
            </li>
            <li>
                <strong>Modo rolagem (Scroll):</strong>
                Projeta um degradê vertical térmico com linhas de porcentagem ao longo da página, mostrando a retenção de leitura e até qual altura os visitantes desceram antes de sair do site.
            </li>
            <li>
                <strong>Alternância Desktop / Mobile:</strong>
                Alterne entre os ícones de monitor e celular na barra superior para simular o layout responsivo de cada tela. Os elementos que existem apenas no celular (como o menu hambúrguer) acendem suas manchas automaticamente.
            </li>
            <li>
                <strong>Navegação bloqueada:</strong>
                A tela do visualizador possui uma camada protetora transparente para evitar que você clique em links acidentais e saia da página durante a inspeção dos dados.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                A precisão das manchas é 100% semântica: se o layout for redimensionado, o calor acompanha a posição real dos elementos automaticamente.
            </div>
        </div>
    </div>
</div>
