<div class="form-group">
    <label>
        <x-switch
            name="no_comments"
            id="no_comments"
            :checked="(bool)$noComments"
            active=""
            inactive=""
        />
        <span>Não exibir comentários</span>
    </label>
    <small>Marque para ocultar os comentários neste conteúdo.</small>
</div>
