<section class="hp-section">
    <div class="hp-container">
        <div>
            <x-he4rt::headline align="center">
                <x-slot:badge>
                    <x-he4rt::section-title>Lineup</x-he4rt::section-title>
                </x-slot>
                <x-slot:title>Fique por dentro da programação do evento</x-slot>
                <x-slot:description>
                    Conteúdos e palestras sobre as mais modernas tecnologias, desde o back-end, até o front-end.
                </x-slot>
            </x-he4rt::headline>
        </div>

        <div class="mx-auto mt-24 flex max-w-5xl flex-col gap-4">
            <x-he4rt::schedule-card starts-at="15:00" title="Abertura e Início da Live (Twitch)" />
            <x-he4rt::schedule-card starts-at="15:30" title="Talk Juliana Gaioso (DevSec PicPay) - Tema" />
            <x-he4rt::schedule-card starts-at="15:50" title="Talk Fernanda Fagundes (Ipê) - Tema" />
            <x-he4rt::schedule-card starts-at="16:10" title="Ações social I (Cestas Básicas)" />
            <x-he4rt::schedule-card starts-at="16:30" title="Coffee Break" />
            <x-he4rt::schedule-card starts-at="17:00" title="Talk Tatiana Barros - Tema" />
            <x-he4rt::schedule-card starts-at="17:20" title="Talk Daniel Reis - Tema" />
            <x-he4rt::schedule-card starts-at="17:40" title="Ações social II (Materiais Escolares)" />
            <x-he4rt::schedule-card
                starts-at="17:45"
                title="Roda de Conversa com ?????????? - IA como ferramenta do dia a dia"
            />
            <x-he4rt::schedule-card starts-at="18:50" title="Lançamento da comunidade 3 Pontos" />
            <x-he4rt::schedule-card starts-at="19:20" title="Hunting de Oportunidades para Comunidade" />
            <x-he4rt::schedule-card starts-at="20:00" title="Encerramento e Agradecimentos" />
        </div>
    </div>
</section>
