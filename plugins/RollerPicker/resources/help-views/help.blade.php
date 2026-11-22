<div class="plugin-help-content">
    <header>
        <h3>RollerPicker</h3>
        <p>
            Componente Blade que exibe o campo nativo de data/hora no desktop e alterna automaticamente para um modal de roletas 3D abaixo da largura de tela configurada.
        </p>
    </header>

    <h4>Uso em Views Blade</h4>
    <div class="code">
        &lt;x-roller-picker type="date" name="birth_date" :value="old('birth_date')" breakpoint="768" required /&gt;<br>
        &lt;x-roller-picker type="time" name="birth_time" label="Hora de nascimento" breakpoint="768" /&gt;<br>
        &lt;x-roller-picker type="datetime-local" name="event_at" :min-year="1950" :max-year="2035" /&gt;
    </div>

    <x-configurable-plugin-values plugin="RollerPicker" />
</div>
