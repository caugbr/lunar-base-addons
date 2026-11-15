<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-map-pin class="lucid-icon" /> Cidades (Cities)
        </h3>
        <p>
            Base mundial de cidades com importador em streaming, API de busca normalizada sem acentuação e componente Blade reutilizável de autocomplete com coordenadas e fuso horário.
        </p>
    </header>

    <h4>Carga inicial da base de dados</h4>
    <p>
        Após ativar o plugin e rodar as migrations, execute o comando abaixo no terminal para importar as cidades a partir de <code>database/data/cities.csv.gz</code> (ou <code>cities.csv</code>):
    </p>
    <div class="code">
        php artisan migrate<br>
        php artisan cities:seed<br>
        php artisan cities:seed --fresh
    </div>

    <h4>Uso do componente Blade</h4>
    <p>
        Inclua o componente em qualquer formulário para habilitar a busca AJAX por nome da cidade com preenchimento automático de latitude, longitude e fuso horário:
    </p>
    <div class="code">
        &lt;x-cities-autocomplete<br>
        &nbsp;&nbsp;&nbsp;&nbsp;name="city"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;id="city"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;:value="old('city', '')"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;lat-target="#latitude"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;lng-target="#longitude"<br>
        &nbsp;&nbsp;&nbsp;&nbsp;timezone-target="#timezone"<br>
        /&gt;
    </div>

    <h4>Propriedades disponíveis</h4>
    <ul>
        <li><code>name</code> / <code>id</code> / <code>value</code> / <code>placeholder</code> / <code>class</code> / <code>required</code>: atributos padrão do campo de texto.</li>
        <li><code>country</code>: filtro opcional por código ISO2 do país (ex.: <code>country="BR"</code>).</li>
        <li><code>limit</code>: quantidade máxima de resultados no dropdown (padrão: <code>12</code>, máximo: <code>25</code>).</li>
        <li><code>use-ascii</code>: quando ativo (padrão: <code>true</code>), preenche o nome da cidade sem acentuação (<code>city_ascii</code>).</li>
        <li><code>lat-target</code>, <code>lng-target</code>, <code>timezone-target</code> e <code>city-ascii-target</code>: seletores CSS dos inputs que serão preenchidos automaticamente ao selecionar um item.</li>
    </ul>

    <h4>Evento JavaScript e endpoint AJAX</h4>
    <p>
        Ao escolher uma cidade, o componente dispara o evento <code>city:selected</code> no próprio input com os dados completos em <code>event.detail</code> (incluindo <code>city_ascii</code>, <code>timezone</code>, <code>lat</code> e <code>lng</code>). A consulta direta também está disponível via GET:
    </p>
    <div class="code">
        GET /api/cities/search?q=sao+paulo&amp;limit=12<br>
        GET /api/cities/search?q=curitiba&amp;country=BR
    </div>
</div>
