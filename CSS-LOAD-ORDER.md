# Ordem oficial de carregamento dos CSS (Tray)

## CSS global (base)
1. `cpapjf/wm-global.css`
   - Utilitários globais sob `.wm-global-scope`.

## CSS base por bloco
2. `cpapjf/hero-woodmart.css`
3. `cpapjf/cat-woodmart.css`
4. `cpapjf/coll-woodmart.css`
5. `cpapjf/prod-woodmart.css`
6. `cpapjf/brand-woodmart.css`
7. `cpapjf/inst-woodmart.css`
8. `cpapjf/cat-page-woodmart.css`
9. `cpapjf/prod-page-woodmart.css`

## CSS micro por bloco
10. `cpapjf/hero-woodmart-micro.css`
11. `cpapjf/cat-woodmart-micro.css`
12. `cpapjf/coll-woodmart-micro.css`
13. `cpapjf/prod-woodmart-micro.css`
14. `cpapjf/brand-woodmart-micro.css`
15. `cpapjf/inst-woodmart-micro.css`
16. `cpapjf/cat-page-woodmart-micro.css`
17. `cpapjf/prod-page-woodmart-micro.css`

## CSS global (micro)
18. `cpapjf/wm-micro.css`
   - Microinterações compartilhadas para blocos existentes.

---

## Observações importantes de dependência
- O `wm-global.css` deve ser carregado antes dos blocos para garantir utilitários e consistência.
- Cada arquivo `-micro.css` deve vir depois do CSS base do mesmo bloco.
- O `wm-micro.css` deve vir por último para complementar as microinterações sem perder prioridades.

## Erros comuns a evitar
- **Carregar micro antes do base:** transições e estados podem ser sobrescritos pelo CSS base.
- **Carregar `wm-global.css` depois dos blocos:** utilitários podem quebrar a hierarquia tipográfica e espaçamentos.
- **Misturar ordem de blocos e micros:** gera inconsistência nos hovers e focos entre seções.
- **Omitir `wm-micro.css`:** perde-se a camada de microinterações globais.
