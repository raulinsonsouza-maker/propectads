<?php

declare(strict_types=1);

/**
 * Corrige artefatos do fix-blog-articles-utf8.php (ex.: "issó", "processó").
 * Uso: php scripts/fix-blog-articles-corruptions.php
 */

$files = [
    __DIR__ . '/blog-articles/articles-11-15.php',
    __DIR__ . '/blog-articles/articles-16-20.php',
];

$map = [
    'Issó' => 'Isso',
    'issó' => 'isso',
    'Nissó' => 'Nisso',
    'nissó' => 'nisso',
    'Dissó' => 'Disso',
    'dissó' => 'disso',
    'sejá' => 'seja',
    'aceitavel' => 'aceitável',
    'processó' => 'processo',
    'impulsó' => 'impulso',
    'lojá' => 'loja',
    'atrasó' => 'atraso',
    'vejá' => 'veja',
    'escolhá' => 'escolha',
    'armadilhá' => 'armadilha',
    'precisó' => 'preciso',
    'campanhá' => 'campanha',
    'despachá' => 'despacha',
    'pesó' => 'peso',
    'empurrao' => 'empurrão',
    'intuiacao' => 'intuição',
    'friccao' => 'fricção',
    'conclusao' => 'conclusão',
    'inseguranca' => 'insegurança',
    'transparenca' => 'transparência',
    'expedicao' => 'expedição',
    'opcao' => 'opção',
    'urgencia' => 'urgência',
    'beneficios' => 'benefícios',
    'politica' => 'política',
    'tecnico' => 'técnico',
    'catalogo' => 'catálogo',
    'comeca' => 'começa',
    'proprio' => 'próprio',
    'opcoes' => 'opções',
    'beneficio' => 'benefício',
    'compativel' => 'compatível',
    'recorrencia' => 'recorrência',
    'razoavel' => 'razoável',
    'opiniao' => 'opinião',
    'saudavel' => 'saudável',
    'inevitavel' => 'inevitável',
    'usuario' => 'usuário',
    'ultima' => 'última',
    'noticia' => 'notícia',
    'ausencia' => 'ausência',
    'imediata e culpar' => 'imediata é culpar',
    'taxa e considerada' => 'taxa é considerada',
    'visiveis é um' => 'visíveis e um',
    'fraude é aumentam' => 'fraude e aumentam',
    'por cliente é por canal' => 'por cliente e por canal',
    'margem saudavel' => 'margem saudável',
    'quando o processo de compra e simplificado é o custo' => 'quando o processo de compra é simplificado e o custo',
    'e simplificado é o custo' => 'é simplificado e o custo',
    ' e culpar' => ' é culpar',
    ' e especialmente' => ' é especialmente',
    ' e o custo total' => ' e o custo total',
    ' e parte central' => ' é parte central',
    ' e trabalhar' => ' é trabalhar',
    ' e organizar' => ' é organizar',
    ' e reduz' => ' é reduz',
    ' e exatamente' => ' é exatamente',
    ' e precis' => ' é precis',
    ' Nao e' => ' Não é',
    ' nao e ' => ' não é ',
    ' não e "' => ' não é "',
    ' não e o' => ' não é o',
    ' não e a' => ' não é a',
    ' não e necessário' => ' não é necessário',
    ' A primeira e:' => ' A primeira é:',
    ' A terceira pergunta e:' => ' A terceira pergunta é:',
    ' O caminho pratico e ' => ' O caminho prático é ',
    ' O ponto importante é que elevar' => 'O ponto importante é que elevar',
    ' Quando issó e bem' => ' Quando isso é bem',
    ' Quando bem desenhado' => 'Quando bem desenhado',
    ' Mesmo quando o custo logistico' => 'Mesmo quando o custo logístico',
    'logistico' => 'logístico',
    'eficiencia' => 'eficiência',
    'separacao' => 'separação',
    'comissao' => 'comissão',
    'intermediarios' => 'intermediários',
    'recebiveis' => 'recebíveis',
    'conveniencia' => 'conveniência',
    'diferenca' => 'diferença',
    'sofisticacao' => 'sofisticação',
    'precificacao' => 'precificação',
    'adicao' => 'adição',
    'inicio' => 'início',
    'magico' => 'mágico',
    'sustentaveis' => 'sustentáveis',
    'negocio' => 'negócio',
    'negocios' => 'negócios',
    'facilitar' => 'facilitar',
    'varias' => 'várias',
    'pratico' => 'prático',
    'Nao ' => 'Não ',
    'tactica' => 'tática',
    'disponivel' => 'disponível',
    'varios' => 'vários',
    'Lojá' => 'Loja',
    'clique é o dinheiro' => 'clique e o dinheiro',
    'produtos é alta margem' => 'produtos de alta margem',
    'seguro e testar' => 'seguro é testar',
    'decidir, testar é aprender' => 'decidir, testar e aprender',
    'psicologicamente' => 'psicologicamente',
    'critico' => 'crítico',
    'sequencial' => 'sequencial',
    'usó' => 'uso',
];

uksort($map, static fn ($a, $b) => strlen($b) <=> strlen($a));

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "Arquivo não encontrado: {$file}\n";
        continue;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        continue;
    }
    $out = [];
    $inSlug = false;
    foreach ($lines as $line) {
        if (preg_match("/^\s*'slug'\s*=>/", $line)) {
            $inSlug = true;
            $out[] = $line;
            continue;
        }
        if ($inSlug) {
            $out[] = $line;
            if (str_contains($line, ',')) {
                $inSlug = false;
            }
            continue;
        }
        foreach ($map as $from => $to) {
            $line = str_replace($from, $to, $line);
        }
        $out[] = $line;
    }
    file_put_contents($file, implode("\n", $out) . "\n");
    echo "Corrigido: {$file}\n";
}

echo "Concluído.\n";
