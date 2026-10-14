@php
    $sections = [
        'aceitacao' => 'Aceitação',
        'servicos' => 'O que a He4rt oferece',
        'conta' => 'Sua conta',
        'conduta' => 'Regras de conduta',
        'conteudo' => 'Seu conteúdo',
        'moderacao' => 'Moderação e sanções',
        'gamificacao' => 'XP, níveis e moedas',
        'eventos' => 'Eventos',
        'terceiros' => 'Serviços de terceiros',
        'propriedade' => 'Propriedade intelectual',
        'disponibilidade' => 'Disponibilidade e responsabilidade',
        'encerramento' => 'Encerramento',
        'alteracoes' => 'Alterações destes termos',
        'lei' => 'Lei aplicável',
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
                    Regras da comunidade
                </p>

                <h1 class="text-text-high text-4xl leading-[1.08] font-bold tracking-tight lg:text-5xl">
                    Termos de <span class="text-primary">uso</span>
                </h1>

                <p class="text-text-medium max-w-xl text-base leading-relaxed">
                    As regras para usar o site, os painéis, o app mobile e o bot do Discord da He4rt Developers.
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
            <x-portal::legal.section id="aceitacao" :number="$number['aceitacao'] + 1" :title="$sections['aceitacao']">
                <p>
                    Estes termos são um acordo entre você e a <strong>He4rt Developers</strong>, comunidade brasileira
                    de pessoas desenvolvedoras. Eles valem para o site heartdevs.com, os painéis web, o app mobile, o
                    bot e o servidor oficial do Discord e os grupos oficiais da comunidade.
                </p>
                <p>
                    Ao entrar na plataforma, conectar uma conta ou participar do servidor, você concorda com estes
                    termos e com a <a href="{{ route('privacy-policy', absolute: false) }}">política de privacidade</a>.
                    Se não concordar, não use os serviços.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="servicos" :number="$number['servicos'] + 1" :title="$sections['servicos']">
                <ul>
                    <li>Perfil profissional, timeline de posts e página de artigos da comunidade.</li>
                    <li>Gamificação: XP, níveis, conquistas, ranking por temporada e moedas virtuais.</li>
                    <li>Eventos: inscrição, check-in e galeria de fotos.</li>
                    <li>Bot do Discord: comandos, recompensas por participação e moderação.</li>
                    <li>Integrações com Discord, Twitch, GitHub e Dev.to.</li>
                </ul>
                <p>
                    A He4rt é uma comunidade mantida por voluntários. Os serviços são gratuitos e podem mudar, ganhar ou
                    perder funções a qualquer momento.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="conta" :number="$number['conta'] + 1" :title="$sections['conta']">
                <ul>
                    <li>Você precisa ter pelo menos 13 anos. Entre 13 e 17 anos, use com o conhecimento de um responsável.</li>
                    <li>
                        O login é feito por uma conta do Discord, GitHub, Twitch ou Dev.to. Você é responsável pela
                        segurança dessas contas.
                    </li>
                    <li>Use dados verdadeiros. Não se passe por outra pessoa, marca ou membro da equipe.</li>
                    <li>Uma pessoa, uma conta. Contas extras para ganhar XP ou moedas são proibidas.</li>
                    <li>Não compartilhe, venda nem transfira a sua conta.</li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="conduta" :number="$number['conduta'] + 1" :title="$sections['conduta']">
                <p>A He4rt é um espaço de aprendizado e respeito. É proibido:</p>
                <ul>
                    <li>
                        Assediar, ameaçar ou discriminar pessoas por raça, gênero, orientação sexual, religião,
                        deficiência, origem, idade ou qualquer outra característica.
                    </li>
                    <li>Publicar conteúdo sexual explícito, violento ou que incentive automutilação.</li>
                    <li>Enviar spam, golpes, phishing, malware ou links enganosos.</li>
                    <li>Divulgar dados pessoais de outras pessoas sem autorização.</li>
                    <li>Fazer divulgação comercial fora dos canais permitidos.</li>
                    <li>Publicar conteúdo que viole direitos autorais ou a lei brasileira.</li>
                    <li>
                        Burlar o sistema: automatizar mensagens (farm de XP), explorar falhas, ou tentar acessar dados e
                        áreas sem permissão.
                    </li>
                    <li>Atrapalhar o funcionamento dos serviços ou sobrecarregar a infraestrutura.</li>
                </ul>
                <p>
                    Achou uma falha de segurança? Avise a equipe em privado pelos canais de
                    <a href="#contato">Contato</a>, sem explorar nem divulgar. Agradecemos quem reporta com
                    responsabilidade.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="conteudo" :number="$number['conteudo'] + 1" :title="$sections['conteudo']">
                <ul>
                    <li>O que você publica (mensagens, posts, imagens, artigos) continua sendo seu.</li>
                    <li>
                        Ao publicar, você dá à He4rt uma licença gratuita e não exclusiva para guardar, exibir e
                        reproduzir esse conteúdo nos serviços da comunidade, como a timeline, a retrospectiva e a
                        página de artigos. A licença termina quando o conteúdo for apagado, exceto em backups e cópias
                        guardadas para moderação.
                    </li>
                    <li>Você garante que tem o direito de publicar o que publica.</li>
                    <li>A He4rt pode remover conteúdo que viole estes termos.</li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="moderacao" :number="$number['moderacao'] + 1" :title="$sections['moderacao']">
                <p>
                    A moderação usa regras automáticas, inteligência artificial e a equipe de moderação. A
                    <a href="{{ route('privacy-policy', absolute: false) }}#moderacao">política de privacidade</a>
                    explica como isso funciona.
                </p>
                <p>Conforme a gravidade e o histórico, as sanções são:</p>
                <ul>
                    <li>Aviso ou remoção do conteúdo.</li>
                    <li>Silenciamento ou suspensão temporária.</li>
                    <li>Perda de XP, moedas, conquistas ou posição no ranking.</li>
                    <li>Banimento do servidor e da plataforma.</li>
                </ul>
                <p>
                    Algumas sanções são aplicadas automaticamente pelo bot. Você pode abrir um recurso em até 7 dias
                    após a sanção. Uma pessoa da moderação revisa e responde em até 48 horas. Casos graves ou ilegais
                    podem ser levados às autoridades.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="gamificacao" :number="$number['gamificacao'] + 1" :title="$sections['gamificacao']">
                <ul>
                    <li>
                        XP, níveis, conquistas e moedas são recompensas da comunidade. Eles
                        <strong>não têm valor em dinheiro</strong>, não podem ser trocados por dinheiro e não são
                        propriedade sua.
                    </li>
                    <li>Moedas não podem ser vendidas nem transferidas fora das funções oferecidas pela plataforma.</li>
                    <li>
                        A He4rt pode mudar as regras de pontuação, encerrar temporadas, reiniciar rankings e corrigir
                        saldos ganhos por erro ou por abuso.
                    </li>
                    <li>Recompensas que dependem de revisão podem ser negadas pela equipe.</li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="eventos" :number="$number['eventos'] + 1" :title="$sections['eventos']">
                <ul>
                    <li>A inscrição pode depender de vagas, de formulário e de aprovação da organização.</li>
                    <li>A organização pode mudar a data, o local ou cancelar um evento.</li>
                    <li>Siga as regras do local e destes termos durante o evento.</li>
                    <li>
                        Os encontros podem ser fotografados e as fotos podem ir para a galeria do site. Se não quiser
                        aparecer, avise a organização no evento ou peça a remoção depois.
                    </li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="terceiros" :number="$number['terceiros'] + 1" :title="$sections['terceiros']">
                <p>
                    Discord, Twitch, GitHub, Dev.to e WhatsApp são serviços de outras empresas, com termos próprios que
                    você também precisa seguir. A He4rt não controla esses serviços e não responde por falhas ou
                    mudanças neles. Os links curtos e os links de terceiros podem levar a sites fora da He4rt.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="propriedade" :number="$number['propriedade'] + 1" :title="$sections['propriedade']">
                <p>
                    O nome, a marca e o logotipo da He4rt Developers pertencem à comunidade. Não use a marca para
                    sugerir apoio ou parceria sem autorização.
                </p>
                <p>
                    O código-fonte da plataforma é aberto, sob a licença MIT, no
                    <a href="https://github.com/he4rt" target="_blank" rel="noopener">GitHub da He4rt</a>.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section
                id="disponibilidade"
                :number="$number['disponibilidade'] + 1"
                :title="$sections['disponibilidade']"
            >
                <ul>
                    <li>
                        Os serviços são oferecidos "no estado em que se encontram". Não garantimos que fiquem sempre
                        disponíveis ou sem erros.
                    </li>
                    <li>
                        Conteúdo publicado por membros, como dicas, artigos e vagas, é responsabilidade de quem publicou.
                        A He4rt não garante que esteja correto.
                    </li>
                    <li>
                        Na medida permitida pela lei, a He4rt não responde por danos indiretos, perda de dados ou lucros
                        cessantes causados pelo uso dos serviços. Nada nestes termos limita direitos que a lei não deixa
                        limitar.
                    </li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="encerramento" :number="$number['encerramento'] + 1" :title="$sections['encerramento']">
                <ul>
                    <li>
                        Você pode parar de usar os serviços quando quiser e pedir a exclusão da conta pelos canais de
                        <a href="#contato">Contato</a>.
                    </li>
                    <li>
                        A He4rt pode suspender ou encerrar uma conta que viole estes termos, conforme a seção
                        <a href="#moderacao">Moderação e sanções</a>.
                    </li>
                    <li>Quando a conta é encerrada, XP, moedas e conquistas são perdidos.</li>
                </ul>
            </x-portal::legal.section>

            <x-portal::legal.section id="alteracoes" :number="$number['alteracoes'] + 1" :title="$sections['alteracoes']">
                <p>
                    Podemos atualizar estes termos. A data no topo mostra a última versão. Mudanças importantes serão
                    avisadas no Discord da comunidade. Continuar usando os serviços depois do aviso significa aceitar a
                    nova versão.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="lei" :number="$number['lei'] + 1" :title="$sections['lei']">
                <p>
                    Estes termos seguem as leis do Brasil, incluindo o Marco Civil da Internet (Lei nº 12.965/2014) e
                    a LGPD (Lei nº 13.709/2018). Conflitos serão resolvidos no foro do domicílio de quem usa os
                    serviços, como garante o Código de Defesa do Consumidor.
                </p>
            </x-portal::legal.section>

            <x-portal::legal.section id="contato" :number="$number['contato'] + 1" :title="$sections['contato']">
                <p>Para dúvidas, recursos ou denúncias:</p>
                <ul>
                    @foreach ($contacts as $contact)
                        <li>
                            {{ $contact->name }}:
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
