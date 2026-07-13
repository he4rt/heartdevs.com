# br-states.geojson

Malha dos estados brasileiros (27 UFs) usada no choropleth do painel de localização
(`Marketing → Localização`).

- **Fonte:** IBGE — malha territorial por UF, qualidade `intermediaria`
  (`https://servicodados.ibge.gov.br/api/v3/malhas/paises/BR?intrarregiao=UF`).
  Dado aberto / domínio público.
- **Processamento:** geometria simplificada (topojson-simplify, ~35% dos vértices) e
  coordenadas arredondadas para 3 casas decimais para reduzir o payload; propriedades
  reescritas de `codarea` (código IBGE) para `{ name, uf }`.
- **Uso:** apenas geometria de desenho. Os nomes canônicos dos estados e as contagens
  vêm do domínio (`App\Geo\Support\GeoLocation` + tabela `addresses`), não deste arquivo.

Não usamos a coleção de mapas da Highsoft/Highcharts (proprietária) — a geometria aqui é
de fonte livre para casar com o renderizador `chartjs-chart-geo` (MIT).
