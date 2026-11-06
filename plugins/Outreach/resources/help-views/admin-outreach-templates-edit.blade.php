<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-help-circle class="lucid-icon" />
            Editar Modelo de E-mail
        </h3>
        <p>Atualize o assunto, o conteúdo do texto ou as variáveis dinâmicas do modelo selecionado.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Impacto em Envios Futuros:</strong>
                As alterações realizadas aqui são aplicadas apenas aos próximos envios. O histórico de mensagens já disparadas permanece intacto.
            </li>
            <li>
                <strong>Sintaxe das Variáveis:</strong>
                Certifique-se de manter as chaves duplas fechadas corretamente (ex: <code>@{{primeiro_nome}}</code>) para que a substituição ocorra com sucesso.
            </li>
            <li>
                <strong>Imagens e Assinaturas:</strong>
                Caso insira imagens no corpo do e-mail, prefira formatos convencionais (PNG ou JPG) e arquivos compactados para evitar rejeições no Outlook.
            </li>
            <li>
                <strong>Proteção Contra Saída Acidental:</strong>
                O formulário avisa se houver alterações pendentes antes de fechar a janela.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                Lembre-se de clicar em <strong>Atualizar</strong> para persistir as modificações feitas no editor.
            </div>
        </div>
    </div>
</div>
