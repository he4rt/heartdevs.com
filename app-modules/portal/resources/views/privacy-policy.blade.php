@php
    $sections = [
        'quem-somos' => 'Quem somos',
        'onde-se-aplica' => 'Onde esta política se aplica',
        'dados-coletados' => 'Quais dados tratamos',
        'finalidades' => 'Para que usamos os dados',
        'moderacao' => 'Moderação e decisões automatizadas',
        'compartilhamento' => 'Com quem compartilhamos',
        'transferencia-internacional' => 'Transferência internacional',
        'dados-publicos' => 'O que fica público',
        'cookies' => 'Cookies, sessão e tokens',
        'retencao' => 'Por quanto tempo guardamos',
        'seguranca' => 'Segurança',
        'direitos' => 'Seus direitos',
        'menores' => 'Crianças e adolescentes',
        'alteracoes' => 'Alterações desta política',
        'contato' => 'Contato',
    ];
    $number = array_flip(array_keys($sections));
@endphp

<div class="pb-20">
    <section class="relative overflow-hidden pt-10 pb-8 lg:pt-14">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 -top-24 -z-10 mx-auto h-64 max-w-4xl rounded-full opacity-60 blur-3xl"
            style="background: radial-gradient(60% 120% at 50% 0%, color-mix(in oklab, var(--primary) 40%, transparent), transparent 70%)"
        ></div>

        <div class="hp-page max-w-6xl">
            <div class="flex max-w-3xl flex-col gap-5">
                <p class="text-text-medium flex items-center gap-3 font-mono text-xs tracking-[0.2em] uppercase">
                    <span aria-hidden="true" class="bg-outline-low inline-block h-px w-8"></span>
                    Seus dados
                </p>

                <h1 class="text-text-high text-4xl leading-[1.08] font-bold tracking-tight lg:text-5xl">
                    Política de <span class="text-primary">privacidade</span>
                </h1>

                <p class="text-text-medium max-w-xl text-base leading-relaxed">
                    Como a He4rt Developers coleta, usa, guarda e compartilha os seus dados pessoais no site, nos
                    painéis, no app mobile e no bot do Discord.
                </p>

                <p class="text-text-low font-mono text-xs">Última atualização: {{ $lastUpdatedAt }}</p>
            </div>
        </div>
    </section>

    <div class="hp-page grid max-w-6xl gap-10 lg:grid-cols-[240px_minmax(0,1fr)]">
        <nav aria-label="Índice" class="hidden lg:block">
            <ol class="sticky top-24 flex flex-col gap-2 text-sm">
                @foreach ($sections as $id => $title)
                    <li>
                        <a href="#{{ $id }}" class="text-text-medium hover:text-text-high transition-colors">
                            <span class="text-text-low font-mono text-xs">
                                {{ str_pad((string) ($number[$id] + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>
                            {{ $title }}
                        </a>
                    </li>
                @endforeach
            </ol>
        </nav>

        <article class="flex max-w-3xl min-w-0 flex-col gap-10">
            <x-portal::legal.section id="quem-somos" :number="$number['quem-somos'] + 1" :title="$sections['quem-somos']">
                <p>
                    A <strong>He4rt Developers</strong> é uma comunidade brasileira de pessoas desenvolvedoras, ativa
                    desde 2018. Nesta política, "nós" e "He4rt" significam a comunidade. A He4rt é a
                    <strong>controladora</strong> dos dados pessoais descritos aqui, nos termos da Lei Geral de Proteção
                    de Dados (LGPD).
                </p>
                <p>
                    Escrevemos este texto para ser lido por pessoas, não só por advogados. Se algo não ficar claro, fale
                    com a gente pelos canais da seção <a href="#contato">Contato</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="onde-se-aplica" :number="$number['onde-se-aplica'] + 1" :title="$sections['onde-se-aplica']">
                <p>Esta política vale para todos os produtos da He4rt:</p>
                <ul>
                    <li><strong>Site</strong> heartdevs.com, incluindo os links curtos (<code>/l/...</code>).</li>
                    <li><strong>Painéis</strong> web da plataforma (área da pessoa usuária e área administrativa).</li>
                    <li><strong>App mobile</strong> da He4rt, publicado na App Store e no Google Play.</li>
                    <li>
                        <strong>Aplicação do Discord</strong>: o bot da He4rt e o servidor oficial da comunidade, além
                        dos bots que acompanham o chat da Twitch nas lives parceiras.
                    </li>
                </ul>
                <p>
                    Discord, Twitch, GitHub, Dev.to e WhatsApp têm políticas próprias. Esta política cobre só o que a He4rt
                    faz com os dados que recebe dessas plataformas.
                </p>
                <p>
                    As regras de uso dos serviços estão nos
                    <a href="{{ route('terms-of-service', absolute: false) }}">termos de uso</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="dados-coletados" :number="$number['dados-coletados'] + 1" :title="$sections['dados-coletados']">
                <h3>Conta e login</h3>
                <ul>
                    <li>
                        O login é feito pelo Discord, GitHub, Twitch ou Dev.to. Dessas contas recebemos nome, nome de
                        usuário, e-mail, foto de perfil e o identificador da conta.
                    </li>
                    <li>
                        Guardamos os tokens de acesso dessas plataformas e as chaves de API que você conectar (por
                        exemplo, a do Dev.to). Eles ficam criptografados.
                    </li>
                    <li>Papéis e permissões da sua conta na plataforma.</li>
                </ul>

                <h3>Perfil</h3>
                <p>Só o que você preencher:</p>
                <ul>
                    <li>Apelido, data de nascimento, bio, título profissional e redes sociais.</li>
                    <li>Senioridade, anos de experiência, habilidades e experiências profissionais.</li>
                    <li>Disponibilidade para propostas e faixa de pretensão salarial.</li>
                    <li>País, estado, cidade e CEP.</li>
                    <li>Foto de perfil e imagem de capa.</li>
                </ul>

                <h3>Atividade na comunidade</h3>
                <ul>
                    <li>
                        <strong>Discord:</strong> o bot lê as mensagens dos canais do servidor da He4rt, incluindo o
                        conteúdo, anexos, menções, reações e respostas. Ele também registra entrada e saída em canais
                        de voz, entrada e saída do servidor, apelido, cargos e foto. Isso vale para todas as pessoas do
                        servidor, mesmo sem conta na plataforma.
                    </li>
                    <li>
                        <strong>Twitch:</strong> mensagens do chat e eventos (follows, inscrições, bits) das lives
                        acompanhadas pela He4rt, com o nome de usuário de quem participou.
                    </li>
                    <li>
                        <strong>GitHub:</strong> contribuições públicas (pull requests, issues e revisões) nos
                        repositórios acompanhados pela He4rt, com o login de quem contribuiu.
                    </li>
                    <li><strong>Dev.to:</strong> artigos publicados na organização He4rt.</li>
                    <li><strong>WhatsApp:</strong> eventos dos grupos oficiais da comunidade.</li>
                    <li>
                        <strong>Plataforma e app:</strong> posts, respostas, imagens e curtidas na timeline; inscrições,
                        respostas de formulário e check-ins em eventos; feedbacks; denúncias e recursos de moderação.
                    </li>
                    <li>
                        <strong>Gamificação e economia:</strong> experiência (XP), nível, reputação, conquistas,
                        ranking, saldo de moedas e transações.
                    </li>
                </ul>

                <h3>Dados técnicos</h3>
                <ul>
                    <li>Endereço IP e navegador (user agent) da sessão quando você entra na plataforma.</li>
                    <li>
                        Em cada clique em um link curto: IP, navegador, sistema operacional, tipo de dispositivo, país
                        aproximado (informado pela Cloudflare), página de origem e parâmetros de campanha (UTM). Se você
                        estiver logado, o clique fica ligado à sua conta.
                    </li>
                    <li>Registros técnicos (logs) de erros e de desempenho.</li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="finalidades" :number="$number['finalidades'] + 1" :title="$sections['finalidades']">
                <p>Cada uso tem uma base legal da LGPD (art. 7º):</p>
                <ul>
                    <li>
                        <strong>Criar e manter sua conta, login e perfil</strong> — execução do serviço que você pediu
                        (art. 7º, V).
                    </li>
                    <li>
                        <strong>Gamificação, ranking, moedas, eventos e timeline</strong> — execução do serviço (art.
                        7º, V).
                    </li>
                    <li>
                        <strong>Moderar o Discord e a plataforma</strong> para manter um espaço seguro — legítimo
                        interesse da comunidade e das pessoas que participam dela (art. 7º, IX).
                    </li>
                    <li>
                        <strong>Medir campanhas e links curtos</strong> e contar contribuições para a retrospectiva da
                        comunidade — legítimo interesse (art. 7º, IX).
                    </li>
                    <li>
                        <strong>Segurança, prevenção de fraude e correção de erros</strong> — legítimo interesse (art.
                        7º, IX).
                    </li>
                    <li>
                        <strong>Dados de perfil opcionais</strong> (como pretensão salarial e disponibilidade) —
                        consentimento, dado quando você preenche o campo (art. 7º, I). Para retirar o consentimento,
                        apague o campo.
                    </li>
                    <li><strong>Cumprir obrigações legais</strong> e pedidos de autoridades (art. 7º, II).</li>
                </ul>
                <p>Não vendemos dados pessoais. Não usamos seus dados para publicidade de terceiros.</p>
            </x-portal::legal.section>

            <x-portal::legal.section id="moderacao" :number="$number['moderacao'] + 1" :title="$sections['moderacao']">
                <p>A moderação do Discord combina regras automáticas, inteligência artificial e pessoas:</p>
                <ul>
                    <li>
                        <strong>Regras automáticas:</strong> cada mensagem passa por regras (por exemplo, padrões de
                        golpe ou spam). Quando uma regra encontra uma violação, o bot pode aplicar
                        <strong>sem revisão humana</strong> um silenciamento ou suspensão de 24 horas ou um banimento.
                    </li>
                    <li>
                        <strong>Inteligência artificial:</strong> mensagens que nenhuma regra marcou podem ser enviadas
                        ao serviço de moderação da OpenAI, que devolve uma pontuação de risco. A IA só abre um caso
                        para análise. Ela <strong>nunca</strong> aplica punição sozinha.
                    </li>
                    <li>
                        <strong>Histórico:</strong> a sugestão de punição considera as ocorrências anteriores da mesma
                        pessoa. A decisão final de um caso aberto é de uma pessoa da moderação.
                    </li>
                </ul>
                <p>
                    Você tem o direito de pedir a <strong>revisão de uma decisão automatizada</strong> (LGPD, art. 20).
                    Abra um recurso em até 7 dias após a punição. Uma pessoa da moderação responde em até 48 horas.
                    Você também pode pedir as razões da decisão pelos canais de <a href="#contato">Contato</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="compartilhamento" :number="$number['compartilhamento'] + 1" :title="$sections['compartilhamento']">
                <p>Compartilhamos dados só com quem nos ajuda a operar o serviço:</p>
                <ul>
                    <li><strong>Discord, Twitch, GitHub e Dev.to</strong> — login e integrações que você usa.</li>
                    <li><strong>OpenAI</strong> — conteúdo de mensagens, só para a classificação de moderação.</li>
                    <li><strong>Cloudflare</strong> — rede de entrega e proteção do site.</li>
                    <li><strong>Provedores de hospedagem e armazenamento</strong> — servidores, arquivos e backups.</li>
                    <li><strong>Provedor de e-mail</strong> — envio de e-mails da plataforma.</li>
                    <li><strong>Ferramentas de monitoramento</strong> — registros de erros e de desempenho.</li>
                    <li>
                        <strong>Autoridades</strong> — quando a lei obrigar ou para proteger direitos da He4rt e da
                        comunidade.
                    </li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section
                id="transferencia-internacional"
                :number="$number['transferencia-internacional'] + 1"
                :title="$sections['transferencia-internacional']"
            >
                <p>
                    Alguns desses serviços (como Discord, OpenAI, GitHub e Cloudflare) guardam ou processam dados fora
                    do Brasil, principalmente nos Estados Unidos. Fazemos essas transferências conforme o art. 33 da
                    LGPD, com base nas garantias contratuais desses fornecedores.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="dados-publicos" :number="$number['dados-publicos'] + 1" :title="$sections['dados-publicos']">
                <ul>
                    <li>Nome de usuário, foto, nível, conquistas e posição no ranking.</li>
                    <li>Posts e respostas na timeline.</li>
                    <li>
                        Contribuições no GitHub e artigos no Dev.to, que já são públicos na origem, nas páginas de
                        retrospectiva e de artigos.
                    </li>
                    <li>Fotos dos encontros na galeria.</li>
                    <li>Inscrição em eventos, quando você escolhe deixá-la pública.</li>
                </ul>
                <p>
                    Não quer aparecer em uma foto da galeria? Peça a remoção pelos canais de
                    <a href="#contato">Contato</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="cookies" :number="$number['cookies'] + 1" :title="$sections['cookies']">
                <ul>
                    <li><strong>Cookie de sessão:</strong> mantém você logado. Expira depois de 2 horas sem uso.</li>
                    <li><strong>Cookie de segurança (CSRF):</strong> protege os formulários contra ataques.</li>
                    <li><strong>Cookie de idioma:</strong> guarda o idioma que você escolheu.</li>
                    <li>
                        <strong>App mobile:</strong> o app guarda no aparelho um token de acesso que vale 60 minutos e
                        pode ser renovado por até 14 dias. Sair da conta invalida o token.
                    </li>
                </ul>
                <p>Não usamos cookies de publicidade nem de rastreamento de terceiros.</p>
            </x-portal::legal.section>

            <x-portal::legal.section id="retencao" :number="$number['retencao'] + 1" :title="$sections['retencao']">
                <ul>
                    <li>Dados da conta e do perfil: enquanto a conta existir.</li>
                    <li>
                        Mensagens, atividade e histórico de moderação: enquanto forem necessários para a gamificação,
                        para a retrospectiva da comunidade e para a segurança do servidor.
                    </li>
                    <li>Cliques em links curtos: enquanto forem úteis para medir campanhas.</li>
                    <li>Backups: substituídos em ciclo pelos backups mais novos.</li>
                </ul>
                <p>
                    Quando um dado não for mais necessário, ou quando você pedir a exclusão, apagamos ou anonimizamos o
                    dado. Podemos manter o mínimo exigido por lei ou necessário para defender direitos.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="seguranca" :number="$number['seguranca'] + 1" :title="$sections['seguranca']">
                <ul>
                    <li>Conexão criptografada (HTTPS) em todo o site.</li>
                    <li>Tokens de acesso e chaves de API guardados com criptografia.</li>
                    <li>Acesso administrativo restrito por papéis e permissões.</li>
                    <li>Backups protegidos e guardados fora do servidor principal.</li>
                </ul>
                <p>
                    Nenhum sistema é 100% seguro. Se houver um incidente que traga risco para você, avisaremos você e a
                    Autoridade Nacional de Proteção de Dados (ANPD), como pede a LGPD.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="direitos" :number="$number['direitos'] + 1" :title="$sections['direitos']">
                <p>Pela LGPD (art. 18), você pode pedir a qualquer momento:</p>
                <ul>
                    <li>Confirmação de que tratamos seus dados e acesso a eles.</li>
                    <li>Correção de dados incompletos, errados ou desatualizados.</li>
                    <li>Anonimização, bloqueio ou exclusão de dados desnecessários ou tratados em excesso.</li>
                    <li>Portabilidade dos seus dados.</li>
                    <li>Exclusão dos dados tratados com o seu consentimento.</li>
                    <li>Informação sobre com quem compartilhamos seus dados.</li>
                    <li>Revogação do consentimento.</li>
                    <li>Revisão de decisões automatizadas (veja <a href="#moderacao">Moderação</a>).</li>
                    <li>Exclusão da sua conta.</li>
                </ul>
                <p>
                    Envie o pedido pelos canais de <a href="#contato">Contato</a>. Podemos pedir uma confirmação de
                    identidade, por exemplo pela conta do Discord ligada à plataforma. Respondemos em até 15 dias. Você
                    também pode reclamar na <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">ANPD</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="menores" :number="$number['menores'] + 1" :title="$sections['menores']">
                <p>
                    A He4rt não é para menores de 13 anos, a mesma idade mínima do Discord. Pessoas entre 13 e 17 anos
                    devem usar a plataforma com o conhecimento de um responsável. Se você souber de uma conta de menor
                    de 13 anos, fale com a gente para removermos os dados.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="alteracoes" :number="$number['alteracoes'] + 1" :title="$sections['alteracoes']">
                <p>
                    Podemos atualizar esta política quando o serviço mudar. A data no topo da página mostra a última
                    versão. Mudanças importantes serão avisadas no Discord da comunidade.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="contato" :number="$number['contato'] + 1" :title="$sections['contato']">
                <p>Para dúvidas, pedidos de direitos ou denúncias sobre privacidade:</p>
                <ul>
                    @foreach ($contacts as $contact)
                        <li>
                            Encarregado: <strong>{{ $contact->name }}</strong> —
                            <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                        </li>
                    @endforeach

                    <li>
                        Discord da comunidade:
                        <a href="{{ $discordUrl }}" target="_blank" rel="noopener">{{ $discordUrl }}</a>
                        — fale com a equipe de moderação.
                    </li>
                </ul>
            </x-portal::legal.section>
        </article>
    </div>
</div>
