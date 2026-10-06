class ElementHeatmap {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.baseRadius = options.radius || 24;
        this.opacity = options.opacity || 0.70;
        this.gradient = this.createGradient();
    }

    // Régua de cores térmicas clássica (Azul -> Ciano -> Verde -> Amarelo -> Vermelho)
    createGradient() {
        const gradCanvas = document.createElement('canvas');
        gradCanvas.width = 1;
        gradCanvas.height = 256;
        const gCtx = gradCanvas.getContext('2d');

        const grad = gCtx.createLinearGradient(0, 0, 0, 256);
        grad.addColorStop(0.0, 'rgba(0, 0, 255, 0)');
        grad.addColorStop(0.2, 'rgba(0, 255, 255, 0.6)');
        grad.addColorStop(0.5, 'rgba(0, 255, 0, 0.8)');
        grad.addColorStop(0.8, 'rgba(255, 255, 0, 0.9)');
        grad.addColorStop(1.0, 'rgba(255, 0, 0, 1.0)');

        gCtx.fillStyle = grad;
        gCtx.fillRect(0, 0, 1, 256);

        return gCtx.getImageData(0, 0, 1, 256).data;
    }

    // Renderiza manchas térmicas sobre os elementos encontrados no Iframe
    render(elementsData, targetDoc, targetWidth, targetHeight) {
        this.canvas.width = targetWidth;
        this.canvas.height = targetHeight;
        this.ctx.clearRect(0, 0, targetWidth, targetHeight);

        if (!elementsData || elementsData.length === 0) return;

        // Descobre o elemento que mais teve cliques para calcular a escala de calor
        const maxClicks = Math.max(...elementsData.map(e => e.clicks), 1);

        // 1. Passada de densidade em tons de cinza
        elementsData.forEach(item => {
            try {
                // Procura o elemento HTML correspondente dentro do iframe
                const el = targetDoc.querySelector(item.selector);
                if (!el) return;

                const rect = el.getBoundingClientRect();

                // Se o elemento estiver oculto no layout atual, ignora
                if (rect.width === 0 && rect.height === 0) return;

                // O CENTRO EXATO DO ELEMENTO:
                // rect.top + window.scrollY pega a posição absoluta vertical na página
                const scrollY = targetDoc.documentElement.scrollTop || targetDoc.body.scrollTop || 0;
                const scrollX = targetDoc.documentElement.scrollLeft || targetDoc.body.scrollLeft || 0;

                const centerX = rect.left + scrollX + (rect.width / 2);
                const centerY = rect.top + scrollY + (rect.height / 2);

                // A intensidade do cinza e o raio variam conforme a proporção de cliques
                const intensityRatio = item.clicks / maxClicks;
                const radius = Math.max(this.baseRadius, Math.min(rect.width / 1.5, 40));

                this.ctx.beginPath();
                const radGrad = this.ctx.createRadialGradient(
                    centerX, centerY, 2,
                    centerX, centerY, radius
                );

                // Quanto mais cliques, mais opaco o centro fica (gerando mais calor)
                const centerAlpha = Math.min(0.8, 0.15 + (intensityRatio * 0.65));
                radGrad.addColorStop(0, `rgba(0,0,0,${centerAlpha})`);
                radGrad.addColorStop(1, 'rgba(0,0,0,0)');

                this.ctx.fillStyle = radGrad;
                this.ctx.arc(centerX, centerY, radius, 0, Math.PI * 2);
                this.ctx.fill();

            } catch (err) {
                // Ignora erros de seletores CSS exóticos
            }
        });

        // 2. Coloração térmica via ImageData
        const imgData = this.ctx.getImageData(0, 0, targetWidth, targetHeight);
        const data = imgData.data;

        for (let i = 0; i < data.length; i += 4) {
            const alpha = data[i + 3];

            if (alpha > 0) {
                const offset = alpha * 4;
                data[i]     = this.gradient[offset];
                data[i + 1] = this.gradient[offset + 1];
                data[i + 2] = this.gradient[offset + 2];
                data[i + 3] = Math.min(255, alpha * this.opacity * 2);
            }
        }

        this.ctx.putImageData(imgData, 0, 0);
    }

    // =========================================================================
    // RENDERIZAÇÃO DO MAPA DE ROLAGEM / PROFUNDIDADE (SCROLL MAP)
    // =========================================================================
    renderScroll(distribution, targetWidth, targetHeight) {
        this.canvas.width = targetWidth;
        this.canvas.height = targetHeight;
        this.ctx.clearRect(0, 0, targetWidth, targetHeight);

        if (!distribution || distribution.length === 0) return;

        // 1. Cria o degradê térmico vertical de cima a baixo
        const gradient = this.ctx.createLinearGradient(0, 0, 0, targetHeight);

        // Mapeia a taxa de retenção de cada marco para uma cor térmica
        distribution.forEach(item => {
            const stop = Math.min(1.0, Math.max(0.0, item.percent_mark / 100));
            const rate = item.rate; // Ex: 100, 75, 40, 15

            // Converte a taxa de 0 a 100% para uma cor espectral (Vermelho -> Amarelo -> Verde -> Azul)
            let color = 'rgba(37, 99, 235, 0.45)'; // Azul frio padrão (< 20%)

            if (rate >= 80) {
                color = 'rgba(239, 68, 68, 0.45)'; // Vermelho (> 80%)
            } else if (rate >= 60) {
                color = 'rgba(245, 158, 11, 0.45)'; // Laranja/Amarelo (60% a 80%)
            } else if (rate >= 40) {
                color = 'rgba(34, 197, 94, 0.45)'; // Verde (40% a 60%)
            } else if (rate >= 20) {
                color = 'rgba(6, 182, 212, 0.45)'; // Ciano (20% a 40%)
            }

            gradient.addColorStop(stop, color);
        });

        // 2. Pinta o degradê suave sobre a página inteira
        this.ctx.fillStyle = gradient;
        this.ctx.fillRect(0, 0, targetWidth, targetHeight);

        // 3. Desenha as linhas horizontais de corte e etiquetas de porcentagem
        distribution.forEach(item => {
            const yPos = (item.percent_mark / 100) * targetHeight;

            // Linha tracejada horizontal sutil
            this.ctx.beginPath();
            this.ctx.setLineDash([4, 6]);
            this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.7)';
            this.ctx.lineWidth = 1.5;
            this.ctx.moveTo(0, yPos);
            this.ctx.lineTo(targetWidth, yPos);
            this.ctx.stroke();
            this.ctx.setLineDash([]); // reseta o tracejado

            // Caixa de etiqueta de texto no canto direito
            const labelText = `${item.rate}% dos visitantes`;
            this.ctx.font = 'bold 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            const textWidth = this.ctx.measureText(labelText).width;

            const badgeX = targetWidth - textWidth - 28;
            const badgeY = yPos - 11;

            // Fundo escuro da etiqueta
            this.ctx.fillStyle = 'rgba(15, 23, 42, 0.75)';
            this.ctx.beginPath();
            this.ctx.roundRect
                ? this.ctx.roundRect(badgeX, badgeY, textWidth + 18, 22, 4)
                : this.ctx.rect(badgeX, badgeY, textWidth + 18, 22);
            this.ctx.fill();

            // Texto da porcentagem
            this.ctx.fillStyle = '#ffffff';
            this.ctx.fillText(labelText, badgeX + 9, badgeY + 15);
        });
    }
}

window.ElementHeatmap = ElementHeatmap;
