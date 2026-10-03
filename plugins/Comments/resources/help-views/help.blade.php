<div class="plugin-help-content">

    <header>
        <h3>
            <x-lucide-message-square class="lucid-icon" /> Sistema de comentários dinâmicos
        </h3>
        <p>
            Habilita discussões, respostas aninhadas e moderação de comentários para posts, páginas e qualquer tipo customizado de publicação de forma 100% integrada ao ecossistema do Lunar Base.
        </p>
    </header>

    <h4>Configuração de exibição</h4>
    <p>
        Você pode escolher onde a área de comentários deve ficar ativa em <strong>Configurações &gt; Comentários</strong>. A lista de opções é gerada dinamicamente a partir dos tipos de publicação registrados no sistema (<code>PublicationTypes</code>).
    </p>

    <h4>Moderação e proteção contra spam</h4>
    <p>
        O plugin oferece duas camadas de controle para manter as discussões saudáveis:
    </p>
    <ul>
        <li>
            <strong>Moderação obrigatória:</strong> quando ativada nas configurações, nenhum comentário é publicado no site antes da aprovação prévia de um administrador.
        </li>
        <li>
            <strong>Filtro automático de spam:</strong> caso a moderação obrigatória esteja desligada, mensagens contendo termos suspeitos ainda serão retidas como <em>Pendentes</em> automaticamente.
        </li>
    </ul>
    <p class="code">
        <strong>Termos filtrados por padrão:</strong> <code>buy</code>, <code>viagra</code>, <code>free casino</code>, <code>spam</code>, <code>cryptocurrency</code> e <code>click here</code>.
    </p>

    <h4>Navegação e moderação contextual</h4>
    <p>
        Ao ativar comentários para um tipo de publicação, o plugin injeta automaticamente um submenu <strong>Comentários</strong> sob o menu principal correspondente (por exemplo: <em>Posts &gt; Comentários</em>, <em>Páginas &gt; Comentários</em> ou <em>Cursos &gt; Comentários</em>). Cada seção exibe apenas os comentários vinculados àquele conteúdo.
    </p>

    <h4>Desativação individual por publicação</h4>
    <p>
        Mesmo com os comentários ativados globalmente para um tipo, é possível desativar a discussão em itens específicos. Em posts e páginas, utilize o interruptor <strong>"Não exibir comentários"</strong> na barra lateral de propriedades ao editar o conteúdo.
    </p>

    <hr class="admin-divider" />

    <h4>Guia para desenvolvedores: adicionando suporte em novos plugins</h4>
    <p>
        Graças à integração com o <code>PublicationTypes</code>, você não precisa escrever código no plugin de comentários para torná-lo compatível com seus próprios plugins (como cursos, eventos, produtos ou imóveis). Basta seguir três convenções simples:
    </p>

    <ol class="steps">
        <li>
            <strong>1. Registre o seu tipo de publicação:</strong>
            <p>No <code>boot()</code> do seu plugin, registre o tipo informando o Model correspondente:</p>
            <pre class="code"><code>\App\Support\PublicationTypes::register('curso', [
    'label' =&gt; 'Cursos',
    'model' =&gt; \Plugins\Cursos\Models\Curso::class,
]);</code></pre>
            <small>Assim que registrado, a opção "Cursos" aparecerá automaticamente nas configurações de comentários.</small>
        </li>

        <li>
            <strong>2. Insira o hook na sua view pública:</strong>
            <p>No template onde o conteúdo é exibido (ex: <code>show.blade.php</code>), adicione o hook seguindo a convenção <code>{tipo}.comments</code>:</p>
            <pre class="code"><code>&lt;x-hook name="curso.comments" :params="['model' =&gt; $curso]" /&gt;</code></pre>
            <small>O plugin de comentários escutará esse hook e renderizará o formulário e a listagem histórica automaticamente.</small>
        </li>

        <li>
            <strong>3. Suporte ao interruptor "Não exibir comentários" (Opcional):</strong>
            <p>Para permitir que os usuários desativem comentários em itens específicos do seu plugin, basta que a sua Model retorne o valor booleano de <code>no_comments</code> por qualquer um destes padrões aceitos:</p>
            <ul>
                <li>Coluna direta no banco de dados (ex: <code>$curso-&gt;no_comments</code>);</li>
                <li>Campo dentro de coluna JSON <code>meta</code> (ex: <code>$curso-&gt;meta['no_comments']</code>);</li>
                <li>Relação clássica de metadados via tabela separada (ex: <code>$curso-&gt;meta()-&gt;where('meta_key', 'no_comments')</code>).</li>
            </ul>
        </li>
    </ol>

    <h4>Integração com avatares</h4>
    <p>
        Se o plugin de <strong>Avatares</strong> estiver ativo no sistema, as iniciais coloridas dos autores serão automaticamente substituídas pelas fotos de perfil personalizadas dos usuários.
    </p>

    <x-configurable-plugin-values plugin="Comments" />
</div>
