# Lunar Base — Addons

Repositório oficial de addons (plugins e temas) para o [Lunar Base](https://github.com/caugbr/lunar-base), um starter kit / CMS baseado em Laravel.

> **Nota:** No momento este repositório contém apenas **plugins**. O diretório de temas existe, mas ainda não possui um tema público disponível — o que há lá é apenas um tema de teste/scaffold para desenvolvimento.

---

## Plugins

| #  | Plugin                | Descrição                                                                                                                |
| -- | --------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| 1  | **Archive Vault**     | Intercepta exclusões definitivas de posts e páginas, preservando o conteúdo para auditoria futura.                       |
| 2  | **Article Reader**    | Adiciona um player de narração em áudio (Text-to-Speech) para que visitantes possam ouvir o conteúdo de páginas e posts. |
| 3  | **Asaas**             | Integração de pagamentos com o banco digital Asaas.                                                                      |
| 4  | **Avatars**           | Permite ao usuário criar uma imagem de perfil (avatar) personalizada.                                                    |
| 5  | **Backup**            | Gerenciador completo de backups e restauração do sistema.                                                                |
| 6  | **Banners**           | Sistema de gerenciamento de banners com estatísticas de cliques, hooks dinâmicos e shortcodes.                           |
| 7  | **BeforeAfter**       | Bloco comparador de imagens *Antes / Depois* com slider interativo.                                                      |
| 8  | **Comments**          | Insere funcionalidade de comentários nos posts.                                                                          |
| 9  | **Countdown**         | Adiciona um bloco de contador regressivo dinâmico para prazos, promoções e lançamentos de eventos no editor.             |
| 10 | **FAQ**               | Cria blocos de perguntas e respostas de forma organizada.                                                                |
| 11 | **File Download**     | Bloco para download de arquivos (PDF, documentos, planilhas, ZIP) com card estilizado.                                   |
| 12 | **Forms**             | Formulários dinâmicos via shortcode.                                                                                     |
| 13 | **FrontendLogin**     | Permite fazer login a partor do site público, com suporte a Turnstile e 2FA.                                                                    |
| 14 | **Galleries**         | Exibe galerias de imagens vinculadas ao post via shortcode `[gallery]`.                                                  |
| 15 | **GTranslate**        | Adiciona tradução instantânea com Google Tradutor para múltiplos idiomas.                                                |
| 16 | **Heatmap**           | Mapas de calor de cliques e rolagem totalmente integrados e hospedados localmente.                                       |
| 17 | **Maintenance**       | Bloqueia o site temporariamente para manutenção.                                                                         |
| 18 | **Maps**              | Cria mapas OpenStreetMap e permite definir pontos no mapa.                                                               |
| 19 | **Menus**             | Gerencia e adiciona menus customizados ao site.                                                                          |
| 20 | **Populator**         | Gera conteúdo de teste: usuários, posts e páginas com imagens placeholder.                                               |
| 21 | **Prism Highlight**   | Syntax highlight para blocos de código utilizando Prism.js.                                                              |
| 22 | **Qr Code**           | Gera QR Codes dinâmicos para posts, páginas e conteúdos avulsos.                                                         |
| 23 | **Reactions**         | Permite adicionar reações (like/dislike) em posts de forma dinâmica.                                                     |
| 24 | **Redirects**         | Gerenciamento de regras de redirecionamento de URLs e monitoramento de erros 404.                                        |
| 25 | **Role Switcher**     | Mude seu papel temporariamente e veja o sistema como outros roles vêem                                                   |
| 26 | **Share**             | Adiciona botões de compartilhamento social dinâmicos em posts do blog.                                                   |
| 27 | **Sitemap**           | Geração dinâmica de Sitemap XML para otimização em motores de busca (SEO).                                               |
| 28 | **Table Of Contents** | Varre todos os títulos (H1, H2, H3) do texto em tempo real e desenha uma caixa de índice navegável.                      |
| 29 | **Taxonomy Colors**   | Permite associar cada termo de taxonomia a uma cor.                                                                      |
| 30 | **Tracker**           | Estatísticas de acesso para o Lunar Base.                                                                                |
| 31 | **User Switcher**     | Permite que administradores assumam a identidade de outros usuários para testes, validações e suporte.                   |
| 32 | **Visual Snippets**   | Moldes visuais reutilizáveis e desacoplados para inserção via shortcode no editor.                                       |

---

## Temas

Nenhum tema público disponível ainda. O diretório `themes/` contém apenas um tema de teste utilizado durante o desenvolvimento do sistema de temas.

---

##  Instalação

Os addons são instalados via painel administrativo do Lunar Base em **Plugins → Instalar plugin** e **Temas → Instalar tema**, ou manualmente colocando a pasta do addon em:

```
addons/plugins/NomeDoPlugin/
addons/themes/NomeDoTema/
```

---
Projeto desenvolvido por [Cau Guanabara](https://cauguanabara.com.br)

Licenciado sob a [MIT License](https://mit-license.org/).
