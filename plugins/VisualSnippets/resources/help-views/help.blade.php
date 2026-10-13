<div class="plugin-help-content">

    {{-- Banner de Introdução --}}
    <header>
        <h3>
            <x-lucide-layout-template class="lucid-icon" />
            <span>Snippets Visuais (Visual Snippets)</span>
        </h3>
        <p>
            Crie estruturas e moldes visuais reutilizáveis no editor de blocos, desacoplando o design da camada de dados para garantir que layouts nunca quebrem no site.
        </p>
    </header>

    {{-- Visão Geral e Conceito --}}
    <h3>Como Funciona a Arquitetura de Snippets</h3>
    <p>
        O plugin opera com o conceito de <strong>moldes reutilizáveis</strong>. Você cria uma estrutura visual uma única vez (usando grids, cards, botões ou imagens) e pode consumi-la em quantas páginas ou artigos desejar através de shortcodes.
    </p>

    {{-- As Duas Formas de Inserção --}}
    <h3>Formatos de Shortcode Suportados</h3>
    <p>Existem dois modos distintos de consumir um Snippet Visual no editor:</p>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Formato</th>
                    <th>Comportamento</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <code>[vs-template slug="meu-slug"]</code>
                    </td>
                    <td>
                        <strong>Molde Global (Sincronizado):</strong> Renderiza o layout exatamente como foi desenhado no painel. Se você alterar o molde no futuro, todas as páginas que usam este shortcode se atualizam automaticamente.
                    </td>
                </tr>
                <tr>
                    <td>
                        <code>[vs-instance slug="meu-slug" titulo="..." foto="..."]</code>
                    </td>
                    <td>
                        <strong>Molde Local com Dados Específicos:</strong> Utiliza o esqueleto visual do molde, mas substitui as marcações internas (<code>data-var</code>) pelos valores passados nos atributos daquele post.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Guia de Marcação de Variáveis (data-var) --}}
    <h3>Mapeando Variáveis no Molde (data-var)</h3>
    <p>
        Para que o shortcode <code>[vs-instance]</code> saiba onde injetar cada informação, os elementos do molde recebem o atributo identificador <code>data-var="nome_do_campo"</code>:
    </p>

    <ul>
        <li>
            <strong>Textos e Títulos:</strong> Substitui o texto interno do elemento (ex: <code>&lt;h2 data-var="titulo"&gt;</code>).
        </li>
        <li>
            <strong>Imagens:</strong> Substitui a URL no atributo de origem (ex: <code>&lt;img data-var="foto" src="..."&gt;</code>).
        </li>
        <li>
            <strong>Links e Botões:</strong> Substitui o endereço de destino (<code>href</code>) ou o texto do link caso seja um texto comum.
        </li>
    </ul>

    <blockquote>
        <strong>Dica de Segurança:</strong>
        <p>
            Ao renderizar no site público, todos os atributos <code>data-var</code> são removidos automaticamente pelo parser, entregando um HTML limpo, semântico e leve para o visitante final.
        </p>
    </blockquote>

    {{-- Inserção pelo Editor Tiptap --}}
    <h3>Inserindo pelo Editor de Blocos</h3>
    <p>
        Você não precisa digitar os shortcodes manualmente no texto. No editor de Páginas e Posts:
    </p>
    <ol class="steps">
        <li>Clique no botão <strong>Shortcode</strong> no menu de blocos.</li>
        <li>Selecione <code>[VS-TEMPLATE]</code> ou <code>[VS-INSTANCE]</code>.</li>
        <li>Preencha o slug do snippet desejado e os atributos correspondentes.</li>
    </ol>

    <x-configurable-plugin-values plugin="VisualSnippets" />

    <x-plugin-dependencies plugin="VisualSnippets" />

</div>
