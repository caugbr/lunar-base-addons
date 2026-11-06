<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-help-circle class="lucid-icon" />
            Revisão de Envio em Lote
        </h3>
        <p>Última etapa de conferência antes de enfileirar os e-mails para os contatos selecionados.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Contatos Selecionados:</strong>
                Informa o total de destinatários que receberão a mensagem configurada nesta rodada de envios.
            </li>
            <li>
                <strong>Demonstração Real:</strong>
                Exibe uma prévia gerada com os dados reais do primeiro lead da lista, demonstrando a substituição prática das variáveis.
            </li>
            <li>
                <strong>Envio Escalonado em Fila:</strong>
                Os e-mails não são disparados todos de uma vez. O sistema programa intervalos aleatórios de <strong>45 a 90 segundos</strong> entre cada mensagem para proteger o domínio e o IP de envio.
            </li>
            <li>
                <strong>Execução em Segundo Plano:</strong>
                Ao confirmar, os jobs entram na fila de processamento (Queue worker), liberando seu painel imediatamente para você continuar navegando.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                Clique em <strong>Confirmar e Iniciar Envio</strong> para agendar os disparos ou em <strong>Cancelar</strong> para retornar à lista.
            </div>
        </div>
    </div>
</div>
