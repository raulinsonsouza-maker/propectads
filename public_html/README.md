# Landing Page - Clínica Letícia Galloni | Medicina Bioenergética

Landing page responsiva desenvolvida em HTML/CSS/JavaScript puro, baseada no design da página MCP da Luana Carolina, adaptada para a Clínica de Medicina Bioenergética.

## 📁 Estrutura de Arquivos

- `index.html` - Estrutura HTML completa com todas as seções
- `styles.css` - Estilos responsivos e design moderno
- `script.js` - Funcionalidades JavaScript e integração WhatsApp
- `README.md` - Este arquivo

## 🚀 Como Usar

1. Abra o arquivo `index.html` em um navegador web
2. Para desenvolvimento local, você pode usar um servidor HTTP simples:
   ```bash
   # Python 3
   python -m http.server 8000
   
   # Node.js (com http-server instalado)
   npx http-server
   ```

## ⚙️ Configuração do WhatsApp

**IMPORTANTE:** Antes de publicar, configure o número do WhatsApp no arquivo `script.js`:

1. Abra o arquivo `script.js`
2. Localize a seção `WHATSAPP_CONFIG` no início do arquivo
3. Altere o número `'5511999999999'` para o número real da clínica
4. O formato deve ser: código do país + DDD + número (apenas dígitos)
   - Exemplo: `5511987654321` (Brasil: 55 + DDD: 11 + Número: 987654321)

### Mensagens Pré-formatadas

As mensagens do WhatsApp já estão configuradas para cada botão CTA:
- **Hero**: Mensagem inicial sobre interesse no atendimento
- **Benefits**: Mensagem sobre iniciar processo terapêutico
- **Evaluation**: Mensagem sobre agendar avaliação gratuita
- **Final**: Mensagem final de contato

Você pode personalizar essas mensagens na seção `messages` do objeto `WHATSAPP_CONFIG`.

## 🎨 Personalização

### Cores

As cores principais podem ser alteradas no arquivo `styles.css`, na seção `:root`:

```css
:root {
    --primary-color: #2d5a3d;      /* Cor principal (verde escuro) */
    --secondary-color: #4a7c59;    /* Cor secundária */
    --accent-color: #6b9f7a;       /* Cor de destaque */
    --cta-color: #2d5a3d;          /* Cor dos botões CTA */
}
```

### Conteúdo

Todo o conteúdo textual está no arquivo `index.html` e pode ser editado diretamente.

## 📱 Responsividade

A landing page é totalmente responsiva e otimizada para:
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

## ✨ Funcionalidades

- ✅ Design responsivo e moderno
- ✅ Animações suaves ao fazer scroll
- ✅ Integração com WhatsApp (links diretos)
- ✅ Scroll suave entre seções
- ✅ Otimizado para performance
- ✅ SEO básico (meta tags)

## 📝 Seções da Landing Page

1. **Header** - Logo da clínica
2. **Hero** - Promessa central com CTA principal
3. **Dor** - Onde a pessoa está hoje
4. **Frustração** - Quebra emocional
5. **Virada** - Nova possibilidade
6. **Apresentação do Método** - Medicina Bioenergética (BEM)
7. **Benefícios** - O que você vai conseguir
8. **Como Funciona** - Estrutura da avaliação gratuita
9. **Para Quem É** - Indicado para você que...
10. **Sobre a Profissional** - Letícia Galloni
11. **Prova Social** - Depoimentos (estrutura preparada)
12. **CTA Final** - Decisão final com WhatsApp
13. **Footer** - Rodapé

## 🔧 Próximos Passos (Opcional)

- Adicionar depoimentos reais na seção de prova social
- Integrar formulário de contato (opcional)
- Adicionar Google Analytics (se necessário)
- Otimizar imagens (se forem adicionadas)
- Configurar domínio e hospedagem

## 📄 Licença

Este projeto foi desenvolvido para a Clínica Letícia Galloni.

---

**Desenvolvido com base no design da página MCP da Luana Carolina**

